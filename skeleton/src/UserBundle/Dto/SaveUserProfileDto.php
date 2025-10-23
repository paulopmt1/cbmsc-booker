<?php

namespace UserBundle\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class SaveUserProfileDto
{
    #[Assert\NotBlank(message: 'O nome do perfil é obrigatório')]
    public string $name;

    /**
     * @var array<int, array{feature: int, action: int}>
     */
    #[Assert\NotBlank(message: 'Informe as permissões')]
    #[Assert\Count(min: 1, minMessage: 'Selecione pelo menos uma permissão')]
    #[Assert\All(
        new Assert\Collection([
            'fields' => [
                'feature' => [
                    new Assert\NotNull(message: 'O campo "feature" é obrigatório'),
                    new Assert\Type(type: 'integer', message: 'O campo "feature" deve ser um número inteiro'),
                ],
                'action' => [
                    new Assert\NotNull(message: 'O campo "action" é obrigatório'),
                    new Assert\Type(type: 'integer', message: 'O campo "action" deve ser um número inteiro'),
                ],
            ],
            'allowExtraFields' => false,
            'allowMissingFields' => false,
        ])
    )]
    public array $roles = [];

    /**
     * @var int[]
     */
    #[Assert\Type(type: 'array', message: 'As categorias devem ser uma lista')]
    #[Assert\All(
        new Assert\Type(type: 'integer', message: 'Cada item de categoria deve ser um número inteiro')
    )]
    public array $allowedReportingCategoryIds = [];

    /**
     * @var int[]
     */
    #[Assert\Type(type: 'array', message: 'Os relatórios devem ser uma lista')]
    #[Assert\All(
        new Assert\Type(type: 'integer', message: 'Cada item de relatórios deve ser um número inteiro')
    )]
    public array $allowedReportIds = [];

    #[Assert\NotNull(message: 'O campo filtrar financiadores do SOCIOBIO é obrigatório')]
    #[Assert\Type(type: 'bool', message: 'O campo de filtrar financiadores do SOCIOBIO deve ser verdadeiro ou falso')]
    public bool $filterSociobioSponsors;

    #[Assert\NotNull(message: 'O campo filtrar financiadores do programa vinculado é obrigatório')]
    #[Assert\Type(type: 'bool', message: 'O campo de filtrar financiadores do programa vinculado deve ser verdadeiro ou falso')]
    public bool $filterProgramSponsors;
}
