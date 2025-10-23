<?php

namespace UserBundle\Service;

use AppBundle\Exception\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use SociobioBundle\Entity\BillingMonitoring\BillingMonitoringEntity;
use SociobioBundle\Exception\BillingMonitoringConstraintException;
use SociobioBundle\Repository\BillingMonitoring\BillingMonitoringRepository;
use UserBundle\Entity\Program;
use UserBundle\Repository\ProgramRepository;

class ProgramService
{
    private ProgramRepository $programRepository;

    private BillingMonitoringRepository $billingMonitoringRepository;

    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager,
    ) {
        $this->entityManager = $entityManager;

        /** @var ProgramRepository $programRepository */
        $programRepository = $this->entityManager->getRepository(Program::class);
        $this->programRepository = $programRepository;

        /** @var BillingMonitoringRepository $billingMonitoringRepository $ */
        $billingMonitoringRepository = $this->entityManager->getRepository(BillingMonitoringEntity::class);
        $this->billingMonitoringRepository = $billingMonitoringRepository;
    }

    /**
     * @throws NotFoundException
     */
    public function findOneVisible(int $id): Program
    {
        return $this->programRepository->findByIdOrNotFound($id);
    }

    /**
     * @return Program[]
     */
    public function findVisibleAll(): array
    {
        return $this->programRepository->findBy([
            'visible' => true,
            'reportOnly' => false,
        ], [
            'name' => 'ASC',
        ]);
    }

    /**
     * @return Program[]
     */
    public function findVisibleReport(): array
    {
        return $this->programRepository->findBy([
            'visible' => true,
        ], [
            'name' => 'ASC',
        ]);
    }

    /**
     * @param array<int> $ids
     *
     * @return Program[]
     */
    public function findVisibleByIds(array $ids = []): array
    {
        if (empty($ids)) {
            return [];
        }

        return $this->programRepository->findBy([
            'id' => $ids,
            'visible' => true,
            'reportOnly' => false,
        ]);
    }

    /**
     * @throws NotFoundException
     */
    public function findById(int $id): Program
    {
        return $this->programRepository->findByIdOrNotFound($id);
    }

    /**
     * @throws NotFoundException
     * @throws BillingMonitoringConstraintException
     */
    public function delete(int $id): bool
    {
        $this->billingMonitoringRepository->throwIfHasLinkedRecord('institutionalPrograms', $id);

        $this->entityManager->beginTransaction();

        try {
            $program = $this->programRepository->findByIdOrNotFound($id);

            $this->entityManager->remove($program);
            $this->entityManager->flush();
            $this->entityManager->commit();

            return true;
        } catch (\Exception $exception) {
            $this->entityManager->rollback();

            throw $exception;
        }
    }
}
