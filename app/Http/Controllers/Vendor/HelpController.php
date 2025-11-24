<?php

namespace App\Http\Controllers\Vendor;

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
      ->activeVendorCompany()
      ->first();

    $company = Company::find($current_company->company_id);

    $vendor_manual = UserManual::select('vendor_user_manual')
      ->where('status', 'active')
      ->first();

    $system_configuration = AdminBankConfiguration::where('bank_id', $company->bank_id)
      ->select('help_contact_email', 'help_contact_number')
      ->first();

    if (!$system_configuration) {
      $system_configuration = SystemConfiguration::select(['help_contact_number', 'help_contact_email'])->first();
    }

    return view('content.vendor.help.index', compact('vendor_manual', 'system_configuration'));
  }
}
