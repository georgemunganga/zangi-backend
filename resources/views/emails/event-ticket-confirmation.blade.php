@extends('emails.layouts.zangi-brand')

@php
    $portalUrl = rtrim((string) env('FRONTEND_URL', config('app.url')), '/') . '/portal/login?email=' . urlencode($portalUser->email) . '&ticket=' . $ticketPurchase->id;
@endphp

@section('preheader', 'Your Zangi event ticket is ready in the portal.')
@section('eyebrow', 'Event ticket')
@section('title', 'Your ticket is ready')

@section('content')
<p style="margin:0 0 16px;font-size:16px;line-height:28px;color:#334155;">
    Hi {{ $portalUser->name }},
</p>

<p style="margin:0 0 20px;font-size:16px;line-height:28px;color:#334155;">
    Your event purchase is confirmed and the pass is now linked to your Zangi portal.
</p>

<div style="margin:0 0 24px;border:1px solid #bfdbfe;border-radius:20px;background-color:#f8fbff;padding:20px;">
    <p style="margin:0 0 14px;font-size:12px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#1d4ed8;">
        Ticket summary
    </p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="padding:8px 0;font-size:14px;line-height:22px;color:#64748b;">Reference</td>
            <td align="right" style="padding:8px 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ $ticketPurchase->reference }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:14px;line-height:22px;color:#64748b;">Event</td>
            <td align="right" style="padding:8px 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ $ticketPurchase->event_title }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:14px;line-height:22px;color:#64748b;">Date</td>
            <td align="right" style="padding:8px 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ $ticketPurchase->date_label }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:14px;line-height:22px;color:#64748b;">Time</td>
            <td align="right" style="padding:8px 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ $ticketPurchase->time_label }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:14px;line-height:22px;color:#64748b;">Ticket type</td>
            <td align="right" style="padding:8px 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ $ticketPurchase->ticket_type_label }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:14px;line-height:22px;color:#64748b;">Quantity</td>
            <td align="right" style="padding:8px 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ $ticketPurchase->quantity }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:14px;line-height:22px;color:#64748b;">Ticket code</td>
            <td align="right" style="padding:8px 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ $ticketPurchase->ticket_code ?: 'Generating' }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0 0;font-size:14px;line-height:22px;color:#64748b;">Total</td>
            <td align="right" style="padding:8px 0 0;font-size:16px;line-height:24px;font-weight:800;color:#c2410c;">{{ $currencySymbol }}{{ number_format((float) $ticketPurchase->total, 2) }} {{ $ticketPurchase->currency }}</td>
        </tr>
    </table>
</div>

<div style="margin:0 0 24px;border-left:4px solid #0f766e;border-radius:0 18px 18px 0;background-color:#ecfeff;padding:16px 18px;">
    <p style="margin:0 0 8px;font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:#0f766e;">
        What happens next
    </p>
    <p style="margin:0 0 8px;font-size:15px;line-height:26px;color:#155e75;">
        Open the portal with the same purchase email to access the ticket pass and QR placeholder.
    </p>
    <p style="margin:0;font-size:15px;line-height:26px;color:#155e75;">
        Keep the ticket reference above available if you need event support.
    </p>
</div>

<p style="margin:0 0 24px;font-size:15px;line-height:26px;color:#475569;">
    Your QR placeholder and downloadable pass live in the portal. Use the same purchase email to open the correct account automatically.
</p>

<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
    <tr>
        <td style="border-radius:999px;background-color:#c2410c;">
            <a href="{{ $portalUrl }}" style="display:inline-block;padding:14px 22px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;">
                Open ticket in portal
            </a>
        </td>
    </tr>
</table>

<p style="margin:0;font-size:14px;line-height:24px;color:#64748b;">
    Keep this reference handy if you need help before the launch.
</p>
@endsection
