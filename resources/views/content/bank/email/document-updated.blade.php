<x-mail::message :url="$url" :logo="$logo">
Hello, {{ $user_name }}

@if ($document)
  The document {{ $document->original_name }} was <span style="text-decoration: underline">{{ Str::title($status) }}</span> by the bank.
@else
  The document {{ $company_document->name }} was <span style="text-decoration: underline">{{ Str::title($status) }}</span> by the bank.
@endif

@if ($status == 'rejected')
<h6>Rejected Reason:</h6>
<x-mail::panel>
  {{ $document ? $document->rejected_reason : $company_document->rejected_reason }}
</x-mail::panel>
<br>
<x-mail::button :url="$url">
Kindly re-upload this document here
</x-mail::button>
@endif

Regards,<br>
{{ config('app.name') }}
</x-mail::message>
