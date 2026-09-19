<?php

namespace App\AppBundle\Repository;

use App\AppBundle\Entity\BaseEntityInterface;
use App\AppBundle\Exception\NotFoundException;
use App\AppBundle\Exception\UniqueException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;

/**
 * @template T of BaseEntityInterface
 *
 * @extends ServiceEntityRepository<T>
 */
abstract class RepositoryService extends ServiceEntityRepository
{
    protected string $notFoundMessage = 'Registro não encontrado';

    /**
     * @throws NotFoundException
     */
    public function throwNotFoundException(?string $customMessage = null): never
    {
        throw new NotFoundException($customMessage ?? $this->notFoundMessage);
    }

    /**
     * @throws NotFoundException
     *
     * @return T
     */
    public function findByIdOrNotFound(?int $id, ?string $customMessage = null)
    {
        $data = $this->find($id);

        if (null === $data) {
            $this->throwNotFoundException($customMessage);
        }

        return $data;
    }

    /**
     * @param array<string,mixed> $criteria
     *
     * @throws NotFoundException
     *
     * @return T
     */
    public function findOneOrNotFound(array $criteria, ?string $customMessage = null)
    {
        $data = $this->findOneBy($criteria);

        if (null === $data) {
            throw new NotFoundException($customMessage ?? $this->notFoundMessage);
        }

        return $data;
    }

    /**
     * @param array<string,mixed> $criteria
     *
     * @throws UniqueException
     *
     * @return void
     */
    public function validateExistsBy(array $criteria, ?string $message = null, ?int $id = null)
    {
        /**
         * @var BaseEntityInterface $data
         */
        $data = $this->findOneBy($criteria);

        if (null != $data && (null === $id || ($id != $data->getId()))) {
            throw new UniqueException($message ?? 'Registro duplicado');
        }
    }

    /**
     * @throws NonUniqueResultException
     * @throws UniqueException
     *
     * @return void
     */
    public function validateExistsByQueryBuilder(
        QueryBuilder $queryBuilder,
        ?string $message = null,
        string|int|null $excludeId = null,
        ?string $internalCode = null,
    ) {
        /**
         * @var BaseEntityInterface[] $result
         */
        $result = $queryBuilder->getQuery()->getResult();

        $data = \array_filter($result, function ($item) use ($excludeId) {
            return $item->getId() !== $excludeId;
        });

        if (\count($data) > 0) {
            throw new UniqueException($message ?? 'Registro duplicado', null, $internalCode);
        }
    }

    /**
     * @param Collection<int, T> $collection
     * @param array<int> $newIds
     */
    public function syncCollection(
        Collection $collection,
        array $newIds,
        callable $addCallback,
        callable $removeCallback,
    ): void {
        $currentIds = \array_map(function ($entity) {
            return $entity->getId();
        }, $collection->toArray());

        $idsToAdd = \array_diff($newIds, $currentIds);
        $idsToRemove = \array_diff($currentIds, $newIds);

        if (!empty($idsToRemove)) {
            foreach ($collection as $entity) {
                if (\in_array($entity->getId(), $idsToRemove, true)) {
                    $removeCallback($entity);
                }
            }
        }

        if (!empty($idsToAdd)) {
            $entities = $this->findBy(['id' => $idsToAdd]);
            foreach ($entities as $entity) {
                $addCallback($entity);
            }
        }
    }

    /**
     * Sincroniza relacionamento many-to-many.
     *
     * @param object $entity Entidade principal
     * @param Collection<int, T>|array<int, mixed> $newItems Novos itens para sincronizar
     * @param string $getterMethod Nome do método getter da collection atual
     * @param string $adderMethod Nome do método para adicionar
     * @param string $removeMethod Nome do método para remover
     * @param string $keyMethod Nome do método para obter a chave única
     */
    public function syncRelationship(
        $entity,
        $newItems,
        string $getterMethod,
        string $adderMethod,
        string $removeMethod,
        string $keyMethod = 'getId',
    ): void {
        if (\is_array($newItems)) {
            $newItems = new ArrayCollection($newItems);
        }

        $newIds = $newItems->map(function ($item) use ($keyMethod) {
            return $item->$keyMethod();
        })->toArray();

        $currentCollection = $entity->$getterMethod();
        $currentIds = $currentCollection->map(function ($item) use ($keyMethod) {
            return $item->$keyMethod();
        })->toArray();

        $idsToAdd = \array_diff($newIds, $currentIds);
        $idsToRemove = \array_diff($currentIds, $newIds);

        foreach ($idsToRemove as $idToRemove) {
            $itemToRemove = $currentCollection->filter(function ($item) use ($idToRemove, $keyMethod) {
                return $item->$keyMethod() === $idToRemove;
            })->first();

            if ($itemToRemove) {
                $entity->$removeMethod($itemToRemove);
            }
        }

        foreach ($idsToAdd as $idToAdd) {
            $itemToAdd = $newItems->filter(function ($item) use ($idToAdd, $keyMethod) {
                return $item->$keyMethod() === $idToAdd;
            })->first();

            if ($itemToAdd) {
                $entity->$adderMethod($itemToAdd);
            }
        }
    }
}
