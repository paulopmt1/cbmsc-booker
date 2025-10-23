<?php

namespace UserBundle\Repository;

use AppBundle\Exception\NotFoundException;
use AppBundle\Repository\RepositoryService;
use Doctrine\Persistence\ManagerRegistry;
use UserBundle\Entity\Program;

/**
 * @extends RepositoryService<Program>
 */
class ProgramRepository extends RepositoryService
{
    protected string $notFoundMessage = 'Programa não encontrado';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Program::class);
    }

    /**
     * @throws NotFoundException
     */
    public function findVisibleByIdOrNotFound(int $id): Program
    {
        /**
         * @var Program|null $data
         */
        $data = $this->findOneBy([
            'id' => $id,
            'visible' => true,
            'reportOnly' => false,
        ]);

        if (null === $data) {
            throw new NotFoundException($this->notFoundMessage);
        }

        return $data;
    }
}
