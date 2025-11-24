<x-mail::message :url="$link" :logo="$logo">
# Hello Sir/Madam

Configurations for invoices or purchase orders are awaiting approval for the company, {{ $company->name }}.

<x-mail::button :url="$link">
Login to View
</x-mail::button>

Regards,<br>
{{ config('app.name') }}
</x-mail::message>
