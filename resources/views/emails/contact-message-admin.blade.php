@extends('emails.layouts.zangi-brand')

@section('preheader', 'A new website contact message has arrived.')
@section('eyebrow', 'Admin alert')
@section('title', 'New contact message')

@section('content')
<p style="margin:0 0 20px;font-size:16px;line-height:28px;color:#334155;">
    A new message was submitted through the Zangi contact form.
</p>

<div style="margin:0 0 24px;border:1px solid #e2e8f0;border-radius:20px;background-color:#f8fafc;padding:20px;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="padding:8px 0;font-size:14px;line-height:22px;color:#64748b;">Name</td>
            <td align="right" style="padding:8px 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ $contactMessage->name }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:14px;line-height:22px;color:#64748b;">Email</td>
            <td align="right" style="padding:8px 0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">{{ $contactMessage->email }}</td>
        </tr>
        <tr>
            <td colspan="2" style="padding-top:14px;">
                <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#475569;">
                    Message
                </p>
                <p style="margin:0;font-size:15px;line-height:27px;color:#334155;white-space:pre-line;">{{ $contactMessage->message }}</p>
            </td>
        </tr>
    </table>
</div>

<p style="margin:0;font-size:14px;line-height:24px;color:#64748b;">
    Reply directly to this email to continue with {{ $contactMessage->name }}.
</p>

<table role="presentation" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <td style="border-radius:999px;background-color:#c2410c;">
            <a href="mailto:{{ $contactMessage->email }}" style="display:inline-block;padding:14px 22px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;">
                Reply to {{ $contactMessage->name }}
            </a>
        </td>
    </tr>
</table>
@endsection
