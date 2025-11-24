<!-- BEGIN: Theme CSS-->
<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
@php
  $primary_color = '#0154AF';
  $secondary_color = '#0154AF';
  $bank = request()->route('bank');
  if ($bank) {
    $configurations = $bank->adminConfiguration()->exists() ? $bank->adminConfiguration : NULL;
    if ($configurations) {
      $primary_color = $configurations->primary_color;
      $secondary_color = $configurations->secondary_color;
    }
  }
@endphp

<link rel="stylesheet" href="{{ asset('assets/vendor/fonts/fontawesome.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/fonts/tabler-icons.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/fonts/flag-icons.css') }}" />

<!-- Core CSS -->
<link rel="stylesheet" href="{{ asset('assets/vendor/css' .$configData['rtlSupport'] .'/core' .($configData['style'] !== 'light' ? '-' . $configData['style'] : '') .'.css') }}" class="{{ $configData['hasCustomizer'] ? 'template-customizer-core-css' : '' }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/css' .$configData['rtlSupport'] .'/' .$configData['theme'] .($configData['style'] !== 'light' ? '-' . $configData['style'] : '') .'.css') }}" class="{{ $configData['hasCustomizer'] ? 'template-customizer-theme-css' : '' }}" />
<link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />

<link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/node-waves/node-waves.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/typeahead-js/typeahead.css') }}" />

<style>
  body {
    font-size: 12px !important;
  }
  .container, .container-fluid, .container-sm, .container-md, .container-lg, .container-xl, .container-xxl {
    padding-right: 0.3rem !important;
    padding-left: 0.3rem !important;
  }
  .tab-content {
    padding: 0.5rem !important;
  }
  .table-search-btn {
    margin-top: auto;
  }
  .table-clear-btn {
    margin-top: auto;
  }
  nav .text-primary {
    color: {!! $primary_color !!} !important
  }
  nav .active .page-link {
    background-color: {!! $primary_color !!} !important
  }
  .btn-primary {
    background-color: {!! $primary_color !!} !important;
  }
  .btn-secondary {
    background-color: {!! $secondary_color !!} !important;
  }
  .bg-menu-theme.menu-horizontal .menu-item.active > .menu-link:not(.menu-toggle) {
    background: {!! $primary_color !!} !important;
  }
  .bg-menu-theme.menu-horizontal .menu-inner > .menu-item.active > .menu-link.menu-toggle {
    background: {!! $primary_color !!} !important;
  }
  .nav-pills .nav-link.active {
    background-color: {!! $primary_color !!} !important;
  }
  .nav-tabs .nav-link.active {
    color: {!! $primary_color !!} !important;
  }
  .bs-stepper .step.active .bs-stepper-circle {
    background-color: {!! $primary_color !!} !important;
  }
  .text-primary {
    color: {!! $primary_color !!} !important;
  }
  .text-seondary {
    color: {!! $secondary_color !!} !important;
  }
  .form-check-input:checked {
    background-color: {!! $primary_color !!} !important;
    border-color: {!! $primary_color !!} !important;
  }
  .form-search {
    min-width: 216px !important;
  }
  .pointer {
    cursor: pointer;
  }
  .select2-container--default .select2-selection--multiple .select2-selection__rendered {
    display: flex !important;
  }
  .card-header {
    padding: 0.5rem !important;
  }
  .card-body {
    padding: 0.5rem !important;
  }

  @media (max-width: 768px) {
    .pagination .page-item:nth-child(n+4):nth-last-child(n+4) {
      display: none; /* Hide middle page numbers on small screens */
    }
  }
</style>

<!-- Vendor Styles -->
@yield('vendor-style')

<!-- Page Styles -->
@yield('page-style')
