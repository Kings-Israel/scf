<?php

namespace App\Http\Controllers\Anchor;

use App\Models\Company;
use App\Models\Program;
use Illuminate\Http\Request;
use App\Models\ProgramBankDetails;
use App\Http\Controllers\Controller;
use App\Models\ProgramVendorBankDetail;
use App\Models\ProgramVendorConfiguration;

class ProgramsController extends Controller
{
  public function programs()
  {
    return view('content.anchor.factoring.program.programs');
  }

  public function program(Program $program, Company $company)
  {
    // Get active company
    $current_company = auth()
      ->user()
      ->activeFactoringCompany()
      ->first();

    if ($program->programType->name == Program::DEALER_FINANCING) {
      $program_vendor_configuration = ProgramVendorConfiguration::where('program_id', $program->id)->where('company_id', $company->id)->select('program_id', 'payment_terms')->first();
    } else {
      $program_vendor_configuration = ProgramVendorConfiguration::where('program_id', $program->id)->where('company_id', $current_company->company_id)->where('buyer_id', $company->id)->select('program_id', 'payment_terms')->first();
    }

    $bank_accounts = ProgramBankDetails::where('program_id', $program->id)->get();

    $program = Program::select('mandatory_invoice_attachment')->find($program->id);

    return response()->json(['program' => $program, 'bank_accounts' => $bank_accounts, 'payment_terms' => $program_vendor_configuration->payment_terms ? $program_vendor_configuration->payment_terms : $program_vendor_configuration->program->default_payment_terms], 200);
  }
}
