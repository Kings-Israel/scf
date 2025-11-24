@php
$containerNav = $containerNav ?? 'container-fluid';
$navbarDetached = ($navbarDetached ?? '');
$brand_img = asset('assets/img/branding/logo-name.png');
$bank = request()->route('bank');
$configurations = $bank->adminConfiguration()->exists() ? $bank->adminConfiguration : NULL;
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
        <a href="{{ route('bank.dashboard', ['bank' => request()->route('bank')->url]) }}" class="app-brand-link gap-2">
          <span class="app-brand-logo demo">
            @include('_partials.macros',["height" => 20])
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
          {{-- INFO: Allow Bank Users to switch banks of assigned to multiple banks --}}
          {{-- <li class="nav-item mx-2">
            <div class="">
              @php($banks = auth()->user()->banks()->where('status', 'active')->where('approval_status', 'approved')->get())
              @if (!auth()->user()->activeBank)
              @php(auth()->user()->activeBank()->create(['bank_id' => $bank->id]))
              @endif
              @php($current_bank = auth()->user()->activeBank->bank_id)
              <form action="{{ route('bank.switch', ['bank' => $bank]) }}" method="post" id="bank-switch-form">
                @csrf
                <select id="bank-switcher" class="form-select" name="bank_id" onchange="event.preventDefault(); document.getElementById('bank-switch-form').submit();">
                  @if ($banks->count() > 0)
                  @foreach ($banks->unique() as $bank_selection)
                  <option value="{{ $bank_selection->id }}" @if($bank_selection->id == $current_bank) selected @endif>{{ $bank_selection->name }}</option>
                  @endforeach
                  @endif
                </select>
              </form>
            </div>
          </li> --}}
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
              <li>
                <a class="dropdown-item" href="{{url('lang/sw')}}" data-language="sw">
                  <i class="fi fi-ke fis rounded-circle me-1 fs-3"></i>
                  <span class="align-middle">Swahili</span>
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
            <a class="nav-link hide-arrow" href="{{ route('help.index', ['bank' => request()->route('bank')->url]) }}" target="_blank">
              <i class='ti ti-help ti-md'></i>
            </a>
          </li>
          {{-- /Help and Manuls Page --}}

          <!-- Notification -->
          <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-1">
            <a class="nav-link hide-arrow" href="{{ route('notifications', ['bank' => request()->route('bank')->url]) }}" aria-expanded="false">
              <i class="ti ti-bell ti-md"></i>
              <span class="badge bg-danger rounded-pill badge-notifications">{{ request()->route('bank')->unreadNotifications()->count() + auth()->user()->unreadNotifications()->count() }}</span>
            </a>
          </li>
          <!--/ Notification -->

          <!-- User -->
          <li class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
              <div class="avatar">
                <img src="{{ request()->route('bank')->adminConfiguration?->favicon ? request()->route('bank')->adminConfiguration?->favicon : (Auth::user() ? (Auth::user()->avatar ? Auth::user()->avatar : asset('assets/img/avatars/user.png')) : asset('assets/img/avatars/1.png')) }}" alt class="h-auto rounded-circle">
              </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item" href="{{ route('configurations.users.edit', ['bank' => $bank, 'user' => auth()->user()]) }}">
                  <div class="d-flex">
                    <div class="flex-shrink-0 me-3">
                      <div class="avatar">
                        <img src="{{ Auth::user() ? (Auth::user()->avatar ? Auth::user()->avatar : asset('assets/img/avatars/user.png')) : asset('assets/img/avatars/1.png') }}" alt class="h-auto rounded-circle">
                      </div>
                    </div>
                    <div class="flex-grow-1">
                      @if (Auth::check())
                      <span class="fw-semibold d-block">
                        {{ Auth::user()->name }}
                      </span>
                      <small class="text-muted">{{ auth()->user()->roles->count() > 0 ? auth()->user()->roles[0]->name : '' }}</small>
                      @endif
                    </div>
                  </div>
                </a>
              </li>
              <li>
                <div class="dropdown-divider"></div>
              </li>
              @if (Auth::check())
                <li>
                  <a class="dropdown-item" href="{{ route('configurations.users.edit', ['bank' => $bank, 'user' => auth()->user()]) }}">
                    <i class="ti ti-user-check me-2 ti-sm"></i>
                    <span class="align-middle">{{ __('Edit Profile')}}</span>
                  </a>
                </li>
              @endif
              <li>
                <div class="dropdown-divider"></div>
              </li>
              @if (Auth::check())
                <li>
                  <a class="dropdown-item" href="{{ route('logout', ['bank' => request()->route('bank')]) }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class='ti ti-logout me-2'></i>
                    <span class="align-middle">{{ __('Logout')}}</span>
                  </a>
                </li>
                <form method="POST" id="logout-form" action="{{ route('logout', ['bank' => request()->route('bank')]) }}">
                  @csrf
                </form>
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
