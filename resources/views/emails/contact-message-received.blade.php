@extends('emails.layouts.zangi-brand')

@section('preheader', 'We received your message and will follow up soon.')
@section('eyebrow', 'Contact')
@section('title', 'Your message has been received')

@section('content')
<p style="margin:0 0 16px;font-size:16px;line-height:28px;color:#334155;">
    Hi {{ $contactMessage->name }},
</p>

<p style="margin:0 0 20px;font-size:16px;line-height:28px;color:#334155;">
    Thanks for contacting Zangi. Your message is now in the queue for review and follow-up.
</p>

<div style="margin:0 0 24px;border:1px solid #e2e8f0;border-radius:20px;background-color:#f8fafc;padding:20px;">
    <p style="margin:0 0 10px;font-size:12px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#475569;">
        Your message
    </p>
    <p style="margin:0;font-size:15px;line-height:27px;color:#334155;white-space:pre-line;">{{ $contactMessage->message }}</p>
</div>

<div style="margin:0 0 24px;border-left:4px solid #f97316;border-radius:0 18px 18px 0;background-color:#fff7ed;padding:16px 18px;">
    <p style="margin:0 0 6px;font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:#9a3412;">
        Next step
    </p>
    <p style="margin:0;font-size:15px;line-height:26px;color:#7c2d12;">
        Our team will review your message and follow up using this email address.
    </p>
</div>

<p style="margin:0;font-size:14px;line-height:24px;color:#64748b;">
    If you need to add more context, reply to this email and our team can continue from there.
</p>
@endsection
