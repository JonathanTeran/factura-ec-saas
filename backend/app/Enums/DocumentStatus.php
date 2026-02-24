<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case DRAFT = 'draft';
    case PROCESSING = 'processing';
    case SIGNED = 'signed';
    case SENT = 'sent';
    case AUTHORIZED = 'authorized';
    case REJECTED = 'rejected';
    case FAILED = 'failed';
    case VOIDED = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Borrador',
            self::PROCESSING => 'Procesando',
            self::SIGNED => 'Firmado',
            self::SENT => 'Enviado al SRI',
            self::AUTHORIZED => 'Autorizado',
            self::REJECTED => 'Rechazado',
            self::FAILED => 'Error',
            self::VOIDED => 'Anulado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PROCESSING => 'blue',
            self::SIGNED => 'indigo',
            self::SENT => 'yellow',
            self::AUTHORIZED => 'green',
            self::REJECTED => 'red',
            self::FAILED => 'orange',
            self::VOIDED => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'heroicon-o-pencil',
            self::PROCESSING => 'heroicon-o-arrow-path',
            self::SIGNED => 'heroicon-o-key',
            self::SENT => 'heroicon-o-paper-airplane',
            self::AUTHORIZED => 'heroicon-o-check-circle',
            self::REJECTED => 'heroicon-o-x-circle',
            self::FAILED => 'heroicon-o-exclamation-triangle',
            self::VOIDED => 'heroicon-o-no-symbol',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::AUTHORIZED, self::REJECTED, self::VOIDED]);
    }
}
