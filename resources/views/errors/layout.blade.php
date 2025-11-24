<!DOCTYPE html>
<html lang="en">
  @php
    $favicon = asset('assets/img/favicon/favicon.ico');
    $page_title = 'YoFinvoice';
  @endphp
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

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

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
                font-weight: 100;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 36px;
                padding: 20px;
            }
        </style>
    </head>
    <body>
      <div class="flex-center position-ref full-height">
        <div class="content">
          <img src="{{ asset('assets/img/branding/logo-name.png') }}" alt="" style="width: 410px">
          <div class="title">
            @yield('message')
          </div>
          <a href="{{ url()->previous() }}">Go Back</a>
        </div>
      </div>
    </body>
</html>
