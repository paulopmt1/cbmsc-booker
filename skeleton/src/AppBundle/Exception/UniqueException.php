<?php

namespace AppBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class UniqueException extends HttpException
{
    protected $code = Response::HTTP_BAD_REQUEST;

    public function __construct($message = 'Não possível salvar um registro com informações duplicadas', ?\Throwable $previous = null)
    {
        parent::__construct($message, Response::HTTP_BAD_REQUEST, $previous);
    }
}