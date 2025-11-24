<x-mail::message :url="$url" :logo="$logo">
@if ($template)
{!! $template->header !!}

{!! $mail_text !!}

Login below to view the details.
<x-mail::button :url="$url">
Login
</x-mail::button>

{!! $template->footer !!}
@else
# Hello {{ $name }}

A CBS Transaction for the invoice, {{ $invoice->invoice_number }} failed

Login below to view the details.
<x-mail::button :url="$url">
Login
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
@endif
</x-mail::message>
