<?php

namespace AppBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class NotFoundException extends HttpException
{
    protected $code = Response::HTTP_NOT_FOUND;

    public function __construct($message = 'Registro não encontrado', ?\Throwable $previous = null)
    {
        parent::__construct($message, Response::HTTP_NOT_FOUND, $previous);
    }
}
