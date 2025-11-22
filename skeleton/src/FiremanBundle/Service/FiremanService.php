<?php

namespace App\FiremanBundle\Service;

use App\FiremanBundle\Dto\CreateFiremanDto;
use AppBundle\Exception\NotFoundException;
use App\FiremanBundle\Entity\FiremanEntity;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class FiremanService
{
    public function create(CreateFiremanDto $createFiremanDto): ?FiremanEntity
    {
        $this->validateFiremanData($createFiremanDto);

        if ($this->firemanRepository->existsByEmail($createFiremanDto->email)) {
            throw new ConflictHttpException('Já existe um bombeiro com esse email.');
        }

        $fireman = $this->firemanRepository->createWithEmail($createFiremanDto->email);

        $fireman
            ->setName($createFiremanDto->name)
            ->setCpf($createFiremanDto->cpf);

        return $this->firemanRepository->save($fireman);
    }

    /**
     * @throws NotFoundException
     */
    public function update(int $id, CreateFiremanDto $createFiremanDto): ?FiremanEntity
    {
        $fireman = $this->firemanRepository->find($id);

        if (null === $fireman) {
            throw new NotFoundException('Bombeiro não encontrado.');
        }

        if ($this->firemanRepository->existsByEmail($createFiremanDto->email, $id)) {
            throw new ConflictHttpException('Já existe um bombeiro com esse email.');
        }


    }
}