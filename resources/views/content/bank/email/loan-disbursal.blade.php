<x-mail::message :url="''" :logo="$logo">
{!! $template?->header !!}

{!! $mail_text !!}

{!! $template?->footer !!}
</x-mail::message>
