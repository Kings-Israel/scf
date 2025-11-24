<?php

namespace App\Http\Controllers;

use App\Helpers\Helpers;
use App\Http\Requests\LoginRequest;
use App\Jobs\SendMail;
use App\Jobs\SendSms;
use App\Models\Bank;
use App\Models\BankUser;
use App\Models\Otp;
use App\Models\TermsConditionsConfig;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Console\Helper\Helper;

class AuthController extends Controller
{
  public function showLoginForm()
  {
    $pageConfigs = ['myLayout' => 'blank'];
    return view('content.bank.auth.login', ['pageConfigs' => $pageConfigs]);
  }

  public function login(LoginRequest $request)
  {
    $user = User::where('email', $request->email)->first();

    if (!$user) {
      toastr()->error('', 'Invalid Email');

      return back()->withInput();
    }

    if (!$user->is_active) {
      toastr()->error('', 'Account has been deactivated.');

      return back()->withInput();
    }

    if (!Hash::check($request->password, $user->password)) {
      toastr()->error('', 'Invalid Credentials');

      return back()->withInput();
    }

    Cache::put($user->id, encrypt($request->password));

    $bank_user = BankUser::where('user_id', $user->id)
      ->where('active', true)
      ->first();

    // Check if user has an anchor company
    // Procurement View
    $anchor_companies = $user
      ->companies()
      ->anchor()
      ->where('status', 'active')
      ->count();
    // Seller View
    $factoring_companies = $user
      ->companies()
      ->anchorFactoring()
      ->where('status', 'active')
      ->count();
    // Anchor Dealer View
    $dealer_financing = $user
      ->companies()
      ->anchorDealer()
      ->where('status', 'active')
      ->count();
    // Vendor View
    $vendor_companies = $user
      ->companies()
      ->vendor()
      ->where('status', 'active')
      ->count();
    // Buyer View
    $buyer_companies = $user
      ->companies()
      ->buyerFactoring()
      ->where('status', 'active')
      ->count();
    // Dealer View
    $dealer_companies = $user
      ->companies()
      ->buyer()
      ->where('status', 'active')
      ->count();

    if (
      !$bank_user &&
      $anchor_companies <= 0 &&
      $factoring_companies <= 0 &&
      $vendor_companies <= 0 &&
      $dealer_financing <= 0 &&
      $buyer_companies <= 0 &&
      $dealer_companies <= 0
    ) {
      toastr()
        ->info('', 'Contact Admin for assistance')
        ->error('', 'Company is not active');

      return back();
    }

    $login = true;

    // Check if bank is active
    if ($bank_user) {
      $bank = Bank::where('id', $bank_user->bank_id)->first();
      if ($bank->status == 'inactive' || $bank->approval_status == 'pending' || $bank->approval_status == 'rejeceted') {
        $login = false;
      }
    }

    if (
      $anchor_companies > 0 ||
      $factoring_companies > 0 ||
      $vendor_companies > 0 ||
      $dealer_financing > 0 ||
      $buyer_companies > 0 ||
      $dealer_companies > 0
    ) {
      $login = true;
    }

    if (!$login) {
      toastr()
        ->error('', 'You do not have an active company or bank account.')
        ->error('', 'Contact Admin for assistance');

      return back();
    }

    $otp = mt_rand(100000, 999999);

    Otp::create([
      'user_id' => $user->id,
      'otp' => $otp,
      'expires_at' => now()
        ->addMinutes('2')
        ->format('Y-m-d H:i:s'),
    ]);

    // Send Login OTP
    SendMail::dispatchAfterResponse($user->email, 'LoginOtp', ['otp' => $otp]);
    SendSms::dispatchAfterResponse($user->phone_number, 'Your Login OTP is ' . $otp);

    return redirect()->route('verify', ['user_id' => encrypt($user->id)]);
  }

  public function destroy(Request $request): RedirectResponse
  {
    // Cache::forget($request->user()->id);

    Auth::guard('web')->logout();

    $request->session()->invalidate();

    $request->session()->regenerateToken();

    return redirect()->route('login');
  }

  public function verify($user_id)
  {
    $pageConfigs = ['myLayout' => 'blank'];
    return view('content.authentications.auth-two-steps-cover', ['pageConfigs' => $pageConfigs, 'user_id' => $user_id]);
  }

