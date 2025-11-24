@php
$containerNav = $containerNav ?? 'container-fluid';
$navbarDetached = ($navbarDetached ?? '');
$navbarHideToggle = ($navbarHideToggle ?? '');
$navbarFull = ($navbarFull ?? '');
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
$brand_img = asset('assets/img/branding/logo-name.png');
@endphp

@extends('layouts/layoutMaster')

@section('title', 'T&Cs')

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
  // $('#submit-btn').attr('disabled', 'disabled')
  $('#accept-terms').change(function () {
    if ($(this).is(':checked')) {
      $('#submit-btn').removeAttr('disabled')
    } else {
      $('#submit-btn').attr('disabled', 'disabled')
    }
  })
</script>
@endsection

@section('content')
@if(isset($navbarDetached) && $navbarDetached == 'navbar-detached')
<nav class="layout-navbar {{$containerNav}} navbar navbar-expand-xl {{$navbarDetached}} align-items-center bg-navbar-theme" id="layout-navbar">
  @endif
  @if(isset($navbarDetached) && $navbarDetached == '')
  <nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
    <div class="{{$containerNav}}">
      @endif

      <!--  Brand demo (display only for navbar-full and hide on below xl) -->
      @if(isset($navbarFull))
      <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4">
        <a href="{{url('/')}}" class="app-brand-link gap-2">
          <span class="app-brand-logo demo">
            <img src="{{ $brand_img }}" alt="YoFInvoice" class="h-20">
          </span>
        </a>
      </div>
      @endif

      <!-- ! Not required for layout-without-menu -->
      @if(!isset($navbarHideToggle))
      <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0{{ isset($menuHorizontal) ? ' d-xl-none ' : '' }} {{ isset($contentNavbar) ?' d-xl-none ' : '' }}">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
          <i class="ti ti-menu-2 ti-sm"></i>
        </a>
      </div>
      @endif

      <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        <ul class="navbar-nav flex-row align-items-center ms-auto">
          <!-- Language -->
          <li class="nav-item dropdown-language dropdown me-2 me-xl-0">
            <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
              <i class='fi fi-us fis rounded-circle me-1 fs-3'></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item" href="{{url('lang/en')}}" data-language="en">
                  <i class="fi fi-us fis rounded-circle me-1 fs-3"></i>
                  <span class="align-middle">English</span>
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="{{url('lang/fr')}}" data-language="fr">
                  <i class="fi fi-fr fis rounded-circle me-1 fs-3"></i>
                  <span class="align-middle">French</span>
                </a>
              </li>
            </ul>
          </li>
          <!--/ Language -->

          <!-- Style Switcher -->
          <li class="nav-item me-2 me-xl-0">
            <a class="nav-link style-switcher-toggle hide-arrow" href="javascript:void(0);">
              <i class='ti ti-md'></i>
            </a>
          </li>
          <!--/ Style Switcher -->

          {{-- Help and Manuals Page --}}
          <li class="nav-item me-2 me-xl-0">
            <a class="nav-link hide-arrow" href="{{ route('vendor.help.index') }}" target="_blank">
              <i class='ti ti-help ti-md'></i>
            </a>
          </li>
          {{-- /Help and Manuls Page --}}

          <!-- User -->
          <li class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
              <div class="avatar">
                <img src="{{ asset('assets/img/avatars/user.png') }}" alt class="h-auto rounded-circle">
              </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item" href="#">
                  <div class="d-flex">
                    <div class="flex-grow-1">
                      <span class="fw-semibold d-block">
                        @if (Auth::check())
                          {{ Auth::user()->name }}
                        @endif
                      </span>
                    </div>
                  </div>
                </a>
              </li>
              <li>
                <div class="dropdown-divider"></div>
              </li>
              @if (Auth::check())
              <li>
                <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                  <i class='ti ti-logout me-2'></i>
                  <span class="align-middle">Logout</span>
                </a>
              </li>
              <form method="POST" id="logout-form" action="{{ route('logout') }}">
                @csrf
              </form>
              @else
              <li>
                <a class="dropdown-item" href="{{ Route::has('login') ? route('login') : 'javascript:void(0)' }}">
                  <i class='ti ti-login me-2'></i>
                  <span class="align-middle">Login</span>
                </a>
              </li>
              @endif
            </ul>
          </li>
          <!--/ User -->
        </ul>
      </div>

      <!-- Search Small Screens -->
      <div class="navbar-search-wrapper search-input-wrapper {{ isset($menuHorizontal) ? $containerNav : '' }} d-none">
        <input type="text" class="form-control search-input {{ isset($menuHorizontal) ? '' : $containerNav }} border-0" placeholder="Search..." aria-label="Search...">
        <i class="ti ti-x ti-sm search-toggler cursor-pointer"></i>
      </div>
      @if(!isset($navbarDetached))
    </div>
    @endif
  </nav>
  <!-- / Navbar -->
</nav>
<div class="mt-4 px-4">
  <div class="card p-1 px-2">
    <h6>Terms and Conditions</h6>
    <div
      class="border border-primary rounded-top rounded-bottom p-1 card"
      v-if="terms_text && terms_text.terms_conditions != ''"
      style="max-height: 60vh; overflow-y: auto"
    >
      <span class="text-wrap">
        {!! $terms_and_conditions !!}
      </span>
    </div>
    <form action="{{ route('auth.terms.submit') }}" method="POST">
      @csrf
      <div class="d-flex flex-column mt-2">
        <div class="form-check">
          <input
            class="form-check-input border-primary"
            type="checkbox"
            id="accept-terms"
            name="accept_terms"
          />
          <label for="" class="form-label">Accept Terms and Conditions</label>
        </div>
        <x-input-error :messages="$errors->get('accept_terms')" />
        <div>
          <button
            type="submit"
            class="btn btn-primary"
            id="submit-btn"
          >
            Accept
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection
