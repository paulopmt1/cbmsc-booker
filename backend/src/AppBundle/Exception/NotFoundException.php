<?php

namespace App\AppBundle\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NotFoundException extends NotFoundHttpException
{
    public function __construct(string $message = 'Registro não encontrado', ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}
