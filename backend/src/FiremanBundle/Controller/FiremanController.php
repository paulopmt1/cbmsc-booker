<?php

namespace App\FiremanBundle\Controller;

use App\FiremanBundle\Dto\SearchFiremanDto;
use App\FiremanBundle\Service\FiremanService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class FiremanController extends AbstractController
{
    public function __construct(
        private readonly FiremanService $firemanService,
        private readonly DenormalizerInterface $denormalizer,
    ) {
    }

    #[Route('/fireman', name: 'fireman-list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        return $this->json($this->firemanService->findAllPaginated(
            $this->denormalizer->denormalize(
                $request->query->all(),
                SearchFiremanDto::class
            )
        ));
    }
}
