@extends('layouts/vendorLayoutMaster')

@section('title', 'Configurations')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/swiper/swiper.css')}}" />
{{-- <link rel="stylesheet" href="{{asset('assets/vendor/libs/quill/typography.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/quill/katex.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/quill/editor.css')}}" /> --}}
@endsection

@section('page-style')
<!-- Page -->
<link rel="stylesheet" href="{{asset('assets/vendor/css/pages/cards-advance.css')}}">
<style>
  .table-responsive .dropdown,
  .table-responsive .btn-group,
  .table-responsive .btn-group-vertical {
      position: static;
  }
  .tab-content {
    padding: 0px !important;
  }
  .no-label {
    margin-left: -65px !important;
  }
  .yes-label {
    margin-left: 40px !important;
  }
</style>
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/swiper/swiper.js')}}"></script>
{{-- <script src="{{asset('assets/vendor/libs/quill/katex.js')}}"></script>
<script src="{{asset('assets/vendor/libs/quill/quill.js')}}"></script> --}}
@endsection

@section('page-script')
{{-- <script src="{{asset('assets/js/forms-editors.js')}}"></script> --}}
@endsection

@section('content')
<h4 class="fw-bold py-1">
  <span class="fw-light">{{ __('Configurations')}}</span>
</h4>
<div id="vendor-settings">
  <vendor-settings
    can_edit={{ auth()->user()->hasPermissionTo('Manage Configurations') ? 1 : 0 }}
    can_edit_anchor={{ auth()->user()->hasPermissionTo('Manage Anchor Settings') ? 1 : 0 }}
    checker={{ auth()->user()->hasPermissionTo('Configurations Changes Checker') ? 1 : 0 }}
    has_proposed_updates={{ $proposed_updates > 0 ? 1 : 0 }}
  ></vendor-settings>
</div>
@endsection
