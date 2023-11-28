<?php

declare(strict_types=1);

namespace Models\User;

class UserFactory
{
    public static function createEmptyUser(): User
    {
        return new User(
            -1,
            '0',
            '',
            '',
            '',
            false,
            null,
            null,
            null,
            null,
            null,
            null,
            false,
            -1,
            0,
            0
        );
    }
}
