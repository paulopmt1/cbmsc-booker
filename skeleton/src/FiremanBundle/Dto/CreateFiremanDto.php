<?php

namespace App\FiremanBundle\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateFiremanDto
{
    #[Assert\NotBlank(message: 'O nome do bombeiro é obrigatório')]
    #[Assert\type(type: 'string', message: 'O nome do bombeiro deve ser um teste válido')]
    #[Assert\Length(max: 100, maxMessage: 'O nome do bombeiro não pode ultrapassar {{ limit }} caracteres')]
    public string $name;

    #[Assert\NotBlank(message: 'O CPF é obrigatório')]
    #[Assert\Type(type: 'string', message: 'O CPF deve ser válido')]
    public string $cpf; // Melhor logo na próxima vez que mexer aqui, escrever a validação para o CPF...


}