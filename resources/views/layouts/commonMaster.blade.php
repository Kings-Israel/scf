<!DOCTYPE html>

<html lang="{{ session()->get('locale') ?? app()->getLocale() }}" class="{{ $configData['style'] }}-style {{ $navbarFixed ?? '' }} {{ $menuFixed ?? '' }} {{ $menuCollapsed ?? '' }} {{ $footerFixed ?? '' }} {{ $customizerHidden ?? '' }}" dir="{{ $configData['textDirection'] }}" data-theme="{{ $configData['theme'] }}" data-assets-path="{{ asset('/assets') . '/' }}" data-base-url="{{url('/')}}" data-framework="laravel" data-template="{{ $configData['layout'] . '-menu-' . $configData['theme'] . '-' . $configData['style'] }}">

@php
  $favicon = asset('assets/img/favicon/favicon.ico');
  $page_title = 'YoFinvoice';
@endphp
@auth
  @php
    $bank_user = App\Models\BankUser::where('user_id', auth()->id())->first();
    if (!$bank_user) {
      $company_user = App\Models\CompanyUser::where('user_id', auth()->id())->first();
      if ($company_user) {
        $page_title = $company_user->company->bank->name;
        $favicon = $company_user->company->bank->adminConfiguration?->favicon ?? 'https://lms.amaniaccess.com/assets/favicon/apple-touch-icon.png';
      }
    } else {
      $page_title = $bank_user->bank->name;
      $favicon = $bank_user->bank->adminConfiguration?->favicon ?? 'https://lms.amaniaccess.com/assets/favicon/apple-touch-icon.png';
    }
  @endphp
@endauth

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

  <title>@yield('title') - {{ __($page_title) }}</title>
  <meta name="description" content="{{ config('variables.templateDescription') ? config('variables.templateDescription') : '' }}" />
  <meta name="keywords" content="{{ config('variables.templateKeyword') ? config('variables.templateKeyword') : '' }}">
  <!-- laravel CRUD token -->
  <meta name="csrf-token" content="{{ csrf_token() }}">
  {{-- <meta http-equiv="refresh" content="{{ config('session.lifetime') }}"> --}}
  <!-- Canonical SEO -->
  <link rel="canonical" href="{{ config('variables.productPage') ? config('variables.productPage') : '' }}">
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="{{ $favicon }}" />
  <link rel="apple-touch-icon" sizes="180x180" href="{{ $favicon }}">
  <link rel="icon" type="image/png" sizes="32x32" href="{{ $favicon }}">
  <link rel="icon" type="image/png" sizes="16x16" href="{{ $favicon }}">

  <!-- Include Styles -->
  @include('layouts/sections/styles')

  <!-- Include Scripts for customizer, helper, analytics, config -->
  @include('layouts/sections/scriptsIncludes')
  @vite(['resources/assets/css/demo.css', 'resources/js/app.js'])
</head>

<body>
  <!-- Layout Content -->
  @yield('layoutContent')
  <!--/ Layout Content -->

  <!-- Include Scripts -->
  @include('layouts/sections/scripts')
</body>

</html>
