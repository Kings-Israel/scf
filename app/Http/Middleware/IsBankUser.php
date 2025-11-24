<?php

namespace App\Http\Middleware;

use App\Models\Bank;
use App\Models\BankUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsBankUser
{
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
   * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
   */
  public function handle(Request $request, Closure $next)
  {
    // Check if is bank user
    $bank_user = BankUser::where([
      'user_id' => $request->user()->id,
      'bank_id' => $request->route('bank')->id,
    ])->first();

    if (!$bank_user) {
      Auth::logout();
      return redirect()->route('login');
    }

    return $next($request);
  }
}
