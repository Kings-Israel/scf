<x-mail::message :url="$url" :logo="$logo">
# Hello Sir/Madam

The following documents have been requested for your company approval

<ul class="list-group">
@foreach ($documents as $document)
<li class="list-item">{{ $document }}</li>
@endforeach
</ul>

Click here to proceed to upload <br>
<x-mail::button :url="$url">
Upload Documents Here
</x-mail::button>

Regards,<br>
{{ config('app.name') }}
</x-mail::message>
