<x-mail::message :url="''" :logo="$logo">
@if ($template)
{!! $template->header !!}

{!! $mail_text !!}

{!! $template->footer !!}
@else
# Hello Sir/Madam

Interested has been posted for payment of the invoice, {{ $invoice->invoice_number }}
with the details as below.

<x-mail::panel>
  Invoice Number: {{ $invoice->invoice_number }}<br/>
  Loan Amount: {{ $data->symbol }}{{ number_format($data->loan_amount) }}<br/>
  Interest Amount: {{ $data->symbol }}{{ number_format($data->amount) }}<br/>
  Due Date: {{ $data->due_date }}
</x-mail::panel>

Regards,<br>
{{ config('app.name') }}
@endif
</x-mail::message>
