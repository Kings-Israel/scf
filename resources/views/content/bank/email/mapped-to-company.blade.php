<x-mail::message :url="$link" :logo="$logo">
@if ($template)

{!! $template->header !!}

{!! $mail_text !!}

{!! $template->footer !!}

@else
# Hello, {{ $content['data']['name'] }}

@if ($content['type'] == 'Company')
This is to inform you that your company, {{ $content['data']['company'] }} is now active.

You can reset your password or login through the link below to start your journey.

<x-mail::button :url="$link">
Set Password
</x-mail::button>
@else
This is to inform you that your account for the bank, {{ $content['data']['bank'] }} is now active.

You can reset your password and login through the link below to start your journey.

<x-mail::button :url="$link">
Set New Password
</x-mail::button>
@endif
Regards<br>
{{ config('app.name') }}
@endif
</x-mail::message>
