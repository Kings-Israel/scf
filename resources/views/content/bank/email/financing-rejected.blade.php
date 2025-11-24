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

An updated has been made for the financing request, {{ $financing_request->reference_number }}

<x-mail::panel>
  Invoice Number: {{ $financing_request->invoice->invoice_number }}<br/>
  Status: {{ $status }}<br/>
  Request Amount: {{ number_format($financing_request->amount) }}<br/>
</x-mail::panel>

Login below to view the details.
<x-mail::button :url="$url">
Login
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
@endif
</x-mail::message>
