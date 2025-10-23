<?php

namespace UserBundle\Controller;

use AppBundle\Controller\BaseController\BaseController;
use AppBundle\Enums\FeatureEnum;
use ReportBundle\Service\ReportAccessService;
use Symfony\Component\Routing\Annotation\Route;
use UserBundle\Enums\ProfileRolePermissionEnum;

class ReportPermissionController extends BaseController
{
    public function __construct(private readonly ReportAccessService $reportAccessService)
    {
    }

    #[Route('/user/profile/report-permissions', name: 'profile-list-report-permissions', methods: ['GET'])]
    public function listReportPermissions(): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $this->denyAccessUnlessGranted(ProfileRolePermissionEnum::READ, FeatureEnum::USERS_PROFILES);

        return $this->json($this->reportAccessService->findCategoriesWithReports());
    }
}
