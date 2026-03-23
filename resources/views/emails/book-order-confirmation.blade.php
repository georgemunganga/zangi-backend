@extends('emails.layouts.zangi-brand')

@php
    $portalUrl = rtrim((string) env('FRONTEND_URL', config('app.url')), '/') . '/portal/login?email=' . urlencode($portalUser->email) . '&order=' . $order->id;
    $isCod = $order->payment_method === 'cash-on-delivery';
    $paymentLabel = $isCod ? 'Payment on Delivery' : ucfirst(str_replace('-', ' ', $order->payment_method));
    $nextStep = $order->format === 'digital'
        ? 'Your download will appear in the portal as soon as the order is marked ready.'
        : ($isCod
            ? 'Your team can now review and confirm this hardcopy order for delivery follow-up.'
            : 'Your hardcopy order is confirmed and will continue through the fulfillment timeline in the portal.');
@endphp

@section('preheader', 'Your Zangi order has been captured successfully.')
@section('eyebrow', 'Book order')
@section('title', $isCod ? 'Your order has been received' : 'Your order is confirmed')

@section('content')
<p style="margin:0 0 16px;font-size:16px;line-height:28px;color:#334155;">
    Hi {{ $portalUser->name }},
</p>

<p style="margin:0 0 20px;font-size:16px;line-height:28px;color:#334155;">
    {{ $isCod ? 'Your hardcopy order is now in the system and marked for payment on delivery.' : 'Your payment has been confirmed and your order is now linked to your Zangi portal.' }}
</p>

<div style="margin:0 0 24px;border:1px solid #fed7aa;border-radius:20px;background-color:#fffaf5;padding:20px;">
    <p style="margin:0 0 14px;font-size:12px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#9a3412;">
        Order summary
    </p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="padding:8px 0;font-size:14px;line-height:22px;color:#64748b;">Reference</td>
            <td align="right" style="padding:8px 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ $order->reference }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:14px;line-height:22px;color:#64748b;">Book</td>
            <td align="right" style="padding:8px 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ $order->product_title }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:14px;line-height:22px;color:#64748b;">Format</td>
            <td align="right" style="padding:8px 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ ucfirst($order->format) }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:14px;line-height:22px;color:#64748b;">Quantity</td>
            <td align="right" style="padding:8px 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ $order->quantity }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:14px;line-height:22px;color:#64748b;">Payment</td>
            <td align="right" style="padding:8px 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ $paymentLabel }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:14px;line-height:22px;color:#64748b;">Status</td>
            <td align="right" style="padding:8px 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ $order->payment_status }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0 0;font-size:14px;line-height:22px;color:#64748b;">Total</td>
            <td align="right" style="padding:8px 0 0;font-size:16px;line-height:24px;font-weight:800;color:#c2410c;">{{ $currencySymbol }}{{ number_format((float) $order->total, 2) }} {{ $order->currency }}</td>
        </tr>
    </table>
</div>

<div style="margin:0 0 24px;border-left:4px solid #f97316;border-radius:0 18px 18px 0;background-color:#fff7ed;padding:16px 18px;">
    <p style="margin:0 0 6px;font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:#9a3412;">
        What happens next
    </p>
    <p style="margin:0;font-size:15px;line-height:26px;color:#7c2d12;">
        {{ $nextStep }}
    </p>
</div>

<p style="margin:0 0 24px;font-size:15px;line-height:26px;color:#475569;">
    Your portal access code is sent separately. Use the same email address to track this order, its payment state, and any download or fulfillment updates.
</p>

<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
    <tr>
        <td style="border-radius:999px;background-color:#c2410c;">
            <a href="{{ $portalUrl }}" style="display:inline-block;padding:14px 22px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;">
                Open order in portal
            </a>
        </td>
    </tr>
</table>

<p style="margin:0;font-size:14px;line-height:24px;color:#64748b;">
    If anything looks wrong, reply to this email and include the order reference above.
</p>
@endsection
