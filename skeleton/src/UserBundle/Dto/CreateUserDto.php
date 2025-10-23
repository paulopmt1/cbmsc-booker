<?php

namespace UserBundle\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateUserDto
{
    use UserPublicFieldsTrait;

    /**
     * @Assert\Length(
     *     max=100,
     *     maxMessage="O email não pode ter mais que {{ limit }} caracteres"
     * )
     *
     * @Assert\Email(
     *      message = "O email '{{ value }}' não é válido."
     *  )
     */
    public string $email;

    /**
     * @Assert\NotBlank(message="O perfil é obrigatório")
     *
     * @Assert\Type(type="integer", message="O perfil deve ser um número inteiro")
     */
    public int $profileId;

    /**
     * @Assert\Type(type="integer", message="O programa deve ser um número inteiro")
     */
    public ?int $programId = null;

    /**
     * @var int[]
     *
     * @Assert\Type(type="array", message="Os tipos de usuário devem ser uma lista")
     *
     * @Assert\All({
     *
     *     @Assert\Type(type="integer", message="Cada item de tipos de usuário deve ser um número inteiro"),
     * })
     */
    public array $userTypes = [];
}
