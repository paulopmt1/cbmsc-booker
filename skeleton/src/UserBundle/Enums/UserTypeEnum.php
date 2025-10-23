<?php

namespace UserBundle\Enums;

class UserTypeEnum
{
    public const GOOGLE = 'google';
    public const LDAP = 'ldap';

    /**
     * @return string[]
     *
     * @psalm-return list{'google', 'ldap'}
     */
    public static function getValues(): array
    {
        return [
            self::GOOGLE,
            self::LDAP,
        ];
    }

    public static function isValid($value): bool
    {
        return \in_array($value, self::getValues());
    }
}
