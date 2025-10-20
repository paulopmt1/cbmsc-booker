<?php

namespace AppBundle\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends HttpException
{
    protected $code = Response::HTTP_UNPROCESSABLE_ENTITY;

    /**
     * @var ConstraintViolationListInterface
     */
    private $errors;

    private $messages = [
        'NotBlank' => 'Campo obrigatório.',
        'LengthMin' => 'O campo precisa ter pelo menos %s caracteres.',
        'LengthMax' => 'O campo precisa ter no maximo %s caracteres.',
        'LengthExact' => 'O campo precisa ter exatamente %s caracteres.',
        'LengthCharset' => 'O campo tem um conjunto de caracteres inválido.',
    ];

    /**
     * @var \Throwable|null
     */
    private $previous;

    public function __construct(
        ConstraintViolationListInterface $errors,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($this->messages['NotBlank'], $this->code, $previous);
        $this->previous = $previous;
        $this->errors = $errors;
    }

    /**
     * @throws \ReflectionException
     */
    public function getResponse(): \stdClass
    {
        $messages = new \stdClass();

        /**
         * @var ConstraintViolation $error
         */
        foreach ($this->errors as $error) {
            $constraint = $error->getConstraint();
            $reflect = new \ReflectionClass($constraint);
            $message = $error->getMessage();
            $className = $reflect->getShortName();

            if (isset($this->messages[$className])) {
                $message = $this->messages[$className];
            }

            if ($constraint instanceof Length) {
                if (\strstr($message, 'short')) {
                    $message = \sprintf($this->messages['LengthMin'], $constraint->min);
                }

                if (\strstr($message, 'long')) {
                    $message = \sprintf($this->messages['LengthMax'], $constraint->max);
                }

                if (\strstr($message, 'exactly')) {
                    $message = \sprintf($this->messages['LengthExact'], $constraint->min);
                }

                if (\strstr($message, 'not match the expected')) {
                    $message = \sprintf($this->messages['LengthCharset'], $constraint->min);
                }
            }

            $messages->{$error->getPropertyPath()} = $message;
        }

        return $messages;
    }
}
