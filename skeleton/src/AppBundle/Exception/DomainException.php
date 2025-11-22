<?php

namespace AppBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class DomainException extends HttpException
{
    protected $code = Response::HTTP_BAD_REQUEST;

    /**
     * @param string $message
     */
    public function __construct($message, ?\Throwable $previous = null)
    {
        parent::__construct($message, Response::HTTP_BAD_REQUEST, $previous);
    }
}
