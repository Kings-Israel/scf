<?php

use Illuminate\Support\Facades\Route;

$controller_path = 'App\Http\Controllers';

Route::group(['middleware' => 'guest'], function () use ($controller_path) {
  Route::get('/', $controller_path . '\AuthController@showLoginForm')->name('login');
  Route::post('/login', $controller_path . '\AuthController@login')
    ->name('login.submit')
    ->middleware('throttle:login');
  Route::get('/auth/forgot-password', $controller_path . '\ForgotPasswordController@showForgotPasswordForm')->name(
    'auth.forgot.password'
  );
  Route::post('/auth/forgot-password', $controller_path . '\ForgotPasswordController@forgotPassword')->name(
    'auth-forgot-password.reset'
  );

  Route::get('/{id}/reset-password', $controller_path . '\ForgotPasswordController@showResetPasswordForm')->name(
    'auth.reset-password'
  );
  Route::get('/{id}/reset-password/{token}', $controller_path . '\ForgotPasswordController@newUserResetPassword')->name(
    'auth.new.reset-password'
  );
  Route::post('/set-password', $controller_path . '\ForgotPasswordController@setPassword')->name('password.set');
});

Route::get('/auth/terms/{type}/{bank?}', $controller_path . '\AuthController@showTermsForm')
  ->name('terms.show')
  ->middleware('auth');
Route::post('/auth/terms', $controller_path . '\AuthController@acceptTerms')
  ->name('auth.terms.submit')
  ->middleware('auth');

Route::post('logout', $controller_path . '\AuthController@destroy')
  ->name('logout')
  ->middleware('auth');

Route::post('logout', $controller_path . '\AuthController@destroy')
  ->name('logout')
  ->middleware('auth');
Route::post('/log-out', function () {
  Auth::logout();
  Session::flush();
  return response()->json(['message' => 'Logged out']);
})
  ->middleware('auth')
  ->name('log-out');

Route::post('/keep-alive', $controller_path . '\AuthController@keepAlive')
  ->middleware('auth')
  ->name('keep-alive');
