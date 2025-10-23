<?php

namespace UserBundle\Enums;

use AppBundle\Enums\Enum;

class ProfileRolePermissionEnum extends Enum
{
    public const READ = 1;
    public const EDIT = 2;
    public const CREATE = 3;
    public const DELETE = 4;

    protected static $values = [
        self::READ => self::READ,
        self::EDIT => self::EDIT,
        self::CREATE => self::CREATE,
        self::DELETE => self::DELETE,
    ];

    protected static $alias = [
        self::READ => 'read',
        self::EDIT => 'edit',
        self::CREATE => 'create',
        self::DELETE => 'delete',
    ];

    protected static $descriptions = [
        self::READ => 'Ler',
        self::EDIT => 'Ler e Editar',
        self::CREATE => 'Ler, Editar e Criar',
        self::DELETE => 'Ler, Editar, Criar e Deletar',
    ];

    public function getName(): string
    {
        return self::class;
    }

    public static function getAlias(): array
    {
        return self::$alias;
    }

    public static function getAliasValue(int $alias): string
    {
        return self::$alias[$alias];
    }

    public static function getValues(): array
    {
        return self::$values;
    }

    public static function getPermissions(int $permission): array
    {
        $permissions = [];

        foreach (self::$alias as $key => $value) {
            if ($key > $permission) {
                break;
            }

            $permissions[] = self::$alias[$key];
        }

        return $permissions;
    }

    public static function getDescription($value): string
    {
        if (!\array_key_exists($value, self::$descriptions)) {
            throw new \InvalidArgumentException("Permissão inválida: {$value}");
        }

        return self::$descriptions[$value];
    }

    public function jsonSerialize(): array
    {
        $data = [];
        foreach (self::$values as $value) {
            $data[] = [
                'id' => $value,
                'name' => self::$descriptions[$value],
            ];
        }

        return $data;
    }
}
