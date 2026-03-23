@extends('emails.layouts.zangi-brand')

@section('preheader', $hasAttachment ? 'Your digital book is attached to this email.' : 'Your order details are included in this email.')
@section('eyebrow', $hasAttachment ? 'Digital Delivery' : 'Order Update')
@section('title', $hasAttachment ? 'Your digital book is attached' : 'Your order details are here')

@section('content')
<p style="margin:0 0 16px;font-size:16px;line-height:28px;color:#334155;">
    Hi {{ $recipientName }},
</p>

<p style="margin:0 0 20px;font-size:16px;line-height:28px;color:#334155;">
    @if ($hasAttachment)
        Your digital book is attached to this email together with the order summary below.
    @else
        Here is the latest order summary and fulfillment status for your Zangi purchase.
    @endif
</p>

<div style="margin:0 0 24px;border:1px solid #bfdbfe;border-radius:20px;background-color:#f8fbff;padding:20px;">
    <p style="margin:0 0 14px;font-size:12px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#1d4ed8;">
        Order summary
    </p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="padding:8px 0;font-size:14px;line-height:22px;color:#64748b;">Reference</td>
            <td align="right" style="padding:8px 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ $order->reference }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:14px;line-height:22px;color:#64748b;">Item</td>
            <td align="right" style="padding:8px 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ $order->product_title }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:14px;line-height:22px;color:#64748b;">Format</td>
            <td align="right" style="padding:8px 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ \Illuminate\Support\Str::headline($order->format) }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:14px;line-height:22px;color:#64748b;">Quantity</td>
            <td align="right" style="padding:8px 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ $order->quantity }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:14px;line-height:22px;color:#64748b;">Status</td>
            <td align="right" style="padding:8px 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ $order->status }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:14px;line-height:22px;color:#64748b;">Payment</td>
            <td align="right" style="padding:8px 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ $order->payment_status }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0 0;font-size:14px;line-height:22px;color:#64748b;">Total</td>
            <td align="right" style="padding:8px 0 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">
                {{ strtoupper($order->currency) === 'ZMW' ? 'K ' : '$' }}{{ number_format((float) $order->total, 2) }}
            </td>
        </tr>
    </table>
</div>

@if ($hasAttachment)
<div style="margin:0 0 24px;border-left:4px solid #0f766e;border-radius:0 18px 18px 0;background-color:#ecfeff;padding:16px 18px;">
    <p style="margin:0 0 8px;font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:#0f766e;">
        Attached file
    </p>
    <p style="margin:0;font-size:15px;line-height:26px;color:#155e75;">
        Keep the attached file available for reading or download whenever you need it.
    </p>
</div>
@endif

<p style="margin:0;font-size:14px;line-height:24px;color:#64748b;">
    If you need help with this order, reply to this email and our team will assist.
</p>
@endsection
