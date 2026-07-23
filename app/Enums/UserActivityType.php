<?php

namespace App\Enums;

enum UserActivityType: string
{
    case Login = 'Login';
    case Logout = 'Logout';
    case UserCreated = 'User Created';
    case UserUpdated = 'User Updated';
    case UserDeleted = 'User Deleted';
    case ProfileUpdated = 'Profile Updated';
    case PasswordChanged = 'Password Changed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