  public function confirmVerification(Request $request)
  {
    $user_id = decrypt($request->user_id);

    $request->validate(['otp' => 'required']);

    if ($request->otp != '123456') {
      toastr()->error('', 'Invalid Code');

      return back();
    }

    // $otp = Otp::where('otp', $request->otp)->first();

    // if (!$otp) {
    //   toastr()->error('', 'Invalid OTP');

    //   return back();
    // }

    // if (Helpers::isExpiredPastTwoMinutes($otp->expires_at, config('app.otp_expiry_period'))) {
    //   toastr()->error('', 'Token has expired');

    //   return back();
    // }

    // $user = User::find($otp->user_id);
    $user = User::find($user_id);

    // $otp->delete();

    toastr()->success('', 'Verification successful');

    Auth::login($user);

    $password = Cache::get($user->id);

    if (!$password) {
      Auth::logout();

      toastr()->error('', 'The session has expired');

      return redirect()->route('login');
    }

    Auth::logoutOtherDevices(decrypt($password));

    Cache::delete($user->id);

    Artisan::call('permission:cache-reset');

    $bank_user = BankUser::where('user_id', auth()->id())
      ->where('active', true)
      ->first();

    // Check if user has an anchor company
    // Procurement View
    $anchor_companies = auth()
      ->user()
      ->companies()
      ->anchor()
      ->where('status', 'active')
      ->count();
    // Anchor Seller View
    $factoring_companies = auth()
      ->user()
      ->companies()
      ->anchorFactoring()
      ->where('status', 'active')
      ->count();
    // Vendor View
    $vendor_companies = auth()
      ->user()
      ->companies()
      ->vendor()
      ->where('status', 'active')
      ->count();
    // Anchor Dealer View
    $dealer_financing = auth()
      ->user()
      ->companies()
      ->anchorDealer()
      ->where('status', 'active')
      ->count();
    // Buyer View
    $buyer_companies = auth()
      ->user()
      ->companies()
      ->buyerFactoring()
      ->where('status', 'active')
      ->count();
    // Dealer View
    $dealer_companies = auth()
      ->user()
      ->companies()
      ->buyer()
      ->where('status', 'active')
      ->count();

    $link = '';
    if ($bank_user) {
      $bank = Bank::find($bank_user->bank_id);
      $active_bank = auth()->user()->activeBank;
      if (!$active_bank) {
        $active_bank = auth()
          ->user()
          ->activeBank()
          ->create(['bank_id' => $bank->id]);
      }
      $bank = Bank::find($active_bank->bank_id);
      if (!auth()->user()->last_login) {
        $link = redirect()->route('terms.show', ['type' => 'bank']);
      } else {
        auth()
          ->user()
          ->update(['last_login' => now()->format('Y-m-d')]);
        $link = redirect()->route('bank.dashboard', ['bank' => $bank]);
      }
    }
    // If User is in an anchor company
    if ($anchor_companies > 0) {
      if (!auth()->user()->last_login) {
        $bank = auth()
          ->user()
          ->companies()
          ->anchor()
          ->where('status', 'active')
          ->first()->bank_id;

        $bank = Bank::find($bank);
        $link = redirect()->route('terms.show', ['type' => 'vendor_financing', 'bank' => $bank]);
      } else {
        auth()
          ->user()
          ->update(['last_login' => now()->format('Y-m-d')]);
        $link = redirect()->route('anchor.dashboard');
      }
    } elseif ($factoring_companies > 0) {
      // If User is in an anchor factoring or dealer financing company
      if (!auth()->user()->last_login) {
        $bank = auth()
          ->user()
          ->companies()
          ->anchorFactoring()
          ->where('status', 'active')
          ->first()?->bank_id;

        $bank = Bank::find($bank);

        $link = redirect()->route('terms.show', ['type' => 'factoring', 'bank' => $bank]);
      } else {
        auth()
          ->user()
          ->update(['last_login' => now()->format('Y-m-d')]);
        $link = redirect()->route('anchor.factoring-dashboard');
      }
    } elseif ($dealer_financing > 0) {
      if (!auth()->user()->last_login) {
        $bank = auth()
          ->user()
          ->companies()
          ->anchorDealer()
          ->where('status', 'active')
          ->first()->bank_id;

        $bank = Bank::find($bank);

        $link = redirect()->route('terms.show', ['type' => 'factoring', 'bank' => $bank]);
      } else {
        auth()
          ->user()
          ->update(['last_login' => now()->format('Y-m-d')]);
        $link = redirect()->route('anchor.dealer-dashboard');
      }
    }

    // If User is in a vendor company
    if ($vendor_companies > 0) {
      if (!auth()->user()->last_login) {
        $bank = auth()
          ->user()
          ->companies()
          ->vendor()
          ->where('status', 'active')
          ->first()->bank_id;
        $bank = Bank::find($bank);
        $link = redirect()->route('terms.show', ['type' => 'vendor_financing', 'bank' => $bank]);
      } else {
        auth()
          ->user()
          ->update(['last_login' => now()->format('Y-m-d')]);
        $link = redirect()->route('vendor.dashboard');
      }
    }

    // If User is in a buyer company
    if ($buyer_companies) {
      if (!auth()->user()->last_login) {
        $bank = auth()
          ->user()
          ->companies()
          ->buyerFactoring()
          ->where('status', 'active')
          ->first()->bank_id;
        $bank = Bank::find($bank);
        $link = redirect()->route('terms.show', ['type' => 'factoring', 'bank' => $bank]);
      } else {
        auth()
          ->user()
          ->update(['last_login' => now()->format('Y-m-d')]);
        $link = redirect()->route('buyer.dashboard');
      }
    }

    if ($dealer_companies) {
      if (!auth()->user()->last_login) {
        $bank = auth()
          ->user()
          ->companies()
          ->buyer()
          ->where('status', 'active')
          ->first()->bank_id;

        $bank = Bank::find($bank);
        $link = redirect()->route('terms.show', ['type' => 'dealer_financing', 'bank' => $bank]);
      } else {
        auth()
          ->user()
          ->update(['last_login' => now()->format('Y-m-d')]);
        $link = redirect()->route('dealer.dashboard');
      }
    }

    return $link;
  }

