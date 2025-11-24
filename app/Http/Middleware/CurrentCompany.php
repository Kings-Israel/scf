<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\UserCurrentCompany;

class CurrentCompany
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
    $exists = UserCurrentCompany::where(['user_id' => auth()->id(), 'platform' => 'anchor factoring'])->first();

    if (!$exists) {
      $factoring_companies = auth()
        ->user()
        ->companies()
        ->anchorFactoring()
        ->first();
      if ($factoring_companies) {
        UserCurrentCompany::create([
          'user_id' => auth()->id(),
          'platform' => 'anchor factoring',
          'company_id' => $factoring_companies->id,
        ]);
      }
    }

    $exists = UserCurrentCompany::where(['user_id' => auth()->id(), 'platform' => 'anchor'])->first();

    if (!$exists) {
      $companies = auth()
        ->user()
        ->companies()
        ->anchor()
        ->first();
      if ($companies) {
        UserCurrentCompany::create([
          'user_id' => auth()->id(),
          'platform' => 'anchor',
          'company_id' => $companies->id,
        ]);
      }
    }

    $exists = UserCurrentCompany::where(['user_id' => auth()->id(), 'platform' => 'anchor dealer'])->first();

    if (!$exists) {
      $anchor_dealer_companies = auth()
        ->user()
        ->companies()
        ->anchorDealer()
        ->first();
      if ($anchor_dealer_companies) {
        UserCurrentCompany::create([
          'user_id' => auth()->id(),
          'platform' => 'anchor dealer',
          'company_id' => $anchor_dealer_companies->id,
        ]);
      }
    }

    $exists = UserCurrentCompany::where(['user_id' => auth()->id(), 'platform' => 'vendor'])->first();

    if (!$exists) {
      $companies = auth()
        ->user()
        ->companies()
        ->vendor()
        ->first();
      if ($companies) {
        UserCurrentCompany::create([
          'user_id' => auth()->id(),
          'platform' => 'vendor',
          'company_id' => $companies->id,
        ]);
      }
    }

    $exists = UserCurrentCompany::where(['user_id' => auth()->id(), 'platform' => 'buyer'])->first();

    if (!$exists) {
      $companies = auth()
        ->user()
        ->companies()
        ->buyerFactoring()
        ->first();
      if ($companies) {
        UserCurrentCompany::create([
          'user_id' => auth()->id(),
          'platform' => 'buyer',
          'company_id' => $companies->id,
        ]);
      }
    }

    $exists = UserCurrentCompany::where(['user_id' => auth()->id(), 'platform' => 'buyer dealer'])->first();

    if (!$exists) {
      $companies = auth()
        ->user()
        ->companies()
        ->buyer()
        ->first();
      if ($companies) {
        UserCurrentCompany::create([
          'user_id' => auth()->id(),
          'platform' => 'buyer dealer',
          'company_id' => $companies->id,
        ]);
      }
    }

    return $next($request);
  }
}
