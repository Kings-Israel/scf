<x-mail::message :url="''" :logo="$logo">
@if ($template)
{!! $template->header !!}

{!! $mail_text !!}

{!! $template->footer !!}
@else
# Hello Sir/Madam

Program {{ $program->name }} has been updated and is waiting for approval

Regards,<br>
{{ config('app.name') }}
@endif
</x-mail::message>
