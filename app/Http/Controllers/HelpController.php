<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bank;
use App\Models\SystemConfiguration;
use App\Models\UserManual;

class HelpController extends Controller
{
  public function index(Bank $bank)
  {
    $bank_manual = UserManual::select('bank_user_manual')
      ->where('status', 'active')
      ->first();

    $system_configuration = SystemConfiguration::select(['help_contact_number', 'help_contact_email'])->first();

    return view('content.bank.help.index', compact('bank_manual', 'system_configuration'));
  }
}
