@extends('layouts/layoutMaster')

@section('title', 'Help Center')

<!-- Page -->
@section('page-style')
<link rel="stylesheet" href="{{asset('assets/vendor/css/pages/page-help-center.css')}}" />
@endsection

@section('content')
<!-- Help Center Header -->
<div class="help-center-header rounded d-flex flex-column justify-content-center align-items-center bg-help-center">
  <img src="{{ asset('assets/img/help.png') }}" alt="YofInvoice" style="width: 15rem">
</div>
<!-- /Help Center Header -->

<div class="help-center-popular-articles bg-help-center py-5">
  <div class="container-xl">
    <div class="row">
      <div class="col-lg-10 mx-auto mb-4">
        <div class="row">
          <div @if ($bank_manual && $bank_manual->bank_user_manual) class="col-md-4 mb-md-0 mb-4" @else class="col-md-6 mb-md-0 mb-4" @endif>
            <div class="card border shadow-none">
              <div class="card-body text-center">
                <i class="ti ti-phone ti-xl text-primary" style="font-size: 25px"></i>
                <br>
                <a href="tel:{{ $system_configuration?->help_contact_number }}" class="my-2" style="font-size: 20px">{{ $system_configuration?->help_contact_number }}</a>
                <br>
                <p> {{ __('Feel Free to call us anytime. We are available 24/7') }}</p>
              </div>
            </div>
          </div>

          <div @if ($bank_manual && $bank_manual->bank_user_manual) class="col-md-4 mb-md-0 mb-4" @else class="col-md-6 mb-md-0 mb-4" @endif>
            <div class="card border shadow-none">
              <div class="card-body text-center">
                <i class="ti ti-mail ti-xl text-primary"></i>
                <br>
                <a href="mailto:{{ $system_configuration?->help_contact_email }}" class="my-2" style="font-size: 20px">{{ $system_configuration?->help_contact_email }}</a>
                <br>
                <p> {{ __('Write to us & we will get back in couple of hours.') }} </p>
              </div>
            </div>
          </div>
          @if ($bank_manual && $bank_manual->bank_user_manual)
            <div class="col-md-4">
              <div class="card border shadow-none">
                <div class="card-body text-center">
                  <i class="ti ti-book ti-xl text-primary"></i>
                  <br>
                  <a href="{{ $bank_manual->bank_user_manual }}" target="_blank" class="my-2" style="font-size: 20px">View Manual</a>
                  <br>
                  <p> This article will help you to understand the functionality of this dashboard </p>
                </div>
              </div>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
