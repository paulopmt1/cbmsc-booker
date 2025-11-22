<?php

namespace AppBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class ForbiddenException extends HttpException
{
    protected $code = Response::HTTP_FORBIDDEN;

    public function __construct($message = 'Acesso negado.', ?\Throwable $previous = null)
    {
        parent::__construct($message, Response::HTTP_FORBIDDEN, $previous);
    }
}
