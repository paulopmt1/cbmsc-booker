<?php

namespace UserBundle\Controller;

use AppBundle\Controller\BaseController\BaseController;
use AppBundle\Enums\FeatureEnum;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use UserBundle\Enums\ProfileRolePermissionEnum;
use UserBundle\Service\ProgramService;

class ProgramController extends BaseController
{
    public function __construct(private readonly ProgramService $programService)
    {
    }

    #[Route('/programs', name: 'program-list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProfileRolePermissionEnum::READ, FeatureEnum::USERS);

        return $this->json($this->programService->findVisibleAll());
    }

    #[Route('/programs/report', name: 'program-report', methods: ['GET'])]
    public function listReport(): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProfileRolePermissionEnum::READ, FeatureEnum::USERS);

        return $this->json($this->programService->findVisibleReport());
    }
}
