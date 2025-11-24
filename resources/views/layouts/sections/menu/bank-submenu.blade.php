<ul class="menu-sub">
  @if (isset($menu))
    @foreach ($menu as $submenu)

    {{-- active menu method --}}
    @php
      $activeClass = null;
      $active = $configData["layout"] === 'vertical' ? 'active open':'active';
      $currentRouteName =  Route::currentRouteName();

      if ($currentRouteName === $submenu->slug) {
          $activeClass = 'active';
      }
      elseif (isset($submenu->submenu)) {
        if (gettype($submenu->slug) === 'array') {
          foreach($submenu->slug as $slug){
            if (str_contains($currentRouteName,$slug) and strpos($currentRouteName,$slug) === 0) {
                $activeClass = $active;
            }
          }
        }
        else{
          if (str_contains($currentRouteName,$submenu->slug) and strpos($currentRouteName,$submenu->slug) === 0) {
            $activeClass = $active;
          }
        }
      }
    @endphp

      <li class="menu-item {{$activeClass}}">
        @if (isset($submenu->permissions))
          @canany($submenu->permissions)
            <a href="{{ isset($submenu->url) ? route($submenu->url, ['bank' => request()->route('bank')]) : 'javascript:void(0)' }}" class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
              @if (isset($submenu->icon))
                <i class="{{ $submenu->icon }}"></i>
              @endif
              <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
              @if (isset($submenu->url) && $submenu->url == 'programs.index' && auth()->user()->hasPermissionTo('Program Changes Checker') && $pending_program_configurations_count > 0)
                <span class="badge bg-danger rounded-pill mx-1" title="Program Changes Require Approval">{{ $pending_program_configurations_count }}</span>
              @endif
              @if (isset($submenu->url) && $submenu->url == 'programs.drafts' && auth()->user()->hasPermissionTo('Add/Edit Program & Mapping'))
                <span class="badge bg-danger rounded-pill mx-1">{{ $program_drafts }}</span>
              @endif
              @if (isset($submenu->url) && $submenu->url == 'companies.index' && auth()->user()->hasAnyPermission(['Company Changes Checker', 'Activate/Deactivate Companies']) && ($company_changes_count > 0))
                <span class="badge bg-danger rounded-pill mx-1" title="Company Changes Require Approval/Company Created Requires Approval">{{ $company_changes_count }}</span>
              @elseif(isset($submenu->url) && $submenu->url == 'companies.drafts' && auth()->user()->hasPermissionTo('Add/Edit Companies'))
                <span class="badge bg-danger rounded-pill mx-1">{{ $drafts }}</span>
              @endif
              @if (isset($submenu->url) && $submenu->url == 'configurations.pending' && $pending_configurations > 0)
                <span class="badge bg-danger rounded-pill mx-1">{{ $pending_configurations }}</span>
              @endif
              @if (isset($submenu->url) && $submenu->url == 'configurations.users' && ($pending_role_changes > 0 || $pending_role_approvals > 0))
                <span class="badge bg-danger rounded-pill mx-1">{{ $pending_role_changes + $pending_role_approvals }}</span>
              @endif
              @if (isset($submenu->url) && $submenu->url == 'configurations.users' && auth()->user()->hasPermissionTo('Add/Edit Users') && $pending_user_changes > 0)
                <span class="badge bg-danger rounded-pill mx-1">{{ $pending_user_changes }}</span>
              @endif
            </a>
          @endcanany
        @else
          <a href="{{ isset($submenu->url) ? route($submenu->url, ['bank' => request()->route('bank')]) : 'javascript:void(0)' }}" class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
            @if (isset($submenu->icon))
              <i class="{{ $submenu->icon }}"></i>
            @endif
            <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
          </a>
        @endif

        {{-- submenu --}}
        @if (isset($submenu->submenu))
          @include('layouts.sections.menu.bank-submenu',['menu' => $submenu->submenu])
        @endif
      </li>
    @endforeach
  @endif
</ul>
