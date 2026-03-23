@extends('emails.layouts.zangi-brand')

@section('preheader', 'Your Zangi portal verification code is ready.')
@section('eyebrow', 'Portal access')
@section('title', 'Verify your portal access')

@section('content')
@php
    $portalUrl = rtrim((string) env('FRONTEND_URL', config('app.url')), '/') . '/portal/login?email=' . urlencode($portalUser->email);
@endphp

<p style="margin:0 0 16px;font-size:16px;line-height:28px;color:#334155;">
    Hi {{ $portalUser->name }},
</p>

<p style="margin:0 0 20px;font-size:16px;line-height:28px;color:#334155;">
    Use this one-time code to open your Zangi portal. It expires in 10 minutes and works with the same email you used to buy books or tickets.
</p>

<div style="margin:0 0 24px;border-radius:20px;background:linear-gradient(180deg, #fff7ed 0%, #ffedd5 100%);padding:20px 18px;text-align:center;">
    <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#9a3412;">
        Verification code
    </p>
    <p style="margin:0;font-size:34px;line-height:40px;font-weight:800;letter-spacing:0.18em;color:#c2410c;">
        {{ $code }}
    </p>
</div>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
    <tr>
        <td width="50%" style="padding-right:8px;">
            <div style="border:1px solid #e2e8f0;border-radius:18px;background-color:#ffffff;padding:14px 16px;">
                <p style="margin:0 0 6px;font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:#64748b;">
                    Portal email
                </p>
                <p style="margin:0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">
                    {{ $portalUser->email }}
                </p>
            </div>
        </td>
        <td width="50%" style="padding-left:8px;">
            <div style="border:1px solid #e2e8f0;border-radius:18px;background-color:#ffffff;padding:14px 16px;">
                <p style="margin:0 0 6px;font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:#64748b;">
                    Expires in
                </p>
                <p style="margin:0;font-size:14px;line-height:22px;font-weight:700;color:#0f172a;">
                    10 minutes
                </p>
            </div>
        </td>
    </tr>
</table>

<p style="margin:0 0 24px;font-size:15px;line-height:26px;color:#475569;">
    If you just bought a book or ticket, this code will open the purchase-linked portal account automatically.
</p>

<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
    <tr>
        <td style="border-radius:999px;background-color:#c2410c;">
            <a href="{{ $portalUrl }}" style="display:inline-block;padding:14px 22px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;">
                Open portal
            </a>
        </td>
    </tr>
</table>

<p style="margin:0;font-size:14px;line-height:24px;color:#64748b;">
    If you did not request this code, you can ignore this email and no further action is needed.
</p>
@endsection
