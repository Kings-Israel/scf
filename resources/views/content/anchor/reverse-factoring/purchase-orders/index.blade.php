@extends('layouts/anchorLayoutMaster')

@section('title', 'Purchase Orders')

@section('vendor-style')
@endsection

@section('page-style')
<style>
  .table-responsive .dropdown,
  .table-responsive .btn-group,
  .table-responsive .btn-group-vertical {
      position: static;
  }
</style>
@endsection

@section('vendor-script')
@endsection

@section('page-script')
@endsection

@section('content')
<h4 class="fw-light py-2">
  {{ __('Purchase Order')}}
</h4>
<div id="purchase_orders">
  <purchase-orders-component></purchase-orders-component>
</div>
@endsection
