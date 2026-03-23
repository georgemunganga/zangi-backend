@extends('emails.layouts.zangi-brand')

@section('preheader', 'Your ticket PDF is attached to this email.')
@section('eyebrow', 'Ticket Delivery')
@section('title', 'Your ticket is attached')

@section('content')
<p style="margin:0 0 16px;font-size:16px;line-height:28px;color:#334155;">
    Hi {{ $recipientName }},
</p>

<p style="margin:0 0 20px;font-size:16px;line-height:28px;color:#334155;">
    Your Zangi ticket has been sent to this email address. The PDF pass is attached for quick download and presentation.
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
            <td style="padding:8px 0;font-size:14px;line-height:22px;color:#64748b;">Ticket code</td>
            <td align="right" style="padding:8px 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ $ticketPurchase->ticket_code ?: 'Pending' }}</td>
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
            <td style="padding:8px 0;font-size:14px;line-height:22px;color:#64748b;">Venue</td>
            <td align="right" style="padding:8px 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ $ticketPurchase->location_label }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0 0;font-size:14px;line-height:22px;color:#64748b;">Ticket type</td>
            <td align="right" style="padding:8px 0 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ $ticketPurchase->ticket_type_label }}</td>
        </tr>
    </table>
</div>

<div style="margin:0 0 24px;border-left:4px solid #0f766e;border-radius:0 18px 18px 0;background-color:#ecfeff;padding:16px 18px;">
    <p style="margin:0 0 8px;font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:#0f766e;">
        Attached pass
    </p>
    <p style="margin:0;font-size:15px;line-height:26px;color:#155e75;">
        Keep the attached PDF available on your phone or print it before arriving at the event.
    </p>
</div>

<p style="margin:0;font-size:14px;line-height:24px;color:#64748b;">
    If you need more help before the event, reply to this email and our team will assist.
</p>
@endsection
