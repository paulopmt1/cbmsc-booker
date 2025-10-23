<?php

namespace UserBundle\Service;

use AppBundle\Dto\UserDataDto;
use AppBundle\Entity\Feature;
use AppBundle\Entity\System;
use AppBundle\Enums\PageEnum;
use AppBundle\Exception\ForbiddenException;
use AppBundle\Exception\UniqueException;
use AppBundle\Exception\ValidationException;
use AppBundle\Model\BaseModel\BaseModelPaginate;
use AppBundle\Repository\FeatureRepository;
use AppBundle\Repository\SystemRepository;
use AppBundle\Service\PaginateTrait;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use ReportBundle\Service\ReportAccessService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UserBundle\Dto\SaveUserProfileDto;
use UserBundle\Entity\UserProfile;
use UserBundle\Entity\UserProfileRoles;
use UserBundle\Enums\ProfileRolePermissionEnum;
use UserBundle\Repository\UserProfileRepository;

class UserProfileService
{
    use PaginateTrait;

    private UserProfileRepository $userProfileRepository;

    private FeatureRepository $featureRepository;

    private SystemRepository $systemRepository;

    private EntityManagerInterface $entityManager;

    private ValidatorInterface $validator;

    public function __construct(
        private ReportAccessService $reportAccessService,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
    ) {
        /**
         * @var UserProfileRepository $userProfileRepository
         */
        $userProfileRepository = $entityManager->getRepository(UserProfile::class);

        /**
         * @var FeatureRepository $featureRepository
         */
        $featureRepository = $entityManager->getRepository(Feature::class);

        /**
         * @var SystemRepository $systemRepository
         */
        $systemRepository = $entityManager->getRepository(System::class);

        $this->userProfileRepository = $userProfileRepository;
        $this->featureRepository = $featureRepository;
        $this->systemRepository = $systemRepository;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    /**
     * @return list<array{
     *     id: int,
     *     name: string,
     *     features: list<array{
     *         id: int,
     *         name: string,
     *         alias: string|null,
     *         actions: list<array{
     *             id: int,
     *             name: string,
     *             available: bool
     *         }>
     *     }>
     * }>
     */
    public function getRoles(UserDataDto $userDataDto): array
    {
        $currentProfile = $userDataDto->getUser()->getProfile();
        $currentProfileRoles = $currentProfile ? $currentProfile->getRoles() : [];

        $featurePermissions = [];
        foreach ($currentProfileRoles as $role) {
            $featurePermissions[$role->getFeature()->getId()] = $role->getAction();
        }

        $roles = [];
        $systems = $this->systemRepository->findBy([], ['description' => 'ASC']);

        foreach ($systems as $system) {
            $systemFeatures = $this->featureRepository->getByFilters([
                'systemId' => $system->getId(),
            ]);

            $features = [];

            foreach ($systemFeatures as $feature) {
                if (!isset($featurePermissions[$feature->getId()])) {
                    continue;
                }

                $permissionLevel = $featurePermissions[$feature->getId()];
                $availableActions = [];

                foreach (ProfileRolePermissionEnum::getValues() as $value) {
                    $availableActions[] = [
                        'id' => $value,
                        'name' => ProfileRolePermissionEnum::getDescription($value),
                        'available' => $value <= $permissionLevel,
                    ];
                }

                $features[] = [
                    'id' => $feature->getId(),
                    'name' => $feature->getDescription(),
                    'alias' => $feature->getAlias(),
                    'actions' => $availableActions,
                ];
            }

            if (\count($features) > 0) {
                $roles[] = [
                    'id' => $system->getId(),
                    'name' => $system->getDescription(),
                    'features' => $features,
                ];
            }
        }

        return $roles;
    }

    /**
     * @param array{search?: string} $filters
     */
    public function findAllPaginated(
        int $page = 1,
        int $limit = PageEnum::DEFAULT_ITEMS_PER_PAGE,
        array $filters = [],
    ): BaseModelPaginate {
        $query = $this->userProfileRepository->createQueryBuilder('profile');

        if (!empty($filters['search'])) {
            $query
                ->andWhere('LOWER(unaccent(profile.name)) LIKE LOWER(unaccent(:search))')
                ->setParameter('search', "%{$filters['search']}%");
        }

        return $this->paginate(
            $query->orderBy('profile.name', 'ASC'),
            $page,
            $limit
        );
    }

    /**
     * @param array<string, string> $orderBy
     *
     * @return UserProfile[]
     */
    public function findAll(array $orderBy = ['name' => 'ASC']): array
    {
        return $this->userProfileRepository->findBy([], $orderBy);
    }

    public function findOne(int $id): ?UserProfile
    {
        return $this->userProfileRepository->find($id);
    }

    /**
     * @throws ValidationException|UniqueException|ForbiddenException
     */
    public function save(SaveUserProfileDto $data, UserDataDto $userDataDto, ?UserProfile $userProfile = null): UserProfile
    {
        $userProfile = $userProfile ?? new UserProfile();
        if ($userProfile->hasUserId($userDataDto->getUser()->getUid())) {
            throw new ForbiddenException();
        }

        $errors = $this->validator->validate($data);
        if (\count($errors) > 0) {
            throw new ValidationException($errors);
        }

        try {
            $this->entityManager->beginTransaction();

            $currentProfile = $userDataDto->getUser()->getProfile();
            $currentProfileRoles = $currentProfile ? $currentProfile->getRoles() : [];

            $featurePermissions = [];
            foreach ($currentProfileRoles as $role) {
                $featurePermissions[$role->getFeature()->getId()] = $role->getAction();
            }

            $userProfile->setName($data->name)
                ->setFilterSociobioSponsors($data->filterSociobioSponsors)
                ->setFilterProgramSponsors($data->filterProgramSponsors);

            foreach ($userProfile->getRoles() as $role) {
                $permissionLevel = $featurePermissions[$role->getFeature()->getId()] ?? 0;
                $noHasRole = null === \array_find($data->roles, fn ($item) => $item['feature'] === $role->getFeature()->getId());

                if ($permissionLevel && $permissionLevel >= $role->getAction() && $noHasRole) {
                    $userProfile->removeRole($role);
                    $this->entityManager->remove($role);
                }
            }

            foreach ($data->roles as $role) {
                $feature = $this->featureRepository->find($role['feature']);
                if (null === $feature) {
                    continue;
                }

                $permissionLevel = $featurePermissions[$feature->getId()];

                if (!$permissionLevel || $permissionLevel < $role['action']) {
                    throw new ForbiddenException(\sprintf("Não é permitido atribuir '%s' à %s - %s", ProfileRolePermissionEnum::getDescription($role['action']), $feature->getSystem()->getDescription(), $feature->getDescription()));
                }

                $existingRole = $userProfile->getRoleByFeature($feature);
                if ($existingRole) {
                    $existingRole->setAction($role['action']);
                    continue;
                }

                $userProfile->addRole(
                    new UserProfileRoles(
                        $userProfile,
                        $feature,
                        $role['action'],
                    )
                );
            }

            $userProfile->updateReportPermissions(
                $this->reportAccessService->findCategoriesByIds($data->allowedReportingCategoryIds),
                $this->reportAccessService->findReportsByIds($data->allowedReportIds)
            );

            $this->entityManager->persist($userProfile);
            $this->entityManager->flush();
            $this->entityManager->commit();

            return $userProfile;
        } catch (UniqueConstraintViolationException $exception) {
            $this->entityManager->rollback();
            throw new UniqueException('Nome de perfil já cadastrado', $exception);
        }
    }

    public function delete(UserProfile $userProfile, UserDataDto $userDataDto): void
    {
        if ($userProfile->hasUserId($userDataDto->getUser()->getUid())) {
            throw new ForbiddenException();
        }

        if ($userProfile->hasUser()) {
            throw new BadRequestHttpException('Não é possível remover o perfil pois existem usuários vinculados.');
        }

        $this->entityManager->remove($userProfile);
        $this->entityManager->flush();
    }
}
