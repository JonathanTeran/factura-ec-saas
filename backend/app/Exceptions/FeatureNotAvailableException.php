<?php

namespace App\Exceptions;

use Exception;

class FeatureNotAvailableException extends Exception
{
    public function __construct(
        public readonly string $feature,
        public readonly ?string $requiredPlan = null,
        ?\Throwable $previous = null,
    ) {
        $message = match ($feature) {
            'webhooks' => 'Webhooks no esta disponible en tu plan actual.',
            'recurring_invoices' => 'Facturacion recurrente no esta disponible en tu plan actual.',
            'api_access' => 'Acceso API no esta disponible en tu plan actual.',
            'inventory' => 'Inventario no esta disponible en tu plan actual.',
            'pos' => 'Punto de Venta no esta disponible en tu plan actual.',
            'advanced_reports' => 'Reportes avanzados no estan disponibles en tu plan actual.',
            'ai_categorization' => 'Categorizacion con IA no esta disponible en tu plan actual.',
            'client_portal' => 'Portal de clientes no esta disponible en tu plan actual.',
            'whitelabel_ride' => 'RIDE personalizado no esta disponible en tu plan actual.',
            'multi_currency' => 'Multi-moneda no esta disponible en tu plan actual.',
            default => "La funcionalidad '{$feature}' no esta disponible en tu plan actual.",
        };

        if ($requiredPlan) {
            $message .= " Disponible desde el plan {$requiredPlan}.";
        }

        parent::__construct($message, 403, $previous);
    }

    public function context(): array
    {
        return array_filter([
            'feature' => $this->feature,
            'required_plan' => $this->requiredPlan,
        ]);
    }
}
