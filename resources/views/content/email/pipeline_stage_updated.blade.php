
<x-mail::message :url="''" :logo="$logo">
  <x-mail::panel>
    <h1>Pipeline Stage Updated</h1>
    <p>The stage for the pipeline <strong>{{ $pipeline->name }}</strong> has been updated to <strong>{{ $newStage }}</strong>.</p>
  </x-mail::panel>
  <x-mail::panel>
    <h2>Details</h2>
    <ul>
      <li>Name: {{ $pipeline->name }}</li>
      <li>Company: {{ $pipeline->company }}</li>
      <li>Stage: {{ $newStage }}</li>
      <li>Updated At: {{ now() }}</li>
    </ul>
  </x-mail::panel>
</x-mail::message>
