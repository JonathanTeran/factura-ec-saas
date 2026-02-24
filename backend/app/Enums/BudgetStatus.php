<?php

namespace App\Enums;

enum BudgetStatus: string
{
    case DRAFT = 'draft';
    case APPROVED = 'approved';
    case ACTIVE = 'active';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Borrador',
            self::APPROVED => 'Aprobado',
            self::ACTIVE => 'Activo',
            self::CLOSED => 'Cerrado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::APPROVED => 'blue',
            self::ACTIVE => 'green',
            self::CLOSED => 'red',
        };
    }
}
