@extends('layouts/layoutMaster')

@section('title', 'Uploaded Companies')

@section('vendor-style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
@endsection

@section('page-style')
@endsection

@section('vendor-script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
@endsection

@section('page-script')
@endsection

@section('content')
<h4 class="fw-bold">
  <span class="text-muted fw-light">{{ __('Uploaded Companies')}}</span>
</h4>

<div id="uploaded-companies">
  <uploaded-companies bank={{ request()->route('bank')->url }}></uploaded-companies>
</div>
@endsection
