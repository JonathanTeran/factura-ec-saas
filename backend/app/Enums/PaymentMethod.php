<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CREDIT_CARD = 'credit_card';
    case DEBIT_CARD = 'debit_card';
    case BANK_TRANSFER = 'transfer';
    case PAYPHONE = 'payphone';
    case KUSHKI = 'kushki';
    case STRIPE = 'stripe';
    case CASH = 'cash';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CREDIT_CARD => 'Tarjeta de Crédito',
            self::DEBIT_CARD => 'Tarjeta de Débito',
            self::BANK_TRANSFER => 'Transferencia Bancaria',
            self::PAYPHONE => 'PayPhone',
            self::KUSHKI => 'Kushki',
            self::STRIPE => 'Stripe',
            self::CASH => 'Efectivo',
            self::OTHER => 'Otro',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CREDIT_CARD => 'heroicon-o-credit-card',
            self::DEBIT_CARD => 'heroicon-o-credit-card',
            self::BANK_TRANSFER => 'heroicon-o-building-library',
            self::PAYPHONE => 'heroicon-o-device-phone-mobile',
            self::KUSHKI => 'heroicon-o-currency-dollar',
            self::STRIPE => 'heroicon-o-currency-dollar',
            self::CASH => 'heroicon-o-banknotes',
            self::OTHER => 'heroicon-o-ellipsis-horizontal-circle',
        };
    }

    public function requiresGateway(): bool
    {
        return in_array($this, [
            self::CREDIT_CARD,
            self::DEBIT_CARD,
            self::PAYPHONE,
            self::KUSHKI,
            self::STRIPE,
        ]);
    }

    public function isCard(): bool
    {
        return in_array($this, [self::CREDIT_CARD, self::DEBIT_CARD]);
    }

    /**
     * Códigos de forma de pago según el SRI Ecuador.
     */
    public function sriCode(): string
    {
        return match ($this) {
            self::CREDIT_CARD => '19',
            self::DEBIT_CARD => '16',
            self::BANK_TRANSFER => '20',
            self::PAYPHONE => '20',
            self::KUSHKI => '20',
            self::STRIPE => '19',
            self::CASH => '01',
            self::OTHER => '20',
        };
    }
}
