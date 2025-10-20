<?php

namespace AppBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class HttpException extends \Exception
{
    protected $code = Response::HTTP_BAD_REQUEST;

    public function getResponse(): mixed
    {
        return $this->getMessage();
    }
}
