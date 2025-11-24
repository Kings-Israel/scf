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

      @if (isset($submenu->name) && ((isset($factoring_links) && collect($factoring_links)->contains($submenu->name)) || (isset($dealer_financing_links) && collect($dealer_financing_links)->contains($submenu->name))))
        @if(isset($submenu->name) && collect($factoring_links)->contains($submenu->name) && $has_factoring_programs)
          <li class="menu-item {{$activeClass}}">
            @if (isset($submenu->permissions))
              @canany($submenu->permissions)
                <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}" class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
                  @if (isset($submenu->icon))
                    <i class="{{ $submenu->icon }}"></i>
                  @endif
                  <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
                  @if (isset($menu->url) && $menu->url == 'programs.index' && auth()->user()->hasPermissionTo('Program Changes Checker') && $program_changes_count > 0)
                    <span class="badge bg-danger rounded-pill mx-1" title="Program Changes Require Approval">{{ $program_changes_count }}</span>
                  @elseif (isset($submenu->url) && $submenu->url == 'programs.drafts' && auth()->user()->hasPermissionTo('Add/Edit Program & Mapping'))
                    <span class="badge bg-danger rounded-pill mx-1">{{ $program_drafts }}</span>
                  @endif
                  @if (isset($submenu->url) && $submenu->url == 'companies.index' && auth()->user()->hasAnyPermission(['Company Changes Checker', 'Activate/Deactivate Companies']) && ($company_changes_count > 0 || $company_approvals_count > 0))
                    <span class="badge bg-danger rounded-pill mx-1" title="Company Changes Require Approval/Company Created Requires Approval">{{ $company_changes_count + $company_approvals_count }}</span>
                  @elseif(isset($submenu->url) && $submenu->url == 'companies.drafts' && auth()->user()->hasPermissionTo('Add/Edit Companies'))
                    <span class="badge bg-danger rounded-pill mx-1">{{ $drafts }}</span>
                  @elseif (isset($submenu->name) && $submenu->slug == 'anchor.configurations-general' && $proposed_updates > 0)
                    <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
                  @endif
                  @if (isset($submenu->url) && ($submenu->slug == 'anchor.configurations-vendor' || $submenu->slug == 'buyer.configurations.vendors') && $config_change)
                    <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
                  @endif
                </a>
              @endcanany
            @else
              <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}" class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
                @if (isset($submenu->icon))
                  <i class="{{ $submenu->icon }}"></i>
                @endif
                <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
                @if (isset($menu->url) && $menu->url == 'programs.index' && auth()->user()->hasPermissionTo('Program Changes Checker') && $program_changes_count > 0)
                  <span class="badge bg-danger rounded-pill mx-1" title="Program Changes Require Approval">{{ $program_changes_count }}</span>
                @elseif (isset($submenu->url) && $submenu->url == 'programs.drafts' && auth()->user()->hasPermissionTo('Add/Edit Program & Mapping'))
                  <span class="badge bg-danger rounded-pill mx-1">{{ $program_drafts }}</span>
                @endif
                @if (isset($submenu->url) && $submenu->url == 'companies.index' && auth()->user()->hasAnyPermission(['Company Changes Checker', 'Activate/Deactivate Companies']) && ($company_changes_count > 0 || $company_approvals_count > 0))
                  <span class="badge bg-danger rounded-pill mx-1" title="Company Changes Require Approval/Company Created Requires Approval">{{ $company_changes_count + $company_approvals_count }}</span>
                @elseif(isset($submenu->url) && $submenu->url == 'companies.drafts' && auth()->user()->hasPermissionTo('Add/Edit Companies'))
                  <span class="badge bg-danger rounded-pill mx-1">{{ $drafts }}</span>
                @elseif (isset($submenu->name) && $submenu->slug == 'anchor.configurations-general' && $proposed_updates > 0)
                  <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
                @endif
                @if (isset($submenu->url) && ($submenu->slug == 'anchor.configurations-vendor' || $submenu->slug == 'buyer.configurations.vendors') && $config_change)
                  <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
                @endif
              </a>
            @endif

            @if (isset($submenu->submenu))
              @include('layouts.sections.menu.submenu',['menu' => $submenu->submenu])
            @endif
          </li>
        @elseif(isset($submenu->name) && collect($dealer_financing_links)->contains($submenu->name) && $has_dealer_financing_programs)
          <li class="menu-item {{$activeClass}}">
            @if (isset($submenu->permissions))
              @canany($submenu->permissions)
                <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}" class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
                  @if (isset($submenu->icon))
                    <i class="{{ $submenu->icon }}"></i>
                  @endif
                  <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
                  @if (isset($menu->url) && $menu->url == 'programs.index' && auth()->user()->hasPermissionTo('Program Changes Checker') && $program_changes_count > 0)
                    <span class="badge bg-danger rounded-pill mx-1" title="Program Changes Require Approval">{{ $program_changes_count }}</span>
                  @elseif (isset($submenu->url) && $submenu->url == 'programs.drafts' && auth()->user()->hasPermissionTo('Add/Edit Program & Mapping'))
                    <span class="badge bg-danger rounded-pill mx-1">{{ $program_drafts }}</span>
                  @endif
                  @if (isset($submenu->url) && $submenu->url == 'companies.index' && auth()->user()->hasAnyPermission(['Company Changes Checker', 'Activate/Deactivate Companies']) && ($company_changes_count > 0 || $company_approvals_count > 0))
                    <span class="badge bg-danger rounded-pill mx-1" title="Company Changes Require Approval/Company Created Requires Approval">{{ $company_changes_count + $company_approvals_count }}</span>
                  @elseif(isset($submenu->url) && $submenu->url == 'companies.drafts' && auth()->user()->hasPermissionTo('Add/Edit Companies'))
                    <span class="badge bg-danger rounded-pill mx-1">{{ $drafts }}</span>
                  @elseif (isset($submenu->name) && $submenu->slug == 'anchor.configurations-general' && $proposed_updates > 0)
                    <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
                  @endif
                  @if (isset($submenu->url) && ($submenu->slug == 'anchor.configurations-vendor' || $submenu->slug == 'buyer.configurations.vendors') && $config_change)
                    <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
                  @endif
                </a>
              @endcanany
            @else
              <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}" class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
                @if (isset($submenu->icon))
                  <i class="{{ $submenu->icon }}"></i>
                @endif
                <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
                @if (isset($menu->url) && $menu->url == 'programs.index' && auth()->user()->hasPermissionTo('Program Changes Checker') && $program_changes_count > 0)
                  <span class="badge bg-danger rounded-pill mx-1" title="Program Changes Require Approval">{{ $program_changes_count }}</span>
                @elseif (isset($submenu->url) && $submenu->url == 'programs.drafts' && auth()->user()->hasPermissionTo('Add/Edit Program & Mapping'))
                  <span class="badge bg-danger rounded-pill mx-1">{{ $program_drafts }}</span>
                @endif
                @if (isset($submenu->url) && $submenu->url == 'companies.index' && auth()->user()->hasAnyPermission(['Company Changes Checker', 'Activate/Deactivate Companies']) && ($company_changes_count > 0 || $company_approvals_count > 0))
                  <span class="badge bg-danger rounded-pill mx-1" title="Company Changes Require Approval/Company Created Requires Approval">{{ $company_changes_count + $company_approvals_count }}</span>
                @elseif(isset($submenu->url) && $submenu->url == 'companies.drafts' && auth()->user()->hasPermissionTo('Add/Edit Companies'))
                  <span class="badge bg-danger rounded-pill mx-1">{{ $drafts }}</span>
                @elseif (isset($submenu->name) && $submenu->slug == 'anchor.configurations-general' && $proposed_updates > 0)
                  <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
                @endif
                @if (isset($submenu->url) && ($submenu->slug == 'anchor.configurations-vendor' || $submenu->slug == 'buyer.configurations.vendors') && $config_change)
                  <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
                @endif
              </a>

              @if (isset($submenu->submenu))
                @include('layouts.sections.menu.submenu',['menu' => $submenu->submenu])
              @endif
            @endif
          </li>
        @endif
      @else
        <li class="menu-item {{$activeClass}}">
          @if (isset($submenu->permissions))
            @canany($submenu->permissions)
              <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}" class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
                @if (isset($submenu->icon))
                  <i class="{{ $submenu->icon }}"></i>
                @endif
                <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
                @if (isset($menu->url) && $menu->url == 'programs.index' && auth()->user()->hasPermissionTo('Program Changes Checker') && $program_changes_count > 0)
                  <span class="badge bg-danger rounded-pill mx-1" title="Program Changes Require Approval">{{ $program_changes_count }}</span>
                @elseif (isset($submenu->url) && $submenu->url == 'programs.drafts' && auth()->user()->hasPermissionTo('Add/Edit Program & Mapping'))
                  <span class="badge bg-danger rounded-pill mx-1">{{ $program_drafts }}</span>
                @endif
                @if (isset($submenu->url) && $submenu->url == 'companies.index' && auth()->user()->hasAnyPermission(['Company Changes Checker', 'Activate/Deactivate Companies']) && ($company_changes_count > 0 || $company_approvals_count > 0))
                  <span class="badge bg-danger rounded-pill mx-1" title="Company Changes Require Approval/Company Created Requires Approval">{{ $company_changes_count + $company_approvals_count }}</span>
                @elseif(isset($submenu->url) && $submenu->url == 'companies.drafts' && auth()->user()->hasPermissionTo('Add/Edit Companies'))
                  <span class="badge bg-danger rounded-pill mx-1">{{ $drafts }}</span>
                @elseif (isset($submenu->name) && $submenu->slug == 'anchor.configurations-general' && $proposed_updates > 0)
                  <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
                @endif
                @if (isset($submenu->url) && ($submenu->slug == 'anchor.configurations-vendor' || $submenu->slug == 'buyer.configurations.vendors') && $config_change)
                  <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
                @endif
              </a>
            @endcanany
          @else
            <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}" class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
              @if (isset($submenu->icon))
                <i class="{{ $submenu->icon }}"></i>
              @endif
              <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
              @if (isset($menu->url) && $menu->url == 'programs.index' && auth()->user()->hasPermissionTo('Program Changes Checker') && $program_changes_count > 0)
                <span class="badge bg-danger rounded-pill mx-1" title="Program Changes Require Approval">{{ $program_changes_count }}</span>
              @elseif (isset($submenu->url) && $submenu->url == 'programs.drafts' && auth()->user()->hasPermissionTo('Add/Edit Program & Mapping'))
                <span class="badge bg-danger rounded-pill mx-1">{{ $program_drafts }}</span>
              @endif
              @if (isset($submenu->url) && $submenu->url == 'companies.index' && auth()->user()->hasAnyPermission(['Company Changes Checker', 'Activate/Deactivate Companies']) && ($company_changes_count > 0 || $company_approvals_count > 0))
                <span class="badge bg-danger rounded-pill mx-1" title="Company Changes Require Approval/Company Created Requires Approval">{{ $company_changes_count + $company_approvals_count }}</span>
              @elseif(isset($submenu->url) && $submenu->url == 'companies.drafts' && auth()->user()->hasPermissionTo('Add/Edit Companies'))
                <span class="badge bg-danger rounded-pill mx-1">{{ $drafts }}</span>
              @elseif (isset($submenu->name) && $submenu->slug == 'anchor.configurations-general' && $proposed_updates > 0)
                <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
              @endif
              @if (isset($submenu->url) && ($submenu->slug == 'anchor.configurations-vendor' || $submenu->slug == 'buyer.configurations.vendors') && $config_change)
                <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
              @endif
            </a>
          @endif
        </li>
      @endif

      {{-- <li class="menu-item {{$activeClass}}">
        @if (isset($submenu->permissions))
          @canany($submenu->permissions)
            @if(isset($submenu->name) && isset($factoring_links) && collect($factoring_links)->contains($submenu->name) && $has_factoring_programs)
              <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}" class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
                @if (isset($submenu->icon))
                  <i class="{{ $submenu->icon }}"></i>
                @endif
                <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
                @if (isset($menu->url) && $menu->url == 'programs.index' && auth()->user()->hasPermissionTo('Program Changes Checker') && $program_changes_count > 0)
                  <span class="badge bg-danger rounded-pill mx-1" title="Program Changes Require Approval">{{ $program_changes_count }}</span>
                @elseif (isset($submenu->url) && $submenu->url == 'programs.drafts' && auth()->user()->hasPermissionTo('Add/Edit Program & Mapping'))
                  <span class="badge bg-danger rounded-pill mx-1">{{ $program_drafts }}</span>
                @endif
                @if (isset($submenu->url) && $submenu->url == 'companies.index' && auth()->user()->hasAnyPermission(['Company Changes Checker', 'Activate/Deactivate Companies']) && ($company_changes_count > 0 || $company_approvals_count > 0))
                  <span class="badge bg-danger rounded-pill mx-1" title="Company Changes Require Approval/Company Created Requires Approval">{{ $company_changes_count + $company_approvals_count }}</span>
                @elseif(isset($submenu->url) && $submenu->url == 'companies.drafts' && auth()->user()->hasPermissionTo('Add/Edit Companies'))
                  <span class="badge bg-danger rounded-pill mx-1">{{ $drafts }}</span>
                @elseif (isset($submenu->name) && $submenu->slug == 'anchor.configurations-general' && $proposed_updates > 0)
                  <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
                @endif
                @if (isset($submenu->url) && ($submenu->slug == 'anchor.configurations-vendor' || $submenu->slug == 'buyer.configurations.vendors') && $config_change)
                  <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
                @endif
              </a>
            @endif
            @if(isset($submenu->name) && isset($dealer_financing_links) && collect($dealer_financing_links)->contains($submenu->name) && $has_dealer_financing_programs)
              <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}" class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
                @if (isset($submenu->icon))
                  <i class="{{ $submenu->icon }}"></i>
                @endif
                <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
                @if (isset($menu->url) && $menu->url == 'programs.index' && auth()->user()->hasPermissionTo('Program Changes Checker') && $program_changes_count > 0)
                  <span class="badge bg-danger rounded-pill mx-1" title="Program Changes Require Approval">{{ $program_changes_count }}</span>
                @elseif (isset($submenu->url) && $submenu->url == 'programs.drafts' && auth()->user()->hasPermissionTo('Add/Edit Program & Mapping'))
                  <span class="badge bg-danger rounded-pill mx-1">{{ $program_drafts }}</span>
                @endif
                @if (isset($submenu->url) && $submenu->url == 'companies.index' && auth()->user()->hasAnyPermission(['Company Changes Checker', 'Activate/Deactivate Companies']) && ($company_changes_count > 0 || $company_approvals_count > 0))
                  <span class="badge bg-danger rounded-pill mx-1" title="Company Changes Require Approval/Company Created Requires Approval">{{ $company_changes_count + $company_approvals_count }}</span>
                @elseif(isset($submenu->url) && $submenu->url == 'companies.drafts' && auth()->user()->hasPermissionTo('Add/Edit Companies'))
                  <span class="badge bg-danger rounded-pill mx-1">{{ $drafts }}</span>
                @elseif (isset($submenu->name) && $submenu->slug == 'anchor.configurations-general' && $proposed_updates > 0)
                  <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
                @endif
                @if (isset($submenu->url) && ($submenu->slug == 'anchor.configurations-vendor' || $submenu->slug == 'buyer.configurations.vendors') && $config_change)
                  <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
                @endif
              </a>
            @endif
            @if (isset($submenu->name) && (isset($factoring_links) || isset($dealer_financing_links)) && (!collect($factoring_links)->contains($submenu->name) && !collect($dealer_financing_links)->contains($submenu->name)))
              <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}" class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
                @if (isset($submenu->icon))
                  <i class="{{ $submenu->icon }}"></i>
                @endif
                <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
                @if (isset($menu->url) && $menu->url == 'programs.index' && auth()->user()->hasPermissionTo('Program Changes Checker') && $program_changes_count > 0)
                  <span class="badge bg-danger rounded-pill mx-1" title="Program Changes Require Approval">{{ $program_changes_count }}</span>
                @elseif (isset($submenu->url) && $submenu->url == 'programs.drafts' && auth()->user()->hasPermissionTo('Add/Edit Program & Mapping'))
                  <span class="badge bg-danger rounded-pill mx-1">{{ $program_drafts }}</span>
                @endif
                @if (isset($submenu->url) && $submenu->url == 'companies.index' && auth()->user()->hasAnyPermission(['Company Changes Checker', 'Activate/Deactivate Companies']) && ($company_changes_count > 0 || $company_approvals_count > 0))
                  <span class="badge bg-danger rounded-pill mx-1" title="Company Changes Require Approval/Company Created Requires Approval">{{ $company_changes_count + $company_approvals_count }}</span>
                @elseif(isset($submenu->url) && $submenu->url == 'companies.drafts' && auth()->user()->hasPermissionTo('Add/Edit Companies'))
                  <span class="badge bg-danger rounded-pill mx-1">{{ $drafts }}</span>
                @elseif (isset($submenu->name) && $submenu->slug == 'anchor.configurations-general' && $proposed_updates > 0)
                  <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
                @endif
                @if (isset($submenu->url) && ($submenu->slug == 'anchor.configurations-vendor' || $submenu->slug == 'buyer.configurations.vendors') && $config_change)
                  <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
                @endif
              </a>
            @endif
            @if(isset($submenu->name) && (!isset($factoring_links) || !isset($dealer_financing_links)))
              <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}" class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
                @if (isset($submenu->icon))
                  <i class="{{ $submenu->icon }}"></i>
                @endif
                <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
                @if (isset($menu->url) && $menu->url == 'programs.index' && auth()->user()->hasPermissionTo('Program Changes Checker') && $program_changes_count > 0)
                  <span class="badge bg-danger rounded-pill mx-1" title="Program Changes Require Approval">{{ $program_changes_count }}</span>
                @elseif (isset($submenu->url) && $submenu->url == 'programs.drafts' && auth()->user()->hasPermissionTo('Add/Edit Program & Mapping'))
                  <span class="badge bg-danger rounded-pill mx-1">{{ $program_drafts }}</span>
                @endif
                @if (isset($submenu->url) && $submenu->url == 'companies.index' && auth()->user()->hasAnyPermission(['Company Changes Checker', 'Activate/Deactivate Companies']) && ($company_changes_count > 0 || $company_approvals_count > 0))
                  <span class="badge bg-danger rounded-pill mx-1" title="Company Changes Require Approval/Company Created Requires Approval">{{ $company_changes_count + $company_approvals_count }}</span>
                @elseif(isset($submenu->url) && $submenu->url == 'companies.drafts' && auth()->user()->hasPermissionTo('Add/Edit Companies'))
                  <span class="badge bg-danger rounded-pill mx-1">{{ $drafts }}</span>
                @elseif (isset($submenu->name) && $submenu->slug == 'anchor.configurations-general' && $proposed_updates > 0)
                  <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
                @endif
                @if (isset($submenu->url) && ($submenu->slug == 'anchor.configurations-vendor' || $submenu->slug == 'buyer.configurations.vendors') && $config_change)
                  <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
                @endif
              </a>
            @endif
          @endcanany
        @else
          @if(isset($submenu->name) && isset($factoring_links) && collect($factoring_links)->contains($submenu->name) && $has_factoring_programs)
            <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}" class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
              @if (isset($submenu->icon))
                <i class="{{ $submenu->icon }}"></i>
              @endif
              <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
              @if (isset($menu->url) && $menu->url == 'programs.index' && auth()->user()->hasPermissionTo('Program Changes Checker') && $program_changes_count > 0)
                <span class="badge bg-danger rounded-pill mx-1" title="Program Changes Require Approval">{{ $program_changes_count }}</span>
              @elseif (isset($submenu->url) && $submenu->url == 'programs.drafts' && auth()->user()->hasPermissionTo('Add/Edit Program & Mapping'))
                <span class="badge bg-danger rounded-pill mx-1">{{ $program_drafts }}</span>
              @endif
              @if (isset($submenu->url) && $submenu->url == 'companies.index' && auth()->user()->hasAnyPermission(['Company Changes Checker', 'Activate/Deactivate Companies']) && ($company_changes_count > 0 || $company_approvals_count > 0))
                <span class="badge bg-danger rounded-pill mx-1" title="Company Changes Require Approval/Company Created Requires Approval">{{ $company_changes_count + $company_approvals_count }}</span>
              @elseif(isset($submenu->url) && $submenu->url == 'companies.drafts' && auth()->user()->hasPermissionTo('Add/Edit Companies'))
                <span class="badge bg-danger rounded-pill mx-1">{{ $drafts }}</span>
              @elseif (isset($submenu->name) && $submenu->slug == 'anchor.configurations-general' && $proposed_updates > 0)
                <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
              @endif
              @if (isset($submenu->url) && ($submenu->slug == 'anchor.configurations-vendor' || $submenu->slug == 'buyer.configurations.vendors') && $config_change)
                <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
              @endif
            </a>
          @endif
          @if(isset($submenu->name) && isset($dealer_financing_links) && collect($dealer_financing_links)->contains($submenu->name) && $has_dealer_financing_programs)
            <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}" class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
              @if (isset($submenu->icon))
                <i class="{{ $submenu->icon }}"></i>
              @endif
              <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
              @if (isset($menu->url) && $menu->url == 'programs.index' && auth()->user()->hasPermissionTo('Program Changes Checker') && $program_changes_count > 0)
                <span class="badge bg-danger rounded-pill mx-1" title="Program Changes Require Approval">{{ $program_changes_count }}</span>
              @elseif (isset($submenu->url) && $submenu->url == 'programs.drafts' && auth()->user()->hasPermissionTo('Add/Edit Program & Mapping'))
                <span class="badge bg-danger rounded-pill mx-1">{{ $program_drafts }}</span>
              @endif
              @if (isset($submenu->url) && $submenu->url == 'companies.index' && auth()->user()->hasAnyPermission(['Company Changes Checker', 'Activate/Deactivate Companies']) && ($company_changes_count > 0 || $company_approvals_count > 0))
                <span class="badge bg-danger rounded-pill mx-1" title="Company Changes Require Approval/Company Created Requires Approval">{{ $company_changes_count + $company_approvals_count }}</span>
              @elseif(isset($submenu->url) && $submenu->url == 'companies.drafts' && auth()->user()->hasPermissionTo('Add/Edit Companies'))
                <span class="badge bg-danger rounded-pill mx-1">{{ $drafts }}</span>
              @elseif (isset($submenu->name) && $submenu->slug == 'anchor.configurations-general' && $proposed_updates > 0)
                <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
              @endif
              @if (isset($submenu->url) && ($submenu->slug == 'anchor.configurations-vendor' || $submenu->slug == 'buyer.configurations.vendors') && $config_change)
                <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
              @endif
            </a>
          @endif
          @if (isset($submenu->name) && (isset($factoring_links) || isset($dealer_financing_links)) && (!collect($factoring_links)->contains($submenu->name) && !collect($dealer_financing_links)->contains($submenu->name)))
            <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}" class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
              @if (isset($submenu->icon))
                <i class="{{ $submenu->icon }}"></i>
              @endif
              <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
              @if (isset($menu->url) && $menu->url == 'programs.index' && auth()->user()->hasPermissionTo('Program Changes Checker') && $program_changes_count > 0)
                <span class="badge bg-danger rounded-pill mx-1" title="Program Changes Require Approval">{{ $program_changes_count }}</span>
              @elseif (isset($submenu->url) && $submenu->url == 'programs.drafts' && auth()->user()->hasPermissionTo('Add/Edit Program & Mapping'))
                <span class="badge bg-danger rounded-pill mx-1">{{ $program_drafts }}</span>
              @endif
              @if (isset($submenu->url) && $submenu->url == 'companies.index' && auth()->user()->hasAnyPermission(['Company Changes Checker', 'Activate/Deactivate Companies']) && ($company_changes_count > 0 || $company_approvals_count > 0))
                <span class="badge bg-danger rounded-pill mx-1" title="Company Changes Require Approval/Company Created Requires Approval">{{ $company_changes_count + $company_approvals_count }}</span>
              @elseif(isset($submenu->url) && $submenu->url == 'companies.drafts' && auth()->user()->hasPermissionTo('Add/Edit Companies'))
                <span class="badge bg-danger rounded-pill mx-1">{{ $drafts }}</span>
              @elseif (isset($submenu->name) && $submenu->slug == 'anchor.configurations-general' && $proposed_updates > 0)
                <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
              @endif
              @if (isset($submenu->url) && ($submenu->slug == 'anchor.configurations-vendor' || $submenu->slug == 'buyer.configurations.vendors') && $config_change)
                <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
              @endif
            </a>
          @endif
          @if(isset($submenu->name) && (!isset($factoring_links) || !isset($dealer_financing_links)))
            <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}" class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
              @if (isset($submenu->icon))
                <i class="{{ $submenu->icon }}"></i>
              @endif
              <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
              @if (isset($menu->url) && $menu->url == 'programs.index' && auth()->user()->hasPermissionTo('Program Changes Checker') && $program_changes_count > 0)
                <span class="badge bg-danger rounded-pill mx-1" title="Program Changes Require Approval">{{ $program_changes_count }}</span>
              @elseif (isset($submenu->url) && $submenu->url == 'programs.drafts' && auth()->user()->hasPermissionTo('Add/Edit Program & Mapping'))
                <span class="badge bg-danger rounded-pill mx-1">{{ $program_drafts }}</span>
              @endif
              @if (isset($submenu->url) && $submenu->url == 'companies.index' && auth()->user()->hasAnyPermission(['Company Changes Checker', 'Activate/Deactivate Companies']) && ($company_changes_count > 0 || $company_approvals_count > 0))
                <span class="badge bg-danger rounded-pill mx-1" title="Company Changes Require Approval/Company Created Requires Approval">{{ $company_changes_count + $company_approvals_count }}</span>
              @elseif(isset($submenu->url) && $submenu->url == 'companies.drafts' && auth()->user()->hasPermissionTo('Add/Edit Companies'))
                <span class="badge bg-danger rounded-pill mx-1">{{ $drafts }}</span>
              @elseif (isset($submenu->name) && $submenu->slug == 'anchor.configurations-general' && $proposed_updates > 0)
                <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
              @endif
              @if (isset($submenu->url) && ($submenu->slug == 'anchor.configurations-vendor' || $submenu->slug == 'buyer.configurations.vendors') && $config_change)
                <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
              @endif
            </a>
          @endif
        @endif

        @if (isset($submenu->submenu))
          @include('layouts.sections.menu.submenu',['menu' => $submenu->submenu])
        @endif
      </li> --}}
    @endforeach
  @endif
</ul>
