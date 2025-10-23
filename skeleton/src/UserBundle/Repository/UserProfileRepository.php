<?php

namespace UserBundle\Repository;

use AppBundle\Repository\RepositoryService;
use Doctrine\Persistence\ManagerRegistry;
use ReportBundle\Entity\ReportCategoryEntity;
use UserBundle\Entity\UserProfile;

/**
 * @extends RepositoryService<UserProfile>
 */
class UserProfileRepository extends RepositoryService
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly string $adminProfilesId,
    ) {
        parent::__construct($registry, UserProfile::class);
    }

    public function addReportCategoryToAdminProfiles(ReportCategoryEntity $category): void
    {
        /** @var UserProfile[] $userProfiles */
        $userProfiles = $this->findBy(['id' => \explode(',', $this->adminProfilesId)]);

        foreach ($userProfiles as $userProfile) {
            $userProfile->addAllowedReportingCategory($category);
            $this->getEntityManager()->persist($userProfile);
            $this->getEntityManager()->flush();
        }
    }
}
