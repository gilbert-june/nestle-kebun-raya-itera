<?php

namespace App\Enums;

abstract class RoleEnum 
{
    const ADMIN = 'ADMIN';
    const PENGELOLA = 'PENGELOLA';
    const PENELITI = 'PENELITI';
    const PENGUNJUNG = 'PENGUNJUNG';

    const ALLOWED_ROLES = [
        self::ADMIN,
        self::PENGELOLA,
        self::PENELITI,
        self::PENGUNJUNG,
    ];
}
