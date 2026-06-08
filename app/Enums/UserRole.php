<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case Vet = 'vet';
    case Admin = 'admin';

    /**
     * Human-readable label for UI consumption.
     */
    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Pet Owner',
            self::Vet => 'Veterinarian',
            self::Admin => 'Administrator',
        };
    }
}