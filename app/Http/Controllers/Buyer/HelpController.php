<?php

namespace App\Http\Controllers\Buyer;

use App\Models\UserManual;
use Illuminate\Http\Request;
use App\Models\SystemConfiguration;
use App\Http\Controllers\Controller;
use App\Models\AdminBankConfiguration;
use App\Models\Company;

class HelpController extends Controller
{
  public function index()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerFactoringCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $dealer_manual = UserManual::select('anchor_user_manual')
      ->where('status', 'active')
      ->first();

    $system_configuration = AdminBankConfiguration::where('bank_id', $company->bank_id)
      ->select('help_contact_email', 'help_contact_number')
      ->first();

    if (!$system_configuration) {
      $system_configuration = SystemConfiguration::select(['help_contact_number', 'help_contact_email'])->first();
    }

    return view('content.buyer.help.index', compact('dealer_manual', 'system_configuration'));
  }

  public function dealerIndex()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeBuyerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $dealer_manual = UserManual::select('dealer_user_manual')
      ->where('status', 'active')
      ->first();

    $system_configuration = AdminBankConfiguration::where('bank_id', $company->bank_id)
      ->select('help_contact_email', 'help_contact_number')
      ->first();

    if (!$system_configuration) {
      $system_configuration = SystemConfiguration::select(['help_contact_number', 'help_contact_email'])->first();
    }

    return view('content.dealer.help.index', compact('dealer_manual', 'system_configuration'));
  }
}
