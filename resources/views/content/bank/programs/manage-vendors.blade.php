@extends('layouts/layoutMaster')

@section('title', 'Manage '.Str::title($type))

@section('vendor-style')
@endsection

@section('vendor-script')
@endsection

@section('page-style')
@endsection

@section('page-script')
@endsection

@section('content')
<div class="d-flex justify-content-between">
  <h4 class="fw-bold">
    <span class="fw-light">Manage {{ Str::title($type) }} for <a href="{{ route('programs.show', ['bank' => $bank, 'program' => $program]) }}">{{ $program->name }}</a></span>
  </h4>
  <div>
    @if ($manage)
      @switch($type)
        @case('vendors')
          <a href="{{ route('programs.vendors.map', ['bank' => $bank, 'program' => $program]) }}" class="btn btn-primary btn-sm">Map {{ Str::title($type) }}</a>
          @break
        @case('buyers')
          <a href="{{ route('programs.buyers.map', ['bank' => $bank, 'program' => $program]) }}" class="btn btn-primary btn-sm">Map {{ Str::title($type) }}</a>
          @break
        @case('dealers')
          <a href="{{ route('programs.dealers.map', ['bank' => $bank, 'program' => $program]) }}" class="btn btn-primary btn-sm">Map {{ Str::title($type) }}</a>
          @break
        @default
      @endswitch
    @endif
  </div>
</div>

<div id="manage-vendors">
  <manage-vendors bank={{ request()->route('bank')->url }} program={{ $program->id }} type={{ $type }} can_edit_mapping={{ auth()->user()->hasPermissionTo('Add/Edit Program & Mapping') }} manage={{ $manage }} date_format="{{ request()->route('bank')->adminConfiguration?->date_format }}"></manage-vendors>
</div>
@endsection
