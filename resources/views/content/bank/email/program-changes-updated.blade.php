<x-mail::message :url="$link" :logo="$logo">
# Hello Sir/Madam

New changes proposed by {{ $user }} were {{ $status == 'approve' ? 'approved' : 'rejected' }}

Click below to view program details
<x-mail::button :url="$link">
Program Details
</x-mail::button>

Regards,<br>
{{ config('app.name') }}
</x-mail::message>
