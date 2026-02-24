<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Pago {{ $payment->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; line-height: 1.5; }
        .container { max-width: 700px; margin: 0 auto; padding: 30px; }
        .header { display: flex; justify-content: space-between; border-bottom: 3px solid #2563eb; padding-bottom: 20px; margin-bottom: 20px; }
        .logo-section { width: 60%; }
        .logo-section h1 { font-size: 22px; color: #2563eb; margin-bottom: 5px; }
        .logo-section p { font-size: 11px; color: #666; }
        .invoice-info { width: 35%; text-align: right; }
        .invoice-info h2 { font-size: 16px; color: #2563eb; text-transform: uppercase; margin-bottom: 8px; }
        .invoice-info .number { font-size: 14px; font-weight: bold; color: #333; }
        .invoice-info .date { font-size: 11px; color: #666; margin-top: 4px; }
        .details-row { display: flex; justify-content: space-between; margin-bottom: 25px; }
        .detail-box { width: 48%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; }
        .detail-box h3 { font-size: 11px; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 8px; }
        .detail-box p { margin-bottom: 3px; }
        .detail-box .name { font-weight: bold; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead th { background: #2563eb; color: white; padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        tbody td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; }
        tbody tr:last-child td { border-bottom: none; }
        .totals { width: 280px; margin-left: auto; margin-bottom: 25px; }
        .totals table { margin-bottom: 0; }
        .totals td { padding: 6px 12px; }
        .totals .label { text-align: right; color: #64748b; font-size: 11px; }
        .totals .value { text-align: right; font-weight: bold; }
        .totals .total-row td { border-top: 2px solid #2563eb; font-size: 14px; color: #2563eb; padding-top: 10px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef9c3; color: #854d0e; }
        .status-failed { background: #fecaca; color: #991b1b; }
        .status-refunded { background: #e9d5ff; color: #6b21a8; }
        .payment-info { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 6px; padding: 15px; margin-bottom: 20px; }
        .payment-info h3 { font-size: 12px; color: #0369a1; margin-bottom: 8px; }
        .payment-info .row { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .payment-info .label { color: #64748b; }
        .footer { border-top: 1px solid #e2e8f0; padding-top: 15px; text-align: center; color: #94a3b8; font-size: 10px; }
        .footer p { margin-bottom: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <table style="width: 100%; margin-bottom: 20px; border-bottom: 3px solid #2563eb; padding-bottom: 20px;">
            <tr>
                <td style="width: 60%; vertical-align: top; border: none;">
                    <h1 style="font-size: 22px; color: #0d9488; margin-bottom: 5px;">AmePhia</h1>
                    <p style="font-size: 11px; color: #666;">Plataforma de Facturacion Electronica</p>
                    <p style="font-size: 11px; color: #666;">Ecuador</p>
                </td>
                <td style="width: 40%; text-align: right; vertical-align: top; border: none;">
                    <h2 style="font-size: 16px; color: #2563eb; text-transform: uppercase; margin-bottom: 8px;">Comprobante de Pago</h2>
                    <p style="font-size: 14px; font-weight: bold;">{{ $payment->invoice_number }}</p>
                    <p style="font-size: 11px; color: #666; margin-top: 4px;">Fecha: {{ $payment->paid_at?->format('d/m/Y') ?? $payment->created_at->format('d/m/Y') }}</p>
                    <p style="margin-top: 6px;">
                        <span class="status-badge status-{{ $payment->status->value }}">
                            {{ $payment->status->label() }}
                        </span>
                    </p>
                </td>
            </tr>
        </table>

        <table style="width: 100%; margin-bottom: 25px;">
            <tr>
                <td style="width: 48%; vertical-align: top; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px;">
                    <p style="font-size: 11px; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 8px; font-weight: bold;">Datos del Cliente</p>
                    <p style="font-weight: bold; font-size: 13px;">{{ $payment->billing_name }}</p>
                    @if($payment->billing_identification)
                        <p>{{ $payment->billing_identification }}</p>
                    @endif
                    <p>{{ $payment->billing_email }}</p>
                    @if($payment->billing_phone)
                        <p>{{ $payment->billing_phone }}</p>
                    @endif
                    @if($payment->billing_address)
                        <p>{{ $payment->billing_address }}</p>
                    @endif
                </td>
                <td style="width: 4%; border: none;">&nbsp;</td>
                <td style="width: 48%; vertical-align: top; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px;">
                    <p style="font-size: 11px; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 8px; font-weight: bold;">Detalles del Pago</p>
                    <p><strong>Transaccion:</strong> {{ $payment->transaction_id }}</p>
                    <p><strong>Metodo:</strong> {{ $payment->payment_method->label() }}</p>
                    <p><strong>Gateway:</strong> {{ ucfirst($payment->gateway ?? 'N/A') }}</p>
                    @if($payment->gateway_payment_id)
                        <p><strong>ID Gateway:</strong> {{ $payment->gateway_payment_id }}</p>
                    @endif
                </td>
            </tr>
        </table>

        <table>
            <thead>
                <tr>
                    <th style="width: 60%;">Concepto</th>
                    <th style="width: 15%; text-align: center;">Periodo</th>
                    <th style="width: 25%; text-align: right;">Monto</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>{{ $payment->description ?? 'Suscripcion' }}</strong>
                        @if($payment->subscription?->plan)
                            <br><span style="font-size: 11px; color: #666;">Plan {{ $payment->subscription->plan->name }}</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        {{ $payment->subscription?->billing_cycle === 'yearly' ? 'Anual' : 'Mensual' }}
                    </td>
                    <td style="text-align: right;">${{ number_format($payment->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr>
                    <td class="label">Subtotal:</td>
                    <td class="value">${{ number_format($payment->amount, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">IVA:</td>
                    <td class="value">${{ number_format($payment->tax_amount, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td class="label" style="font-size: 14px;"><strong>Total:</strong></td>
                    <td class="value" style="font-size: 14px;">${{ number_format($payment->total_amount, 2) }} {{ $payment->currency }}</td>
                </tr>
                @if($payment->refund_amount)
                    <tr>
                        <td class="label" style="color: #991b1b;">Reembolsado:</td>
                        <td class="value" style="color: #991b1b;">-${{ number_format($payment->refund_amount, 2) }}</td>
                    </tr>
                @endif
            </table>
        </div>

        @if($payment->transfer_reference)
            <div class="payment-info">
                <h3>Informacion de Transferencia</h3>
                <p><strong>Referencia:</strong> {{ $payment->transfer_reference }}</p>
                @if($payment->approved_at)
                    <p><strong>Aprobado:</strong> {{ $payment->approved_at->format('d/m/Y H:i') }}</p>
                @endif
            </div>
        @endif

        <div class="footer">
            <p>Este documento es un comprobante de pago generado automaticamente.</p>
            <p>AmePhia - Plataforma de Facturacion Electronica para Ecuador</p>
            <p>Generado el {{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</body>
</html>
