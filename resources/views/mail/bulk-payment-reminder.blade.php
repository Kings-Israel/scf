<x-mail::message :url="''" :logo="$logo">
# Hello Sir/Madam

The following invoice are due today:

{!! $table_data !!}

Regards,<br>
{{ config('app.name') }}
</x-mail::message>
