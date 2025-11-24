<x-mail::message :url="$link" :logo="$logo">
@if ($template)
{!! $template->header !!}

{!! $mail_text !!}

{!! $template->footer !!}
@else
# Hello {{ $user_name }}

You made a password reset request for YOFINVOICE BANK.

To reset your password, click the link below.

<x-mail::button :url="$link">
Set your Password
</x-mail::button>
@endif

</x-mail::message>
