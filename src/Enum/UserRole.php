<?php
namespace App\Enum;

enum UserRole: string
{
    case ROLE_USER = 'ROLE_USER';
    case ROLE_CONDUCTEUR = 'ROLE_CONDUCTEUR';
    case ROLE_ADMIN = 'ROLE_ADMIN';
}
