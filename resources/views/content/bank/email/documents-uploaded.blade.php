<x-mail::message :url="''" :logo="$logo">
# Hello Sir/Madam

The following documents that are required by the bank, {{ $company->bank->name }} for the company {{ $company->name }} have been uploaded

<x-mail::panel>
@foreach ($documents as $document)
  {{ $document }}<br>
@endforeach
</x-mail::panel>

Regards,<br>
{{ config('app.name') }}
</x-mail::message>
