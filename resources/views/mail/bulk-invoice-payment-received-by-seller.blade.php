<x-mail::message :url="''" :logo="$logo">
# Hello Sir/Madam

Payments for the following invoice(s) have been disbursed:

{!! $table_data !!}

Regards,<br>
{{ config('app.name') }}
</x-mail::message>

