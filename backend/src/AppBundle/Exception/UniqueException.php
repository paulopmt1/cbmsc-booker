<?php

namespace App\AppBundle\Exception;

class UniqueException extends \DomainException
{
    public function __construct(
        string $message = 'Registro duplicado',
        ?\Throwable $previous = null,
        private readonly ?string $internalCode = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getInternalCode(): ?string
    {
        return $this->internalCode;
    }
}
