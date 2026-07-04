<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super-admin';
    case BranchAdmin = 'admin-cabang';

    /**
     * @return list<string>
     */
    public static function webAdminRoles(): array
    {
        return array_map(
            static fn (self $role): string => $role->value,
            self::cases(),
        );
    }
}
