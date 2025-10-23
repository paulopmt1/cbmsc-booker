<?php

namespace UserBundle\Controller;

use AppBundle\Controller\BaseController\BaseController;
use AppBundle\Enums\FeatureEnum;
use AppBundle\Enums\PageEnum;
use AppBundle\Exception\ForbiddenException;
use AppBundle\Exception\NotFoundException;
use AppBundle\Exception\ValidationException;
use AppBundle\Helper\DeserializerHelper;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;
use UserBundle\Dto\CreateUserDto;
use UserBundle\Dto\UpdateUserEditableData;
use UserBundle\Enums\ProfileRolePermissionEnum;
use UserBundle\Service\UserService;

class UserController extends BaseController
{
    public function __construct(private readonly UserService $userService)
    {
    }

    #[Route('/user', name: 'user-list', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProfileRolePermissionEnum::READ, FeatureEnum::USERS);

        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('pageSize', PageEnum::DEFAULT_ITEMS_PER_PAGE);

        $filters = [
            'search' => (string) $request->query->get('search'),
            'excludeUserId' => $this->getCurrentUserData($request)->getUser()->getUid(),
        ];

        $constraints = new Collection([
            'search' => [new Optional(new Type('string'))],
            'excludeUserId' => [new Type('integer')],
        ]);

        $this->validate($filters, $constraints);

        return $this->json($this->userService->readAll($page, $limit, $filters));
    }

    /**
     * @throws NotFoundException
     */
    #[Route('/user/{id}', name: 'user-detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function read(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProfileRolePermissionEnum::READ, FeatureEnum::USERS);

        return $this->json($this->userService->read($id));
    }

    /**
     * @throws OptimisticLockException
     * @throws NonUniqueResultException
     * @throws NotFoundException
     * @throws ValidationException
     */
    #[Route('/user', name: 'user-create', methods: ['POST'])]
    public function create(Request $request, DeserializerHelper $serializer): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProfileRolePermissionEnum::CREATE, FeatureEnum::USERS);

        return $this->json($this->userService->create($serializer->deserializeJson((string) $request->getContent(), CreateUserDto::class)));
    }

    #[Route('/user/types', name: 'user-type-list', methods: ['GET'])]
    public function getAllUserType(): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProfileRolePermissionEnum::READ, FeatureEnum::USERS);

        return $this->json($this->userService->getAllUserType());
    }

    /**
     * @throws OptimisticLockException
     * @throws ForbiddenException
     * @throws NonUniqueResultException
     * @throws NotFoundException
     * @throws ValidationException
     */
    #[Route('/user/{id}', name: 'user-update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(int $id, Request $request, DeserializerHelper $serializer): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProfileRolePermissionEnum::EDIT, FeatureEnum::USERS);

        return $this->json($this->userService->update(
            $id,
            $serializer->deserializeJson((string) $request->getContent(), CreateUserDto::class),
            $this->getCurrentUserData($request)
        ));
    }

    /**
     * @throws ForbiddenException
     * @throws NonUniqueResultException
     * @throws NotFoundException
     */
    #[Route('/user/{id}', name: 'user-delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProfileRolePermissionEnum::DELETE, FeatureEnum::USERS);

        $this->userService->delete($id, $this->getCurrentUserData($request));

        return $this->json(['message' => 'Usuário deletado com sucesso'], Response::HTTP_OK);
    }

    /**
     * @throws ForbiddenException
     */
    #[Route('/user/{id}/profile', name: 'user-profile-update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function profileUpdate(int $id, Request $request, DeserializerHelper $serializer): JsonResponse
    {
        return $this->json($this->userService->updatePublicData(
            $id,
            $serializer->deserializeJson((string) $request->getContent(), UpdateUserEditableData::class),
            $this->getCurrentUserData($request)
        ));
    }
}
