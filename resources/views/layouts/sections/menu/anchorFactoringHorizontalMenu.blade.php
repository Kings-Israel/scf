@php
$configData = Helper::appClasses();
$current_company = auth()->user()->activeFactoringCompany()->first();
$company = App\Models\Company::find($current_company->company_id);
$has_factoring_programs = $company->has_factoring_programs ? true : false;
$factoring_links = ['Dashboard', 'Purchase Orders', 'Invoices', 'All Invoices', 'Fund Planner', 'Financing Requests', 'Create Invoice (Factoring)', 'Payments', 'Expired Invoices', 'Reports', 'All Invoices Report', 'Programs Report', 'Payments Report', 'Settings'];
// $factoring_links = ['Dashboard', 'Purchase Orders', 'Invoices', 'All Invoices', 'Fund Planner', 'Financing Requests', 'Create Invoice (Factoring)', 'Payments', 'Expired Invoices'];
$has_dealer_financing_programs = $company->has_dealer_financing_programs ? true : false;
$dealer_financing_links = ['Dashboard', 'Invoices', 'All Invoices', 'Drawdown', 'Create Invoice (Dealer Financing)', 'OD Accounts', 'Dealer Payment Instructions', 'Dealer DPD Invoices', 'Dealer Rejected Invoices', 'Reports', 'All Invoices Report', 'Dealer Programs Report', 'Payments Report', 'Settings'];
// $dealer_financing_links = ['Dashboard', 'Invoices', 'All Invoices', 'Drawdown', 'Create Invoice (Dealer Financing)', 'OD Accounts', 'Dealer Payment Instructions', 'Dealer DPD Invoices', 'Dealer Rejected Invoices'];
$proposed_updates = $company->invoiceSetting?->proposedUpdate?->where('user_id', '!=', auth()->id())->count();
@endphp
<!-- Horizontal Menu -->
<aside id="layout-menu" class="layout-menu-horizontal menu-horizontal menu bg-menu-theme flex-grow-0">
  <div class="{{$containerNav}} d-flex h-100">
    <ul class="menu-inner">
      @foreach ($menuData[3]->menu as $menu)

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
          }
          else{
            if (str_contains($currentRouteName,$menu->slug) and strpos($currentRouteName,$menu->slug) === 0) {
              $activeClass = 'active';
            }
          }
        }
      @endphp

      <li class="menu-item {{$activeClass}}">
        @if (isset($menu->permissions))
          @canany($menu->permissions)
            <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0);' }}" class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($menu->target) and !empty($menu->target)) target="_blank" @endif>
              @isset($menu->icon)
              <i class="{{ $menu->icon }}"></i>
              @endisset
              <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
              @if (isset($menu->name) && $menu->name == 'Settings' && $proposed_updates > 0)
                <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
              @endif
            </a>
          @endcanany
        @else
          <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0);' }}" class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($menu->target) and !empty($menu->target)) target="_blank" @endif>
            @isset($menu->icon)
            <i class="{{ $menu->icon }}"></i>
            @endisset
            <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
            @if (isset($menu->name) && $menu->name == 'Settings' && $proposed_updates > 0)
              <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Pending Changes awating approval"></i>
            @endif
          </a>
        @endif

        @isset($menu->submenu)
          @include('layouts.sections.menu.submenu',['menu' => $menu->submenu, 'factoring_links' => $factoring_links, 'dealer_financing_links' => $dealer_financing_links])
        @endisset
      </li>
      @endforeach
    </ul>
  </div>
</aside>
<!--/ Horizontal Menu -->
