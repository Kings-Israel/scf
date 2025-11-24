<?php

namespace App\Http\Controllers\Anchor;

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
      ->activeAnchorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $anchor_manual = UserManual::select('anchor_user_manual')
      ->where('status', 'active')
      ->first();

    $system_configuration = AdminBankConfiguration::where('bank_id', $company->bank_id)
      ->select('help_contact_email', 'help_contact_number')
      ->first();

    if (!$system_configuration) {
      $system_configuration = SystemConfiguration::select(['help_contact_number', 'help_contact_email'])->first();
    }

    return view('content.anchor.reverse-factoring.help.index', compact('anchor_manual', 'system_configuration'));
  }

  public function factoringIndex()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();
    $company = Company::find($current_company->company_id);

    $anchor_manual = UserManual::select('vendor_user_manual')
      ->where('status', 'active')
      ->first();

    $system_configuration = AdminBankConfiguration::where('bank_id', $company->bank_id)
      ->select('help_contact_email', 'help_contact_number')
      ->first();

    if (!$system_configuration) {
      $system_configuration = SystemConfiguration::select(['help_contact_number', 'help_contact_email'])->first();
    }

    return view('content.anchor.factoring.help.index', compact('anchor_manual', 'system_configuration'));
  }

  public function dealerIndex()
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeAnchorDealerCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $anchor_manual = UserManual::select('anchor_user_manual')
      ->where('status', 'active')
      ->first();

    $system_configuration = AdminBankConfiguration::where('bank_id', $company->bank_id)
      ->select(['help_contact_email', 'help_contact_number'])
      ->first();

    if (!$system_configuration) {
      $system_configuration = SystemConfiguration::select(['help_contact_number', 'help_contact_email'])->first();
    }

    return view('content.anchor.dealer.help.index', compact('anchor_manual', 'system_configuration'));
  }
}
