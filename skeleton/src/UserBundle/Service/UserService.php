<?php

namespace UserBundle\Service;

use AppBundle\Dto\UserDataDto;
use AppBundle\Exception\DomainException;
use AppBundle\Exception\ForbiddenException;
use AppBundle\Exception\NotFoundException;
use AppBundle\Exception\ValidationException;
use AppBundle\Model\BaseModel\BaseModelPaginate;
use AppBundle\Service\PaginateTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use LibrariesBundle\Entity\ResponsibleEntity;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UserBundle\Dto\CreateUserDto;
use UserBundle\Dto\UpdateUserEditableData;
use UserBundle\Entity\User;
use UserBundle\Entity\UserProfile;
use UserBundle\Entity\UserType;
use UserBundle\Enums\UserTypeEnum;
use UserBundle\Repository\UserRepository;
use UserBundle\Repository\UserTokenRepository;
use UserBundle\Repository\UserTypeRepository;

class UserService
{
    use PaginateTrait;

    private UserRepository $userRepository;

    private UserTokenRepository $userTokenRepository;

    private UserProfileService $userProfileService;

    private UserTypeRepository $userTypeRepository;

    private ProgramService $programService;

    private ValidatorInterface $validator;

    private EntityManagerInterface $entityManager;

    public function __construct(
        UserRepository $userRepository,
        UserTokenRepository $userTokenRepository,
        UserProfileService $userProfileService,
        UserTypeRepository $userTypeRepository,
        ProgramService $programService,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
    ) {
        $this->userRepository = $userRepository;
        $this->userProfileService = $userProfileService;
        $this->userTypeRepository = $userTypeRepository;
        $this->programService = $programService;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->userTokenRepository = $userTokenRepository;
    }

    /**
     * @param array{search?: string, excludeUserId?: int, userType?: string} $filters
     */
    public function readAll(int $page, int $limit, array $filters): BaseModelPaginate
    {
        $query = $this->userRepository->createQueryBuilder('user');

        if (!empty($filters['search'])) {
            $query
                ->andWhere(
                    'LOWER(concat('
                    ."unaccent(user.firstName), ' ', "
                    .'unaccent(user.lastName))) '
                    .'LIKE LOWER(unaccent(:search))'
                )
                ->orWhere('LOWER(unaccent(user.email)) LIKE LOWER(unaccent(:search))')
                ->orWhere('LOWER(unaccent(user.username)) LIKE LOWER(unaccent(:search))')
                ->setParameter('search', "%{$filters['search']}%");
        }

        if (!empty($filters['excludeUserId'])) {
            $query
                ->andWhere('user.uid != :excludeUserId')
                ->setParameter('excludeUserId', $filters['excludeUserId']);
        }

        if (!empty($filters['userType'])) {
            $query
                ->innerJoin('user.userTypes', 'ut')
                ->andWhere('ut.name = :typeName')
                ->setParameter('typeName', $filters['userType'])
                ->groupBy('user.uid');
        }

        return $this->paginate(
            $query->orderBy('user.uid', 'DESC'),
            $page,
            $limit
        );
    }

