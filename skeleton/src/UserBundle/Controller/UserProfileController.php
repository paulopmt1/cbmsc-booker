<?php

namespace UserBundle\Controller;

use AppBundle\Controller\BaseController\BaseController;
use AppBundle\Enums\FeatureEnum;
use AppBundle\Enums\PageEnum;
use AppBundle\Exception\ForbiddenException;
use AppBundle\Exception\UniqueException;
use AppBundle\Exception\ValidationException;
use AppBundle\Helper\DeserializerHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;
use UserBundle\Dto\SaveUserProfileDto;
use UserBundle\Entity\UserProfile;
use UserBundle\Enums\ProfileRolePermissionEnum;
use UserBundle\Service\UserProfileService;

class UserProfileController extends BaseController
{
    /**
     * @var UserProfileService
     */
    private $userProfileService;

    public function __construct(UserProfileService $userProfileService)
    {
        $this->userProfileService = $userProfileService;
    }

    public function roles(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProfileRolePermissionEnum::READ, FeatureEnum::USERS_PROFILES);

        return $this->json($this->userProfileService->getRoles($this->getCurrentUserData($request)));
    }

    public function list(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProfileRolePermissionEnum::READ, FeatureEnum::USERS_PROFILES);

        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('pageSize', PageEnum::DEFAULT_ITEMS_PER_PAGE);

        $filters = ['search' => (string) $request->query->get('search')];

        $constraints = new Collection([
            'search' => [new Optional(new Type('string'))],
        ]);

        $this->validate($filters, $constraints);

        return $this->json($this->userProfileService->findAllPaginated($page, $limit, $filters));
    }

    public function listAll(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProfileRolePermissionEnum::READ, FeatureEnum::USERS_PROFILES);

        return $this->json($this->userProfileService->findAll());
    }

    public function getOne(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProfileRolePermissionEnum::READ, FeatureEnum::USERS_PROFILES);
        $user = $this->userProfileService->findOne($id);

        if (null == $user) {
            return $this->notFound();
        }

        return $this->json($user);
    }

    /**
     * @throws ForbiddenException
     * @throws UniqueException
     * @throws ValidationException
     */
    public function create(Request $request, DeserializerHelper $deserializer): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProfileRolePermissionEnum::CREATE, FeatureEnum::USERS_PROFILES);

        return $this->json(
            $this->userProfileService->save(
                $deserializer->deserializeJson($request->getContent(), SaveUserProfileDto::class),
                $this->getCurrentUserData($request)
            )
        );
    }

    /**
     * @throws ForbiddenException
     * @throws UniqueException
     * @throws ValidationException
     */
    public function edit(int $id, Request $request, DeserializerHelper $deserializer): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProfileRolePermissionEnum::EDIT, FeatureEnum::USERS_PROFILES);
        $userProfile = $this->userProfileService->findOne($id);

        if (null == $userProfile) {
            return $this->notFound();
        }

        return $this->json($this->userProfileService->save(
            $deserializer->deserializeJson($request->getContent(), SaveUserProfileDto::class),
            $this->getCurrentUserData($request),
            $userProfile
        ));
    }

    /**
     * @throws ForbiddenException
     */
    public function delete(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProfileRolePermissionEnum::DELETE, FeatureEnum::USERS_PROFILES);
        /**
         * @var UserProfile $userProfile
         */
        $userProfile = $this->userProfileService->findOne($id);

        if (null == $userProfile) {
            return $this->notFound();
        }

        $this->userProfileService->delete($userProfile, $this->getCurrentUserData($request));

        return $this->removedMessage();
    }
}
