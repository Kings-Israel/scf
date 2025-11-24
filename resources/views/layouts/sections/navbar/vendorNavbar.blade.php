@php
$containerNav = $containerNav ?? 'container-fluid';
$navbarDetached = ($navbarDetached ?? '');
$active_company = \App\Models\Company::find(Auth::user()->activeVendorCompany()->first()->company_id);
$brand_img = asset('assets/img/branding/logo-name.png');
$bank = $active_company->bank;
$configurations = $bank->adminConfiguration()->exists() ? $bank->adminConfiguration : NULL;
if ($configurations && $configurations->logo) {
$brand_img = $configurations->logo;
}
@endphp

<!-- Navbar -->
@if(isset($navbarDetached) && $navbarDetached == 'navbar-detached')
<nav class="layout-navbar {{$containerNav}} navbar navbar-expand-xl {{$navbarDetached}} align-items-center bg-navbar-theme" id="layout-navbar">
  @endif
  @if(isset($navbarDetached) && $navbarDetached == '')
  <nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
    <div class="{{$containerNav}}">
      @endif

      <!--  Brand demo (display only for navbar-full and hide on below xl) -->
      @if(isset($navbarFull))
      <div class="navbar-brand app-brand demo d-xl-flex py-0 me-4">
        <a href="{{url('/')}}" class="app-brand-link gap-2">
          <span class="app-brand-logo demo">
            @include('_partials.macros',["height"=>20])
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
          <li class="nav-item mx-2">
            <div class="">
              {{-- @php($companies = auth()->user()->companies()->vendor()->get()) --}}
              @php($companies = auth()->user()->companies()->where('status', 'active')->get())
              @if (!auth()->user()->activeVendorCompany)
                @php(auth()->user()->activeVendorCompany()->create(['company_id' => $companies->first()->id, 'platform' => 'vendor']))
              @endif
              @php($current_company = auth()->user()->activeVendorCompany->company_id)
              <form action="{{ route('vendor.company.switch') }}" method="post" id="company-switch-form">
                @csrf
                <select id="company-switcher" class="form-select" name="company_id" onchange="event.preventDefault(); document.getElementById('company-switch-form').submit();">
                  @if ($companies->count() > 0)
                    @foreach ($companies->unique() as $company)
                      <option value="{{ $company->id }}" @if($company->id == $current_company) selected @endif>{{ $company->name }}</option>
                    @endforeach
                  @endif
                </select>
              </form>
            </div>
          </li>

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

          <!-- Notification -->
          <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-1">
            <a class="nav-link hide-arrow" href="{{ route('vendor.notifications') }}">
              <i class="ti ti-bell ti-md"></i>
              <span class="badge bg-danger rounded-pill badge-notifications">{{ $active_company->unreadNotifications()->count() }}</span>
            </a>
          </li>
          <!--/ Notification -->

          <!-- User -->
          <li class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
              <div class="avatar">
                <img src="{{ $active_company && $active_company->logo ? $active_company->logo : asset('assets/img/avatars/user.png') }}" alt class="h-auto rounded-circle">
              </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item" href="{{ route('vendor.company.profile', ['company' => $active_company]) }}">
                  <div class="d-flex">
                    <div class="flex-grow-1">
                      <span class="fw-semibold d-block">
                        @if (Auth::check())
                        {{ Auth::user()->name }}
                        @else
                        John Doe
                        @endif
                      </span>
                    </div>
                  </div>
                </a>
              </li>
              <li>
                <div class="dropdown-divider"></div>
              </li>
              <li>
                <a class="dropdown-item" href="{{ route('vendor.company.profile', ['company' => $active_company]) }}">
                  <i class="ti ti-user-check me-2 ti-sm"></i>
                  <span class="align-middle">Profile</span>
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
