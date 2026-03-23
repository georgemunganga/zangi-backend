@extends('emails.layouts.zangi-brand')

@section('preheader', 'We replied to your recent message.')
@section('eyebrow', 'Support Reply')
@section('title', 'We replied to your message')

@section('content')
<p style="margin:0 0 16px;font-size:16px;line-height:28px;color:#334155;">
    Hi {{ $contactMessage->name }},
</p>

<p style="margin:0 0 20px;font-size:16px;line-height:28px;color:#334155;">
    Our team has sent a reply to your recent message to Zangi.
</p>

<div style="margin:0 0 24px;border-left:4px solid #0f766e;border-radius:0 18px 18px 0;background-color:#ecfeff;padding:18px 20px;">
    <p style="margin:0 0 8px;font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:#115e59;">
        Zangi reply
    </p>
    <p style="margin:0;font-size:15px;line-height:27px;color:#134e4a;white-space:pre-line;">{{ $reply->body }}</p>
</div>

<div style="margin:0 0 24px;border:1px solid #e2e8f0;border-radius:20px;background-color:#f8fafc;padding:20px;">
    <p style="margin:0 0 10px;font-size:12px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:#475569;">
        Your original message
    </p>
    <p style="margin:0;font-size:15px;line-height:27px;color:#334155;white-space:pre-line;">{{ $contactMessage->message }}</p>
</div>

<p style="margin:0;font-size:14px;line-height:24px;color:#64748b;">
    If you need to add more context, reply to this email and our team can continue the conversation.
</p>
@endsection
