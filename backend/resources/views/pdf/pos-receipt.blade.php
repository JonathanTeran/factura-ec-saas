<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo POS {{ $transaction->transaction_number }}</title>
    <style>
        @page {
            margin: 5mm;
            size: 80mm auto;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', 'Courier', monospace;
            font-size: 11px;
            color: #000;
            line-height: 1.4;
            width: 70mm;
            max-width: 70mm;
        }

        .receipt {
            width: 100%;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .bold {
            font-weight: bold;
        }

        .separator {
            border-top: 1px dashed #000;
            margin: 4px 0;
        }

        .double-separator {
            border-top: 2px solid #000;
            margin: 4px 0;
        }

        /* Company Header */
        .header {
            text-align: center;
            margin-bottom: 4px;
        }

        .header .company-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .header .company-ruc {
            font-size: 11px;
            margin-bottom: 1px;
        }

        .header .company-address {
            font-size: 9px;
            color: #333;
            margin-bottom: 1px;
        }

        .header .company-phone {
            font-size: 9px;
            color: #333;
        }

        /* Info rows */
        .info-row {
            display: block;
            width: 100%;
            margin-bottom: 1px;
            font-size: 10px;
        }

        .info-row .label {
            font-weight: bold;
        }

        /* Items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2px 0;
        }

        .items-table th {
            font-size: 9px;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding: 2px 0;
            text-align: left;
        }

        .items-table th:nth-child(2) {
            text-align: center;
        }

        .items-table th:nth-child(3),
        .items-table th:nth-child(4) {
            text-align: right;
        }

        .items-table td {
            font-size: 10px;
            padding: 2px 0;
            vertical-align: top;
        }

        .items-table td:nth-child(2) {
            text-align: center;
        }

        .items-table td:nth-child(3),
        .items-table td:nth-child(4) {
            text-align: right;
        }

        .items-table .item-name {
            max-width: 30mm;
            word-wrap: break-word;
        }

        /* Totals */
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2px 0;
        }

        .totals-table td {
            padding: 1px 0;
            font-size: 10px;
        }

        .totals-table .total-label {
            text-align: right;
            padding-right: 4px;
        }

        .totals-table .total-value {
            text-align: right;
            font-weight: bold;
            width: 22mm;
        }

        .totals-table .grand-total td {
            font-size: 13px;
            font-weight: bold;
            padding-top: 3px;
            border-top: 1px solid #000;
        }

        .totals-table .payment-row td {
            font-size: 10px;
            padding-top: 2px;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 6px;
            font-size: 9px;
            color: #333;
        }

        .footer .thanks {
            font-size: 11px;
            font-weight: bold;
            color: #000;
            margin-bottom: 2px;
        }

        .voided-stamp {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            color: #cc0000;
            border: 2px solid #cc0000;
            padding: 4px;
            margin: 4px 0;
        }
    </style>
