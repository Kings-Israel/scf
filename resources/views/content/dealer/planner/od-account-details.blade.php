@extends('layouts/buyerLayoutMaster')

@section('title', 'OD Account Details')

@section('vendor-style')
@endsection

@section('page-style')

@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/moment/moment.js')}}"></script>
@endsection

@section('page-script')
<script>

</script>
@endsection

@section('content')
<div class="d-flex justify-content-between">
  <h4 class="fw-bold">
    <span class="fw-light">{{ __('OD Account Details')}}</span>
  </h4>
  <div class="d-flex">
    <a href="#" class="btn btn-primary btn-sm">{{ __('Total Outstanding')}}</a>
    <a href="#" class="btn btn-primary btn-sm mx-1">{{ __('View Daily Discount Details')}}</a>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="row">
      <div
        class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">{{ __('Payment Account')}}:</h6>
        <h5 class="px-2 text-right">
            {{ $vendor_configuration->payment_account_number }}
        </h5>
      </div>
      <div class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">{{ __('Anchor')}}:</h6>
        <span class="fw-bold text-decoration-underline text-right text-nowrap">{{ $vendor_configuration->program->anchor->name }}</span>
      </div>
      <div class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">{{ __('Account Status')}}:</h6>
        <h5 class="px-2 text-right">{{ Str::title($vendor_configuration->program->account_status) }}</h5>
      </div>
      <div class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">{{ __('Sanctioned Limit')}}:</h6>
        <h5 class="px-2 text-right">
          {{ number_format($vendor_configuration->sanctioned_limit) }}
        </h5>
      </div>
      <div class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">{{ __('Pipeline Requests')}}:</h6>
        <h5 class="px-2 text-right">
          {{ number_format($pipeline_amount) }}
        </h5>
      </div>
      <div class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">{{ __('Utilized Amount')}}:</h6>
        <h5 class="px-2 text-right">
          {{ number_format($utilized_amount) }}
        </h5>
      </div>
      <div class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">{{ __('Available Limit')}}:</h6>
        <h5 class="px-2 text-right">
          {{ number_format($vendor_configuration->sanctioned_limit - $utilized_amount) }}
        </h5>
      </div>
      <div class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">{{ __('Limit Approval Date')}}:</h6>
        <h5 class="px-2 text-right">
          {{ Carbon\Carbon::parse($vendor_configuration->limit_approved_date)->format('d M Y') }}
        </h5>
      </div>
      <div class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">{{ __('Limit Expiry Date')}}:</h6>
        <h5 class="px-2 text-right">
          {{ Carbon\Carbon::parse($vendor_configuration->limit_expiry_date)->format('d M Y') }}
        </h5>
      </div>
      <div class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">L{{ __('Limit Review Date')}}:</h6>
        <h5 class="px-2 text-right">
          {{ Carbon\Carbon::parse($vendor_configuration->limit_review_date)->format('d M Y') }}
        </h5>
      </div>
    </div>
  </div>
</div>

<h4 class="fw-bold mt-4">
  <span class="fw-light">{{ __('OD Account Summary')}}</span>
</h4>

<div id="od_account_summary">
  <od-account-summary od_account={{ $vendor_configuration->id }}></od-account-summary>
</div>
@endsection
