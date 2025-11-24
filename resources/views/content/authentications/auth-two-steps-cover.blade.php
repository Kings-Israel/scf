@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
// $brand_img = asset('assets/img/branding/logo-name.png');
$brand_img = 'https://lms.amaniaccess.com/assets/images/amani-logo.png';
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Verification')

@section('vendor-style')
<!-- Vendor -->
<link rel="stylesheet" href="{{asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css')}}" />
@endsection

@section('page-style')
<!-- Page -->
<link rel="stylesheet" href="{{asset('assets/vendor/css/pages/page-auth.css')}}">
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/cleavejs/cleave.js')}}"></script>
<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js')}}"></script>
<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js')}}"></script>
<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js')}}"></script>
@endsection

@section('page-script')
<script src="{{asset('assets/js/pages-auth.js')}}"></script>
<script src="{{asset('assets/js/pages-auth-two-steps.js')}}"></script>
<script>
  var timer2 = "2:00";
  var interval = setInterval(function() {
    var timer = timer2.split(':');
    //by parsing integer, I avoid all extra string processing
    var minutes = parseInt(timer[0], 10);
    var seconds = parseInt(timer[1], 10);
    --seconds;
    minutes = (seconds < 0) ? --minutes : minutes;
    seconds = (seconds < 0) ? 59 : seconds;
    seconds = (seconds < 10) ? '0' + seconds : seconds;
    //minutes = (minutes < 10) ?  minutes : minutes;
    $('.countdown').html(minutes + ':' + seconds);
    if (minutes < 0) clearInterval(interval);
    //check if both minutes and seconds are 0
    if ((seconds <= 0) && (minutes <= 0)) {
      clearInterval(interval)
      $('.verify-btn').attr('disabled', 'disabled')
    };
    timer2 = minutes + ':' + seconds;
  }, 1000);
</script>
@endsection

@section('content')
<div class="authentication-wrapper authentication-cover">
  <div class="authentication-inner row">

    <!-- /Left Text -->
    {{-- <div class="d-none d-lg-flex col-lg-7 p-0">
      <div class="auth-cover-bg auth-cover-bg-color d-flex justify-content-center align-items-center">
        <img src="{{ asset('assets/img/illustrations/auth-two-step-illustration-'.$configData['style'].'.png') }}" alt="auth-two-steps-cover" class="img-fluid my-5 auth-illustration" data-app-light-img="illustrations/auth-two-step-illustration-light.png" data-app-dark-img="illustrations/auth-two-step-illustration-dark.png">

        <img src="{{ asset('assets/img/illustrations/bg-shape-image-'.$configData['style'].'.png') }}" alt="auth-two-steps-cover" class="platform-bg" data-app-light-img="illustrations/bg-shape-image-light.png" data-app-dark-img="illustrations/bg-shape-image-dark.png">
      </div>
    </div> --}}
    <!-- /Left Text -->

    <!-- Two Steps Verification -->
    <div class="d-flex col-12 col-lg-12 align-items-center p-4 p-sm-5">
      <div class="w-px-400 mx-auto">
        <!-- Logo -->
        <div class="app-brand mb-4">
          <img class="img-fluid my-5 auth-illustration mx-auto w-75" src="{{ $brand_img }}" alt="">
        </div>
        <!-- /Logo -->

        <h3 class="mb-1 fw-bold">{{ __('Two Step Verification') }}</h3>
        <p class="text-start mb-4">
          {{ __('We sent a verification code to your email. Enter the code in the field below.') }}
        </p>

        <p class="mb-0 fw-semibold">{{ __('Type your 6 digit security code') }}</p>
        <form id="twoStepsForm" action="{{ route('verify.confirm') }}" method="POST">
          @csrf
          <input type="hidden" name="user_id" value="{{ $user_id }}">
          <div class="mb-3">
            <div class="auth-input-wrapper d-flex align-items-center justify-content-sm-between numeral-mask-wrapper">
              <input type="text" class="form-control auth-input h-px-50 text-center numeral-mask text-center h-px-50 mx-1 my-2" maxlength="1" autofocus>
              <input type="text" class="form-control auth-input h-px-50 text-center numeral-mask text-center h-px-50 mx-1 my-2" maxlength="1">
              <input type="text" class="form-control auth-input h-px-50 text-center numeral-mask text-center h-px-50 mx-1 my-2" maxlength="1">
              <input type="text" class="form-control auth-input h-px-50 text-center numeral-mask text-center h-px-50 mx-1 my-2" maxlength="1">
              <input type="text" class="form-control auth-input h-px-50 text-center numeral-mask text-center h-px-50 mx-1 my-2" maxlength="1">
              <input type="text" class="form-control auth-input h-px-50 text-center numeral-mask text-center h-px-50 mx-1 my-2" maxlength="1">
            </div>
            <!-- Create a hidden field which is combined by 3 fields above -->
            <input type="hidden" name="otp" />
          </div>
          <div class="d-flex justify-content-center my-1">
            <span>{{ __('Code Expires in') }}</span>
            <div class="countdown text-danger mx-1"></div>
          </div>
          <button class="btn btn-primary d-grid w-100 mb-3 verify-btn">
            {{ __('Verify') }}
          </button>
          <div class="text-center">{{ __('Didn\'t get the code?') }}
            <a href="{{ route('verify.resend', ['user_id' => $user_id]) }}">
              {{ __('Resend') }}
            </a>
          </div>
          <div class="text-center">
            <a href="{{ route('login') }}">
              {{ __('Back To Login') }}
            </a>
          </div>
        </form>
      </div>
    </div>
    <!-- /Two Steps Verification -->
  </div>
</div>
@endsection
