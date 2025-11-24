<x-mail::message :url="$url" :logo="$logo">
# Hello {{ $name }}

Details for the company, {{ $company->name }} have been updated to the following

<x-mail::panel>
@if (array_key_exists('Company Details', $changes) && count($changes['Company Details']) > 0)
  <div class="col-12">
    <h4 class="mb-0">General Details</h4>
    @foreach ($changes['Company Details'] as $key => $details_changes)
      <div>
        <span><strong>{{ Str::title(Str::replace('_', ' ', $key)) }}:</strong></span>
        <span>{{ $details_changes }}</span>
      </div>
    @endforeach
  </div>
  <hr>
@endif
@if (array_key_exists('Relationship Manager', $changes) && count($changes['Relationship Manager']) > 0)
  <div class="col-12">
    <div class="row">
      <h4 class="mb-0">Relationship Manager(s)</h4>
      @foreach ($changes['Relationship Manager'] as $id => $relationship_manager)
          <div class="col-6">
            @foreach ($relationship_manager as $key => $details)
              <div>
                <span><strong>{{ Str::title(Str::replace('_', ' ', $key)) }}:</strong></span>
                <span>{{ $details }}</span>
              </div>
            @endforeach
            <hr>
          </div>
        @endforeach
    </div>
  </div>
  <hr>
@endif
</x-mail::panel>

Login in Below to Approve these changes
<x-mail::button :url="$url">
Login
</x-mail::button>

Regards,<br>
{{ config('app.name') }}
</x-mail::message>
