<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Zangi')</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f5efe6;
            font-family: 'Poppins', Arial, Helvetica, sans-serif;
            color: #0f172a;
        }

        a {
            color: #c2410c;
        }

        @media only screen and (max-width: 640px) {
            .email-shell {
                width: 100% !important;
            }

            .email-card {
                border-radius: 18px !important;
            }

            .email-body,
            .email-header,
            .email-footer {
                padding-left: 20px !important;
                padding-right: 20px !important;
            }

            .email-title {
                font-size: 28px !important;
                line-height: 34px !important;
            }
        }
    </style>
</head>
@php
    $frontendUrl = rtrim((string) env('FRONTEND_URL', config('app.url')), '/');
    $homeUrl = $frontendUrl !== '' ? $frontendUrl : config('app.url');
    $supportEmail = config('mail.from.address');
    $logoUrl = $homeUrl !== '' ? $homeUrl . '/logo-white-Zangi.png' : null;
@endphp
<body>
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">
        @yield('preheader', 'A Zangi email update')
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f5efe6;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="640" cellspacing="0" cellpadding="0" border="0" class="email-shell" style="width:640px;max-width:640px;">
                    <tr>
                        <td class="email-card" style="overflow:hidden;border-radius:24px;background-color:#ffffff;box-shadow:0 20px 60px rgba(15, 23, 42, 0.12);">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td class="email-header" style="padding:24px 28px;background:linear-gradient(135deg, #0f172a 0%, #0f766e 100%);">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td align="left">
                                                    <a href="{{ $homeUrl }}" style="display:inline-block;text-decoration:none;">
                                                        @if ($logoUrl)
                                                            <img src="{{ $logoUrl }}" alt="Zangi" style="display:block;height:54px;width:auto;max-width:240px;">
                                                        @else
                                                            <span style="display:inline-block;border-radius:999px;background-color:#fff7ed;padding:8px 14px;font-size:12px;font-weight:700;letter-spacing:0.22em;color:#c2410c;">
                                                                ZANGI
                                                            </span>
                                                        @endif
                                                    </a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-top:16px;">
                                                    <p style="margin:0;font-size:12px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:rgba(255,255,255,0.72);">
                                                        @yield('eyebrow', 'Zangi Update')
                                                    </p>
                                                    <p style="margin:8px 0 0;font-size:14px;line-height:24px;color:rgba(255,255,255,0.78);">
                                                        Stories, books, events, and portal access for every Zangi purchase.
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="email-body" style="padding:32px 28px 20px;">
                                        <h1 class="email-title" style="margin:0 0 18px;font-size:34px;line-height:40px;font-weight:800;color:#0f172a;">
                                            @yield('title', 'Zangi')
                                        </h1>

                                        @yield('content')
                                    </td>
                                </tr>

                                <tr>
                                    <td class="email-footer" style="padding:0 28px 28px;">
                                        <div style="border-top:1px solid #e2e8f0;padding-top:20px;">
                                            <p style="margin:0 0 8px;font-size:13px;line-height:22px;color:#475569;">
                                                You're receiving this email because you interacted with Zangi on the site or through the portal.
                                            </p>
                                            <p style="margin:0;font-size:13px;line-height:22px;color:#475569;">
                                                Need help? Email
                                                <a href="mailto:{{ $supportEmail }}" style="color:#c2410c;text-decoration:none;">{{ $supportEmail }}</a>
                                                or visit
                                                <a href="{{ $homeUrl }}" style="color:#c2410c;text-decoration:none;">{{ $homeUrl }}</a>.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
