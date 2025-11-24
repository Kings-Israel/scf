@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
// $brand_img = asset('assets/img/branding/logo-name.png');
$brand_img = 'https://lms.amaniaccess.com/assets/images/amani-logo.png';
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Forgot Password')

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
@endsection

@section('content')
<div class="authentication-wrapper authentication-cover">
  <div class="authentication-inner row">

    <!-- /Left Text -->
    {{-- <div class="d-none d-lg-flex col-lg-7 p-0">
      <div class="auth-cover-bg auth-cover-bg-color d-flex justify-content-center align-items-center">
        <img src="{{ asset('assets/img/illustrations/auth-forgot-password-illustration-'.$configData['style'].'.png') }}" alt="auth-forgot-password-cover" class="img-fluid my-5 auth-illustration" data-app-light-img="illustrations/auth-forgot-password-illustration-light.png" data-app-dark-img="illustrations/auth-forgot-password-illustration-dark.png">

        <img src="{{ asset('assets/img/illustrations/bg-shape-image-'.$configData['style'].'.png') }}" alt="auth-forgot-password-cover" class="platform-bg" data-app-light-img="illustrations/bg-shape-image-light.png" data-app-dark-img="illustrations/bg-shape-image-dark.png">
        <img class="img-fluid auth-illustration w-75" src="{{ $brand_img }}" alt="">
      </div>
    </div> --}}
    <!-- /Left Text -->

    <!-- Forgot Password -->
    <div class="d-flex col-12 col-lg-12 align-items-center p-sm-5 p-4">
      <div class="w-px-400 mx-auto">
        <div class="app-brand mb-4">
          <img class="img-fluid my-5 auth-illustration mx-auto w-75" src="{{ $brand_img }}" alt="">
        </div>
        <h3 class="mb-1 fw-bold">Set Password 🔒</h3>
        <form id="formAuthentication" class="mb-3" action="{{ route('password.set') }}" method="POST">
          @csrf
          <input type="hidden" name="user_id" value="{{ $user }}">
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="Set your password" autofocus>
            <x-input-error :messages="$errors->get('password')" />
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password Confirmation</label>
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Confirm your password">
            <x-input-error :messages="$errors->get('password_confirmation')" />
          </div>
          <button class="btn btn-primary d-grid w-100">Submit</button>
        </form>
      </div>
    </div>
    <!-- /Forgot Password -->
  </div>
</div>
@endsection
