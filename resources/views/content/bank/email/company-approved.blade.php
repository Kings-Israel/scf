<x-mail::message :url="''" :logo="$logo">
Hello, {{ $name }}

This is to inform you that your company, {{ $company->name }}, has been approved by the bank.

You will receive an email once your account has been completed in setup for you to login.

Regards,<br>
{{ config('app.name') }}
</x-mail::message>
