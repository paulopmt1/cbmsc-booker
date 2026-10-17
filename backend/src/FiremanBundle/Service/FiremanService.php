<?php

namespace App\FiremanBundle\Service;

use App\FiremanBundle\Dto\SearchFiremanDto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FiremanService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function findAllPaginated(SearchFiremanDto $searchFiremanDto): array
    {
        // implementar
        return [];
    }
}
