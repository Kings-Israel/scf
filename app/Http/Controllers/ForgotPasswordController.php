<?php

namespace App\Http\Controllers;

use App\Jobs\SendMail;
use App\Models\Bank;
use App\Models\BankUser;
use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class ForgotPasswordController extends Controller
{
  public function showForgotPasswordForm()
  {
    $pageConfigs = ['myLayout' => 'blank'];
    return view('content.bank.auth.forgot-password', ['pageConfigs' => $pageConfigs]);
  }

  public function forgotPassword(Request $request)
  {
    $request->validate([
      'email' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
      toastr()->error('', 'Invalid email address');

      return back();
    }

    $link = URL::temporarySignedRoute('auth.reset-password', now()->addHour(), ['id' => $user->id]);

    SendMail::dispatchAfterResponse($user->email, 'ResetPassword', ['user_name' => $user->name, 'link' => $link]);

    toastr()->success('', 'Check email to reset password');

    return redirect()->route('login');
  }

  public function newUserResetPassword($id, $token)
  {
    $user = User::find($id);

    if (!$user) {
      toastr()->error('Invalid Link');

      return redirect()->route('login');
    }

    $reset_token = PasswordReset::where('token', $token)
      ->where('email', $user->email)
      ->first();

    if (!$reset_token) {
      toastr()->error('Invalid Link');

      return redirect()->route('login');
    }

    $pageConfigs = ['myLayout' => 'blank'];

    return view('content.bank.auth.reset-password', ['pageConfigs' => $pageConfigs, 'user' => $user->id]);
  }

  public function showResetPasswordForm($id)
  {
    if (!request()->hasValidSignature()) {
      toastr()->error('', 'The link is invalid or has expired');

      return redirect()->route('login');
    }

    $user = User::find($id);

    if (!$user) {
      $user_id = decrypt($id);
      $user = User::find($user_id);
    }

    if (!$user) {
      toastr()->error('Invalid Link');

      return redirect()->route('login');
    }

    $pageConfigs = ['myLayout' => 'blank'];

    return view('content.bank.auth.reset-password', ['pageConfigs' => $pageConfigs, 'user' => $user->id]);
  }

  public function setPassword(Request $request)
  {
    $request->validate(
      [
        'user_id' => 'required',
        'password' => [
          'required',
          'min:6',
          // 'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/',
          'confirmed',
        ],
      ],
      [
        // 'password.regex' => 'Password must contain an uppercase letter, a number and a special character',
      ]
    );

    $user = User::find($request->user_id);

    if (!$user) {
      toastr()->error('Invalid Link');

      return redirect()->route('login');
    }

    $user->update([
      'password' => bcrypt($request->password),
      'password_expiry' => now()
        ->addMonth()
        ->format('Y-m-d'),
    ]);

    $reset_token = PasswordReset::where('email', $user->email)->first();

    if ($reset_token) {
      $reset_token->delete();
    }

    toastr()->success('', 'Password updated successfully. You can now login');

    return redirect()->route('login');
  }
}
