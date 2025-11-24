<x-mail::message :url="$url" :logo="$logo">
@if ($template)
{!! $template->header !!}

{!! $mail_text !!}

{!! $template->footer !!}
@endif
</x-mail::message>
