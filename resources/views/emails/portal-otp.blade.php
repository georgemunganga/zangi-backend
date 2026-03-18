<x-mail::message>
# Verify your Zangi portal login

Hi {{ $portalUser->name }},

Use this one-time code to finish logging in to your Zangi portal:

<x-mail::panel>
## {{ $code }}
</x-mail::panel>

The code expires in 10 minutes.

If you did not request this code, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