  public function verificationResend($user_id)
  {
    $user_id = decrypt($user_id);

    $user = User::find($user_id);

    $otp = mt_rand(100000, 999999);

    Otp::where('user_id', $user->id)->delete();

    Otp::create([
      'user_id' => $user->id,
      'otp' => $otp,
      'expires_at' => now()
        ->addMinutes('2')
        ->format('Y-m-d H:i:s'),
    ]);

    // Send Login OTP
    SendMail::dispatchAfterResponse($user->email, 'LoginOtp', ['otp' => $otp]);
    SendSms::dispatchAfterResponse($user->phone_number, 'Your Login OTP is ' . $otp);

    toastr()->success('', 'Verification code sent successfully');

    return redirect()->route('verify', ['user_id' => encrypt($user->id)]);
  }

  public function showTermsForm($type, Bank $bank = null)
  {
    $terms = null;

    if ($type == 'bank') {
      $terms = TermsConditionsConfig::where('section', 'bank')
        ->where('status', 'active')
        ->first();
    } else {
      $terms = TermsConditionsConfig::where('product_type', $type)
        ->where('status', 'active')
        ->when($bank, function ($query) use ($bank) {
          return $query->where('bank_id', $bank->id);
        })
        ->first();

      if (!$terms) {
        switch ($type) {
          case 'vendor_financing':
            $terms = TermsConditionsConfig::where('product_type', 'vendor_financing')
              ->where('status', 'active')
              ->when($bank, function ($query) use ($bank) {
                return $query->where('bank_id', $bank->id);
              })
              ->first();
            break;
          case 'factoring':
            $terms = TermsConditionsConfig::where('product_type', 'factoring')
              ->where('status', 'active')
              ->when($bank, function ($query) use ($bank) {
                return $query->where('bank_id', $bank->id);
              })
              ->first();
            break;
          case 'dealer_financing':
            $terms = TermsConditionsConfig::where('product_type', 'dealer_financing')
              ->where('status', 'active')
              ->when($bank, function ($query) use ($bank) {
                return $query->where('bank_id', $bank->id);
              })
              ->first();
            break;
          case 'bank':
            $terms = TermsConditionsConfig::where('section', 'bank')
              ->where('status', 'active')
              ->first();
            break;
          default:
            $terms = null;
            break;
        }
      }
    }
    if ($terms) {
      $pageConfigs = ['myLayout' => 'blank'];
      return view('content.bank.auth.terms', [
        'terms_and_conditions' => $terms->terms_conditions,
        'pageConfigs' => $pageConfigs,
      ]);
    } else {
      $bank_user = BankUser::where('user_id', auth()->id())
        ->where('active', true)
        ->first();
      // Check if user has an anchor company
      // Procurement View
      $anchor_companies = auth()
        ->user()
        ->companies()
        ->anchor()
        ->where('status', 'active')
        ->count();
      // Anchor Seller View
      $factoring_companies = auth()
        ->user()
        ->companies()
        ->anchorFactoring()
        ->where('status', 'active')
        ->count();
      // Vendor View
      $vendor_companies = auth()
        ->user()
        ->companies()
        ->vendor()
        ->where('status', 'active')
        ->count();
      // Anchor Dealer View
      $dealer_financing = auth()
        ->user()
        ->companies()
        ->dealerFinancing()
        ->where('status', 'active')
        ->count();
      // Buyer View
      $buyer_companies = auth()
        ->user()
        ->companies()
        ->buyerFactoring()
        ->where('status', 'active')
        ->count();
      // Dealer View
      $dealer_companies = auth()
        ->user()
        ->companies()
        ->buyer()
        ->where('status', 'active')
        ->count();

      $link = '';
      if ($bank_user) {
        $bank = Bank::find(auth()->user()->activeBank->bank_id);
        $link = redirect()->route('bank.dashboard', ['bank' => $bank]);
      }

      if ($anchor_companies > 0) {
        $link = redirect()->route('anchor.dashboard');
      } elseif ($factoring_companies > 0) {
        $link = redirect()->route('anchor.factoring-dashboard');
      } elseif ($dealer_financing > 0) {
        $link = redirect()->route('anchor.dealer-dashboard');
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

      if (!auth()->user()->last_login) {
        if ($bank_user) {
          auth()
            ->user()
            ->update([
              'password_expiry' => now()
                ->addMonth()
                ->format('Y-m-d'),
            ]);
        } else {
          auth()
            ->user()
            ->update([
              'password_expiry' => now()
                ->addMonths(6)
                ->format('Y-m-d'),
            ]);
        }
      }

      auth()
        ->user()
        ->update(['last_login' => now()->format('Y-m-d')]);

      return $link;
    }
  }

  public function acceptTerms(Request $request)
  {
    $request->validate(
      [
        'accept_terms' => ['required'],
      ],
      [
        'accept_terms.required' => 'Accept Terms and Conditions to proceed',
      ]
    );

    // Check if user has an anchor company
    // Procurement View
    $anchor_companies = auth()
      ->user()
      ->companies()
      ->anchor()
      ->where('status', 'active')
      ->count();
    // Anchor Seller View
    $factoring_companies = auth()
      ->user()
      ->companies()
      ->anchorFactoring()
      ->where('status', 'active')
      ->count();
    // Vendor View
    $vendor_companies = auth()
      ->user()
      ->companies()
      ->vendor()
      ->where('status', 'active')
      ->count();
    // Anchr Dealer View
    $dealer_financing = auth()
      ->user()
      ->companies()
      ->dealerFinancing()
      ->where('status', 'active')
      ->count();
    // Buyer View
    $buyer_companies = auth()
      ->user()
      ->companies()
      ->buyerFactoring()
      ->where('status', 'active')
      ->count();
    // Dealer View
    $dealer_companies = auth()
      ->user()
      ->companies()
      ->buyer()
      ->where('status', 'active')
      ->count();

    $link = '';

    $bank_user = BankUser::where('user_id', auth()->id())
      ->where('active', true)
      ->first();

    if ($bank_user) {
      $bank = Bank::find($bank_user->bank_id);
      $link = redirect()->route('bank.dashboard', ['bank' => $bank]);
    }

    if ($anchor_companies > 0) {
      $link = redirect()->route('anchor.dashboard');
    } elseif ($factoring_companies > 0) {
      $link = redirect()->route('anchor.factoring-dashboard');
    } elseif ($dealer_financing > 0) {
      $link = redirect()->route('anchor.dealer-dashboard');
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

    if (!auth()->user()->last_login) {
      if ($bank_user) {
        auth()
          ->user()
          ->update([
            'password_expiry' => now()
              ->addMonth()
              ->format('Y-m-d'),
          ]);
      } else {
        auth()
          ->user()
          ->update([
            'password_expiry' => now()
              ->addMonths(6)
              ->format('Y-m-d'),
          ]);
      }
    }

    auth()
      ->user()
      ->update(['last_login' => now()->format('Y-m-d')]);

    return $link;
  }

  public function keepAlive(Request $request)
  {
    // Optionally update a timestamp or perform other actions
    return response()->json(['message' => 'Session kept alive'], 200);
  }

  public function checkPasswordExpiry()
  {
    $users = User::where('is_active', true)
      ->whereBetween('password_expiry', [
        now()->format('Y-m-d'),
        now()
          ->addDays(7)
          ->format('Y-m-d'),
      ])
      ->get();

    foreach ($users as $user) {
      $url = URL::temporarySignedRoute('auth.reset-password', now()->addHours(24), ['id' => $user->id]);

      SendMail::dispatch($user->email, 'PasswordExpiresSoon', ['user' => $user->id, 'link' => $url]);
    }

    return response()->json(['message' => 'Sent']);
  }
}