</head>
<body>
    <div class="receipt">
        {{-- ========== COMPANY HEADER ========== --}}
        <div class="header">
            <div class="company-name">{{ $company->commercial_name ?: $company->business_name }}</div>
            <div class="company-ruc">RUC: {{ $company->ruc }}</div>
            @if($company->address)
                <div class="company-address">{{ $company->address }}</div>
            @endif
            @if($company->phone)
                <div class="company-phone">Tel: {{ $company->phone }}</div>
            @endif
            @if($company->email)
                <div class="company-phone">{{ $company->email }}</div>
            @endif
        </div>

        <div class="double-separator"></div>

        {{-- ========== TRANSACTION INFO ========== --}}
        <div style="margin: 3px 0;">
            <div class="info-row">
                <span class="label">No:</span> {{ $transaction->transaction_number }}
            </div>
            <div class="info-row">
                <span class="label">Fecha:</span> {{ $transaction->created_at->format('d/m/Y') }}
                <span style="margin-left: 4px;"><span class="label">Hora:</span> {{ $transaction->created_at->format('H:i:s') }}</span>
            </div>
            @if($session)
                <div class="info-row">
                    <span class="label">Caja:</span> {{ $session->branch->name ?? '' }} / {{ $session->emissionPoint->code ?? '' }}
                </div>
            @endif
            <div class="info-row">
                <span class="label">Cliente:</span> {{ $transaction->customer->name ?? 'Consumidor Final' }}
            </div>
            @if($transaction->customer && $transaction->customer->identification)
                <div class="info-row">
                    <span class="label">ID:</span> {{ $transaction->customer->identification }}
                </div>
            @endif
        </div>

        <div class="separator"></div>

        {{-- ========== VOIDED STAMP ========== --}}
        @if($transaction->isVoided())
            <div class="voided-stamp">*** ANULADO ***</div>
            <div class="separator"></div>
        @endif

        {{-- ========== ITEMS ========== --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Descripcion</th>
                    <th style="width: 12%;">Cant</th>
                    <th style="width: 24%;">P.Unit</th>
                    <th style="width: 24%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transaction->items as $item)
                    <tr>
                        <td class="item-name">{{ $item->description }}</td>
                        <td>{{ intval($item->quantity) }}</td>
                        <td>{{ number_format($item->unit_price, 2) }}</td>
                        <td>{{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="separator"></div>

        {{-- ========== TOTALS ========== --}}
        <table class="totals-table">
            <tr>
                <td class="total-label">Subtotal:</td>
                <td class="total-value">${{ number_format($transaction->subtotal, 2) }}</td>
            </tr>
            @if($transaction->discount > 0)
                <tr>
                    <td class="total-label">Descuento:</td>
                    <td class="total-value">-${{ number_format($transaction->discount, 2) }}</td>
                </tr>
            @endif
            <tr>
                <td class="total-label">IVA:</td>
                <td class="total-value">${{ number_format($transaction->tax, 2) }}</td>
            </tr>
            <tr class="grand-total">
                <td class="total-label">TOTAL:</td>
                <td class="total-value">${{ number_format($transaction->total, 2) }}</td>
            </tr>
        </table>

        <div class="separator"></div>

        {{-- ========== PAYMENT INFO ========== --}}
        <table class="totals-table">
            <tr class="payment-row">
                <td class="total-label">Forma pago:</td>
                <td class="total-value">
                    @switch($transaction->payment_method)
                        @case('cash') EFECTIVO @break
                        @case('card') TARJETA @break
                        @case('transfer') TRANSFERENCIA @break
                        @default OTRO
                    @endswitch
                </td>
            </tr>
            @if($transaction->payment_method === 'cash')
                <tr class="payment-row">
                    <td class="total-label">Recibido:</td>
                    <td class="total-value">${{ number_format($transaction->amount_received, 2) }}</td>
                </tr>
                @if($transaction->change_amount > 0)
                    <tr class="payment-row">
                        <td class="total-label">Cambio:</td>
                        <td class="total-value">${{ number_format($transaction->change_amount, 2) }}</td>
                    </tr>
                @endif
            @endif
        </table>

        @if($transaction->notes)
            <div class="separator"></div>
            <div style="font-size: 9px; margin: 2px 0;">
                <span class="bold">Nota:</span> {{ $transaction->notes }}
            </div>
        @endif

        <div class="double-separator"></div>

        {{-- ========== FOOTER ========== --}}
        <div class="footer">
            <div class="thanks">Gracias por su compra!</div>
            <div>{{ $company->commercial_name ?: $company->business_name }}</div>
            @if(isset($footerMessage) && $footerMessage)
                <div style="margin-top: 2px;">{{ $footerMessage }}</div>
            @endif
            <div style="margin-top: 4px; font-size: 8px; color: #666;">
                Generado por AmePhia Facturacion
            </div>
        </div>
    </div>
</body>
</html>
