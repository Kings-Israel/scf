<x-mail::message :url="''" :logo="$logo">
# Hello Sir/Madam

Payments for the following invoice(s) have been received and the invoice(s) closed:

{!! $table_data !!}

Refer to your Closed Invoices Report for more details.

Regards,<br>
{{ config('app.name') }}
</x-mail::message>
