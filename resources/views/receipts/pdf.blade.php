<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; text-align: center; }
        .header { margin-bottom: 16px; }
        .logo { max-height: 64px; max-width: 180px; margin: 0 auto 8px; display: block; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .tagline { color: #444; font-size: 11px; margin: 0; }
        .meta { color: #666; font-size: 10px; margin: 2px 0; }
        .details { text-align: left; margin-top: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        td { padding: 4px 0; border-bottom: 1px solid #eee; }
        .totals { text-align: left; margin-top: 12px; }
        .total { font-weight: bold; font-size: 14px; }
        .footer { color: #666; font-size: 10px; margin-top: 16px; }
    </style>
</head>
<body>
    <div class="header">
        @if(!empty($data['logo_path']))
            <img src="{{ $data['logo_path'] }}" class="logo" alt="{{ $data['name'] ?? '' }}">
        @endif
        <h1>{{ $data['name'] ?? 'Restaurant' }}</h1>
        @if(!empty($data['tagline']))
            <p class="tagline">{{ $data['tagline'] }}</p>
        @endif
        @if(!empty($data['address_line']))
            <p class="meta">{{ $data['address_line'] }}</p>
        @endif
        @if(!empty($data['phone']) || !empty($data['email']))
            <p class="meta">
                @if(!empty($data['phone'])){{ $data['phone'] }}@endif
                @if(!empty($data['phone']) && !empty($data['email'])) · @endif
                @if(!empty($data['email'])){{ $data['email'] }}@endif
            </p>
        @endif
        @if(!empty($data['tax_id']))
            <p class="meta">TPIN: {{ $data['tax_id'] }}</p>
        @endif
    </div>

    <div class="details">
        <p class="meta">Receipt {{ $receipt->receipt_number }}</p>
        <p class="meta">Order: {{ $data['order_number'] ?? '' }} · Table {{ $data['table'] ?? '—' }}</p>
        <p class="meta">Waiter: {{ $data['waiter'] ?? '—' }}</p>
    </div>

    <table>
        @foreach($data['items'] ?? [] as $item)
            <tr>
                <td>{{ $item['qty'] }}× {{ $item['name'] }}</td>
                <td align="right">{{ money_major($item['total']) }}</td>
            </tr>
        @endforeach
    </table>

    <div class="totals">
        <p>Subtotal: {{ money_major($data['subtotal'] ?? 0) }}</p>
        <p>{{ $data['tax_label'] ?? 'Tax' }}: {{ money_major($data['tax'] ?? 0) }}</p>
        @if(($data['discount'] ?? 0) > 0)
            <p>{{ $data['discount_label'] ?? 'Discount' }}: -{{ money_major($data['discount']) }}</p>
        @endif
        <p class="total">Total: {{ money_major($data['total'] ?? 0) }}</p>
    </div>

    <p class="footer">Paid via {{ $data['payment_method'] ?? '' }} · {{ $data['paid_at'] ?? '' }}</p>
    <p class="footer">Thank you for dining with us</p>
</body>
</html>