    public function read(int $id): User
    {
        /** @var User $user */
        $user = $this->userRepository->find($id);

        if (null == $user) {
            throw new NotFoundException('Usuário não encontrado');
        }

        return $user;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NotFoundException
     * @throws ValidationException
     * @throws DomainException
     */
    public function create(CreateUserDto $createUserDto): ?User
    {
        $this->validateUserData($createUserDto);

        if ($this->userRepository->existsByEmail($createUserDto->email)) {
            throw new ConflictHttpException('Já existe um usuário com esse email');
        }

        $user = $this->userRepository->createWithEmail($createUserDto->email);

        $user
            ->setFirstName($createUserDto->firstName)
            ->setLastName($createUserDto->lastName)
            ->setResponsible(
                new ResponsibleEntity()
                ->setUser($user)
                ->setName("$createUserDto->firstName $createUserDto->lastName")
            );

        $user->addUserType($this->userTypeRepository->findOneOrNotFound([
            'name' => UserTypeEnum::GOOGLE,
        ]));

        if ($createUserDto->profileId) {
            /** @var UserProfile $profile */
            $profile = $this->userProfileService->findOne($createUserDto->profileId);

            if (null == $profile) {
                throw new NotFoundException('Perfil de usuário não encontrado');
            }

            $user->setProfile($profile);
        }

        if ($createUserDto->programId) {
            $user->setProgram($this->programService->findOneVisible($createUserDto->programId));
        }

        return $this->userRepository->save($user);
    }

    /**
     * @throws ForbiddenException
     * @throws NonUniqueResultException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function update(int $id, CreateUserDto $createUserDto, UserDataDto $userDataDto): ?User
    {
        $this->ensureNotEditingSelf($userDataDto->getUser()->getUid(), $id);

        $this->validateUserData($createUserDto);

        /** @var User $user */
        $user = $this->userRepository->find($id);

        if (null == $user) {
            throw new NotFoundException('Usuário não encontrado');
        }

        if ($this->userRepository->existsByEmail($createUserDto->email, $id)) {
            throw new ConflictHttpException('Já existe um usuário com esse email');
        }

        if (\count($createUserDto->userTypes) > 1 || (1 === \count($createUserDto->userTypes) && UserTypeEnum::LDAP !== $this->userTypeRepository->findByIdOrNotFound($createUserDto->userTypes[0])->getName())) {
            $user
                ->setFirstName($createUserDto->firstName)
                ->setLastName($createUserDto->lastName);

            $user->getResponsible()->setName("$createUserDto->firstName $createUserDto->lastName");
        }

        if ($createUserDto->profileId) {
            /** @var UserProfile $profile */
            $profile = $this->userProfileService->findOne($createUserDto->profileId);

            if (null == $profile) {
                throw new NotFoundException('Perfil de usuário não encontrado');
            }

            $user->setProfile($profile);
        }

        if (($user->getProgram() ? $user->getProgram()->getId() : null) !== $createUserDto->programId) {
            $user->setProgram(
                null !== $createUserDto->programId
                    ? $this->programService->findOneVisible($createUserDto->programId)
                    : null
            );
        }

        return $this->userRepository->save($user);
    }

    /**
     * @throws ForbiddenException
     * @throws NonUniqueResultException
     * @throws NotFoundException
     */
    public function delete(int $id, UserDataDto $userDataDto): void
    {
        $this->ensureNotEditingSelf($userDataDto->getUser()->getUid(), $id);

        $this->entityManager->beginTransaction();

        try {
            /**
             * @var User $user
             */
            $user = $this->userRepository->find($id);

            if (null == $user) {
                throw new NotFoundException('Usuário não encontrado');
            }

            foreach ($this->userTokenRepository->findByUser($user) as $userToken) {
                $this->entityManager->remove($userToken);
            }

            $this->entityManager->remove($user);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Exception $exception) {
            $this->entityManager->rollback();

            throw $exception;
        }
    }

    /**
     * @return UserType[]
     */
    public function getAllUserType(): array
    {
        return $this->userTypeRepository->findAll();
    }

    public function updatePublicData(int $id, UpdateUserEditableData $data, UserDataDto $userDataDto): ?User
    {
        if ($userDataDto->getUser()->getUid() !== $id) {
            throw new ForbiddenException();
        }

        $this->validateUserData($data);

        /** @var User $user */
        $user = $this->userRepository->find($id);

        $user
            ->setFirstName($data->firstName)
            ->setLastName($data->lastName);

        return $this->userRepository->save($user);
    }

    /**
     * @throws ForbiddenException
     */
    private function ensureNotEditingSelf(int $currentUserId, int $userId): void
    {
        if ($currentUserId === $userId) {
            throw new ForbiddenException();
        }
    }

    /**
     * @param CreateUserDto|UpdateUserEditableData $data
     *
     * @throws ValidationException
     */
    private function validateUserData($data): void
    {
        $errors = $this->validator->validate($data);

        if (\count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }
}
