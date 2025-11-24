@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
// $brand_img = asset('assets/img/branding/logo-name.png');
$brand_img = 'https://lms.amaniaccess.com/assets/images/amani-logo.png';
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Login')

@section('vendor-style')
<!-- Vendor -->
<link rel="stylesheet" href="{{asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css')}}" />
@endsection

@section('page-style')
<!-- Page -->
<link rel="stylesheet" href="{{asset('assets/vendor/css/pages/page-auth.css')}}">
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js')}}"></script>
<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js')}}"></script>
<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js')}}"></script>
@endsection

@section('page-script')
<script src="{{asset('assets/js/pages-auth.js')}}"></script>
<script>
  $('#formAuthentication').on('submit', function () {
    $('#sign-in-btn').attr('disabled', true)
  })
</script>
@endsection

@section('content')
<div class="authentication-wrapper authentication-cover">
  <div class="authentication-inner row">
    <!-- /Left Text -->
    {{-- <div class="d-none d-lg-flex col-lg-7 p-0">
      <div class="auth-cover-bg auth-cover-bg-color d-flex justify-content-center align-items-center">
        <img src="{{ asset('assets/img/illustrations/auth-login-illustration-'.$configData['style'].'.png') }}" alt="auth-login-cover" class="img-fluid my-5 auth-illustration" data-app-light-img="illustrations/auth-login-illustration-light.png" data-app-dark-img="illustrations/auth-login-illustration-dark.png">

        <img src="{{ asset('assets/img/illustrations/bg-shape-image-'.$configData['style'].'.png') }}" alt="auth-login-cover" class="platform-bg" data-app-light-img="illustrations/bg-shape-image-light.png" data-app-dark-img="illustrations/bg-shape-image-dark.png">
      </div>
    </div> --}}
    <!-- /Left Text -->

    <!-- Login -->
    <div class="d-flex col-12 col-lg-12 align-items-center p-sm-5 p-4">
      <div class="w-px-400 mx-auto">
        <div class="app-brand mb-4">
          <img class="img-fluid my-5 auth-illustration mx-auto w-75" src="{{ $brand_img }}" alt="">
        </div>
        <form id="formAuthentication" class="mb-3" action="{{ route('login.submit') }}" method="POST">
          @csrf
          <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input type="text" class="form-control" id="email" name="email" placeholder="Enter your email" autofocus value="{{ old('email') }}">
            <x-input-error :messages="$errors->get('email')" />
          </div>
          <div class="mb-3 form-password-toggle">
            <div class="d-flex justify-content-between">
              <label class="form-label" for="password">{{ __('Password') }}</label>
              <a href="{{ route('auth.forgot.password') }}">
                <small>{{ __('Forgot Password') }}?</small>
              </a>
            </div>
            <div class="input-group input-group-merge">
              <input type="password" id="password" class="form-control" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" aria-describedby="password" />
              <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
            </div>
          </div>
          <button class="btn btn-primary d-grid w-100" id="sign-in-btn">
            {{ __('Sign in') }}
          </button>
        </form>
      </div>
    </div>
    <!-- /Login -->
  </div>
</div>
@endsection
