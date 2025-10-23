<?php

namespace UserBundle\Dto;

use Symfony\Component\Validator\Constraints as Assert;

trait UserPublicFieldsTrait
{
    /**
     * @Assert\Length(
     *     max=60,
     *     maxMessage="O nome não pode ter mais que {{ limit }} caracteres"
     * )
     */
    public string $firstName;

    /**
     * @Assert\Length(
     *     max=60,
     *     maxMessage="O sobrenome não pode ter mais que {{ limit }} caracteres"
     * )
     */
    public string $lastName;
}
