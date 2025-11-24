@php
$configData = Helper::appClasses();
$pending_program_configurations_count = request()->route('bank')->pending_program_configurations_count;
// $program_changes_count = App\Models\Program::where('bank_id', request()->route('bank')->id)->where(fn ($query) => $query->whereHas('proposedUpdate')->orWhere('status', 'pending'))->where('is_published', true)->count();
$program_drafts = App\Models\Program::where('bank_id', request()->route('bank')->id)->where('publisher_type', App\Models\User::class)->onlyDrafts()->count();
$company_user_changes_count = App\Models\Bank::find(request()->route('bank')->id)->company_user_changes_count;
$company_changes_count = App\Models\Company::where('bank_id', request()->route('bank')->id)->where(fn ($query) => $query->whereHas('proposedUpdate')->orWhere('approval_status', 'pending'))->where('is_published', true)->count() + $company_user_changes_count;
$drafts = App\Models\Company::where('bank_id', request()->route('bank')->id)->where('publisher_type', App\Models\User::class)->onlyDrafts()->count();
$pending_configurations = App\Models\ProposedConfigurationChange::where('modeable_type', App\Models\Bank::class)->where('modeable_id', request()->route('bank')->id)->count();
$pending_user_changes = App\Models\UserChange::whereIn('user_id', request()->route('bank')->users->pluck('id'))->count();
$pending_role_changes = App\Models\RoleChange::whereHas('permissionData', fn ($query) => $query->where('bank_id', request()->route('bank')->id))->count();
$pending_role_approvals = App\Models\PermissionData::where('bank_id', request()->route('bank')->id)->where('status', 'pending')->count();
@endphp
<!-- Horizontal Menu -->
<aside id="layout-menu" class="layout-menu-horizontal menu-horizontal menu bg-menu-theme flex-grow-0">
  <div class="{{$containerNav}} d-flex h-100">
    <ul class="menu-inner">
      @foreach ($menuData[1]->menu as $menu)

      {{-- active menu method --}}
      @php
        $activeClass = null;
        $currentRouteName =  Route::currentRouteName();

        if ($currentRouteName === $menu->slug) {
          $activeClass = 'active';
        }
        elseif (isset($menu->submenu)) {
          if (gettype($menu->slug) === 'array') {
            foreach($menu->slug as $slug){
              if (str_contains($currentRouteName,$slug) and strpos($currentRouteName,$slug) === 0) {
                $activeClass = 'active';
              }
            }
          } else {
            if (str_contains($currentRouteName,$menu->slug) and strpos($currentRouteName,$menu->slug) === 0) {
              $activeClass = 'active';
            }
          }
        }
      @endphp

      {{-- main menu --}}
      <li class="menu-item {{$activeClass}}">
        @if (isset($menu->permissions))
          @canany($menu->permissions)
            <a href="{{ isset($menu->url) ? route($menu->url, ['bank' => request()->route('bank')]) : 'javascript:void(0);' }}" class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($menu->target) and !empty($menu->target)) target="_blank" @endif>
              @isset($menu->icon)
                <i class="{{ $menu->icon }}"></i>
              @endisset
              <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
              @if (isset($menu->name) && $menu->name == 'Programs' && auth()->user()->hasPermissionTo('Program Changes Checker') && ($pending_program_configurations_count > 0 || $program_drafts > 0))
                <span class="badge bg-danger rounded-pill mx-1" title="Program Changes Require Approval">{{ $pending_program_configurations_count + $program_drafts }}</span>
              @endif
              @if (isset($menu->name) && $menu->name == 'Companies' && auth()->user()->hasAnyPermission(['Company Changes Checker', 'Activate/Deactivate Companies', 'Add/Edit Companies']) && ($company_changes_count > 0 || $drafts))
                <span class="badge bg-danger rounded-pill mx-1" title="Company Changes Require Approval/Company Created Requires Approval">{{ $company_changes_count + $drafts }}</span>
              @endif
              @if (isset($menu->name) && $menu->name == 'Configurations' && ($pending_configurations > 0 || $pending_user_changes > 0 || $pending_role_changes > 0 || $pending_role_approvals > 0))
                <span class="badge bg-danger rounded-pill mx-1">{{ $pending_configurations + $pending_user_changes + $pending_role_changes + $pending_role_approvals }}</span>
              @endif
            </a>
          @endcanany
        @else
          <a href="{{ isset($menu->url) ? route($menu->url, ['bank' => request()->route('bank')]) : 'javascript:void(0);' }}" class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($menu->target) and !empty($menu->target)) target="_blank" @endif>
            @isset($menu->icon)
            <i class="{{ $menu->icon }}"></i>
            @endisset
            <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
          </a>
        @endif

        {{-- submenu --}}
        @isset($menu->submenu)
          @include('layouts.sections.menu.bank-submenu',['menu' => $menu->submenu])
        @endisset
      </li>
      @endforeach
    </ul>
  </div>
</aside>
<!--/ Horizontal Menu -->
