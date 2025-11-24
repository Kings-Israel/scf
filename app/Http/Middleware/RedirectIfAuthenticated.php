<?php

namespace App\Http\Middleware;

use App\Models\Bank;
use App\Models\BankUser;
use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
   * @param  string|null  ...$guards
   * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
   */
  public function handle(Request $request, Closure $next, ...$guards)
  {
    $guards = empty($guards) ? [null] : $guards;

    foreach ($guards as $guard) {
      if (Auth::guard($guard)->check()) {
        $bank_user = BankUser::where('user_id', auth()->id())->where('active', true)->first();

        // Check if user has an anchor company
        $anchor_companies = auth()->user()->companies()->anchor()->count();
        $factoring_companies = auth()->user()->companies()->anchorFactoring()->count();
        $vendor_companies = auth()->user()->companies()->vendor()->count();
        $dealer_financing = auth()->user()->companies()->dealerFinancing()->count();
        $buyer_companies = auth()->user()->companies()->buyerFactoring()->count();
        $dealer_companies = auth()->user()->companies()->buyer()->count();

        $link = '';
        if ($bank_user) {
          $bank = Bank::where('id', $bank_user->bank_id)->first();
          $link = redirect()->route('bank.dashboard', ['bank' => $bank]);
        }

        if ($anchor_companies > 0) {
          $link = redirect()->route('anchor.dashboard');
        } elseif ($factoring_companies > 0 || $dealer_financing > 0) {
          $link = redirect()->route('anchor.factoring-dashboard');
        }

        if ($vendor_companies > 0) {
          $link = redirect()->route('vendor.dashboard');
        }

        if ($buyer_companies) {
          $link = redirect()->route('buyer.dashboard');
        }

        if ($dealer_companies) {
          $link = redirect()->route('dealer.dashboard');
        }

        if ($link == '') {
          Auth::guard($guard)->logout();

          $request->session()->invalidate();

          $request->session()->regenerateToken();

          $link = redirect()->route('login');
        }

        return $link;
      }
    }

    return $next($request);
  }
}
