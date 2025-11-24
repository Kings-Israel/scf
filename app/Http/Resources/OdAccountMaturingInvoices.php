<?php

namespace App\Http\Resources;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\ProgramVendorConfiguration;
use Illuminate\Http\Resources\Json\JsonResource;

class OdAccountMaturingInvoices extends JsonResource
{
  public $collects = ProgramVendorConfiguration::class;

  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
   */
  public function toArray($request)
  {
    $company = null;
    $anchor = null;
    $total_due = 0;
    $due_today = 0;
    $due_seven_days = 0;
    $due_fourteen_days = 0;
    $due_twenty_one_days = 0;
    $due_thirty_days = 0;
    $due_fourty_five_days = 0;
    $due_sixty_days = 0;
    $due_seventy_five_days = 0;
    $due_ninety_days = 0;

    if ($this->program->programType->name === Program::VENDOR_FINANCING) {
      if ($this->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
        $company = Company::find($this->company_id);
        $anchor = $this->program->anchor;

        $total_due = Invoice::vendorFinancing()
          ->where('company_id', $company->id)
          ->where('program_id', $this->program_id)
          ->where('financing_status', 'disbursed')
          ->sum('calculated_total_amount');

        $due_today = Invoice::vendorFinancing()
          ->where('company_id', $company->id)
          ->where('program_id', $this->program_id)
          ->where('financing_status', 'disbursed')
          ->whereDate('due_date', '<=', now()->format('Y-m-d'))
          ->sum('calculated_total_amount');

        $due_seven_days = Invoice::vendorFinancing()
          ->where('company_id', $company->id)
          ->where('program_id', $this->program_id)
          ->where('financing_status', 'disbursed')
          ->whereBetween('due_date', [
            now()
              ->addDay()
              ->format('Y-m-d'),
            now()
              ->addDays(7)
              ->format('Y-m-d'),
          ])
          ->sum('calculated_total_amount');

        $due_fourteen_days = Invoice::vendorFinancing()
          ->where('company_id', $company->id)
          ->where('program_id', $this->program_id)
          ->where('financing_status', 'disbursed')
          ->whereBetween('due_date', [
            now()
              ->addDays(8)
              ->format('Y-m-d'),
            now()
              ->addDays(14)
              ->format('Y-m-d'),
          ])
          ->sum('calculated_total_amount');

        $due_twenty_one_days = Invoice::vendorFinancing()
          ->where('company_id', $company->id)
          ->where('program_id', $this->program_id)
          ->where('financing_status', 'disbursed')
          ->whereBetween('due_date', [
            now()
              ->addDays(15)
              ->format('Y-m-d'),
            now()
              ->addDays(21)
              ->format('Y-m-d'),
          ])
          ->sum('calculated_total_amount');

        $due_thirty_days = Invoice::vendorFinancing()
          ->where('company_id', $company->id)
          ->where('program_id', $this->program_id)
          ->where('financing_status', 'disbursed')
          ->whereBetween('due_date', [
            now()
              ->addDays(22)
              ->format('Y-m-d'),
            now()
              ->addDays(30)
              ->format('Y-m-d'),
          ])
          ->sum('calculated_total_amount');

        $due_fourty_five_days = Invoice::vendorFinancing()
          ->where('company_id', $company->id)
          ->where('program_id', $this->program_id)
          ->where('financing_status', 'disbursed')
          ->whereBetween('due_date', [
            now()
              ->addDays(31)
              ->format('Y-m-d'),
            now()
              ->addDays(45)
              ->format('Y-m-d'),
          ])
          ->sum('calculated_total_amount');

        $due_sixty_days = Invoice::vendorFinancing()
          ->where('company_id', $company->id)
          ->where('program_id', $this->program_id)
          ->where('financing_status', 'disbursed')
          ->whereBetween('due_date', [
            now()
              ->addDays(46)
              ->format('Y-m-d'),
            now()
              ->addDays(60)
              ->format('Y-m-d'),
          ])
          ->sum('calculated_total_amount');

        $due_seventy_five_days = Invoice::vendorFinancing()
          ->where('company_id', $company->id)
          ->where('program_id', $this->program_id)
          ->where('financing_status', 'disbursed')
          ->whereBetween('due_date', [
            now()
              ->addDays(61)
              ->format('Y-m-d'),
            now()
              ->addDays(75)
              ->format('Y-m-d'),
          ])
          ->sum('calculated_total_amount');

        $due_ninety_days = Invoice::vendorFinancing()
          ->where('company_id', $company->id)
          ->where('program_id', $this->program_id)
          ->where('financing_status', 'disbursed')
          ->whereBetween('due_date', [
            now()
              ->addDays(76)
              ->format('Y-m-d'),
            now()
              ->addDays(90)
              ->format('Y-m-d'),
          ])
          ->sum('calculated_total_amount');
      } else {
        $company = Company::find($this->buyer_id);
        $anchor = $this->program->anchor;

        $total_due = Invoice::factoring()
          ->where('buyer_id', $company->id)
          ->where('program_id', $this->program_id)
          ->where('financing_status', 'disbursed')
          ->sum('calculated_total_amount');

        $due_today = Invoice::factoring()
          ->where('buyer_id', $company->id)
          ->where('program_id', $this->program_id)
          ->where('financing_status', 'disbursed')
          ->whereDate('due_date', '<=', now()->format('Y-m-d'))
          ->sum('calculated_total_amount');

        $due_seven_days = Invoice::factoring()
          ->where('buyer_id', $company->id)
          ->where('program_id', $this->program_id)
          ->where('financing_status', 'disbursed')
          ->whereBetween('due_date', [
            now()
              ->addDay()
              ->format('Y-m-d'),
            now()
              ->addDays(7)
              ->format('Y-m-d'),
          ])
          ->sum('calculated_total_amount');

        $due_fourteen_days = Invoice::factoring()
          ->where('buyer_id', $company->id)
          ->where('program_id', $this->program_id)
          ->where('financing_status', 'disbursed')
          ->whereBetween('due_date', [
            now()
              ->addDays(8)
              ->format('Y-m-d'),
            now()
              ->addDays(14)
              ->format('Y-m-d'),
          ])
          ->sum('calculated_total_amount');

        $due_twenty_one_days = Invoice::factoring()
          ->where('buyer_id', $company->id)
          ->where('program_id', $this->program_id)
          ->where('financing_status', 'disbursed')
          ->whereBetween('due_date', [
            now()
              ->addDays(15)
              ->format('Y-m-d'),
            now()
              ->addDays(21)
              ->format('Y-m-d'),
          ])
          ->sum('calculated_total_amount');

        $due_thirty_days = Invoice::factoring()
          ->where('buyer_id', $company->id)
          ->where('program_id', $this->program_id)
          ->where('financing_status', 'disbursed')
          ->whereBetween('due_date', [
            now()
              ->addDays(22)
              ->format('Y-m-d'),
            now()
              ->addDays(30)
              ->format('Y-m-d'),
          ])
          ->sum('calculated_total_amount');

        $due_fourty_five_days = Invoice::factoring()
          ->where('buyer_id', $company->id)
          ->where('program_id', $this->program_id)
          ->where('financing_status', 'disbursed')
          ->whereBetween('due_date', [
            now()
              ->addDays(31)
              ->format('Y-m-d'),
            now()
              ->addDays(45)
              ->format('Y-m-d'),
          ])
          ->sum('calculated_total_amount');

        $due_sixty_days = Invoice::factoring()
          ->where('buyer_id', $company->id)
          ->where('program_id', $this->program_id)
          ->where('financing_status', 'disbursed')
          ->whereBetween('due_date', [
            now()
              ->addDays(46)
              ->format('Y-m-d'),
            now()
              ->addDays(60)
              ->format('Y-m-d'),
          ])
          ->sum('calculated_total_amount');

        $due_seventy_five_days = Invoice::factoring()
          ->where('buyer_id', $company->id)
          ->where('program_id', $this->program_id)
          ->where('financing_status', 'disbursed')
          ->whereBetween('due_date', [
            now()
              ->addDays(61)
              ->format('Y-m-d'),
            now()
              ->addDays(75)
              ->format('Y-m-d'),
          ])
          ->sum('calculated_total_amount');

        $due_ninety_days = Invoice::factoring()
          ->where('buyer_id', $company->id)
          ->where('program_id', $this->program_id)
          ->where('financing_status', 'disbursed')
          ->whereBetween('due_date', [
            now()
              ->addDays(76)
              ->format('Y-m-d'),
            now()
              ->addDays(90)
              ->format('Y-m-d'),
          ])
          ->sum('calculated_total_amount');
      }
    } else {
      $company = Company::find($this->company_id);
      $anchor = $this->program->anchor;

      $total_due = Invoice::dealerFinancing()
        ->where('company_id', $company->id)
        ->where('program_id', $this->program_id)
        ->where('financing_status', 'disbursed')
        ->sum('calculated_total_amount');

      $due_today = Invoice::dealerFinancing()
        ->where('company_id', $company->id)
        ->where('program_id', $this->program_id)
        ->where('financing_status', 'disbursed')
        ->whereDate('due_date', '<=', now()->format('Y-m-d'))
        ->sum('calculated_total_amount');

      $due_seven_days = Invoice::dealerFinancing()
        ->where('company_id', $company->id)
        ->where('program_id', $this->program_id)
        ->where('financing_status', 'disbursed')
        ->whereBetween('due_date', [
          now()
            ->addDay()
            ->format('Y-m-d'),
          now()
            ->addDays(7)
            ->format('Y-m-d'),
        ])
        ->sum('calculated_total_amount');

      $due_fourteen_days = Invoice::dealerFinancing()
        ->where('company_id', $company->id)
        ->where('program_id', $this->program_id)
        ->where('financing_status', 'disbursed')
        ->whereBetween('due_date', [
          now()
            ->addDays(8)
            ->format('Y-m-d'),
          now()
            ->addDays(14)
            ->format('Y-m-d'),
        ])
        ->sum('calculated_total_amount');

      $due_twenty_one_days = Invoice::dealerFinancing()
        ->where('company_id', $company->id)
        ->where('program_id', $this->program_id)
        ->where('financing_status', 'disbursed')
        ->whereBetween('due_date', [
          now()
            ->addDays(15)
            ->format('Y-m-d'),
          now()
            ->addDays(21)
            ->format('Y-m-d'),
        ])
        ->sum('calculated_total_amount');

      $due_thirty_days = Invoice::dealerFinancing()
        ->where('company_id', $company->id)
        ->where('program_id', $this->program_id)
        ->where('financing_status', 'disbursed')
        ->whereBetween('due_date', [
          now()
            ->addDays(22)
            ->format('Y-m-d'),
          now()
            ->addDays(30)
            ->format('Y-m-d'),
        ])
        ->sum('calculated_total_amount');

      $due_fourty_five_days = Invoice::dealerFinancing()
        ->where('company_id', $company->id)
        ->where('program_id', $this->program_id)
        ->where('financing_status', 'disbursed')
        ->whereBetween('due_date', [
          now()
            ->addDays(31)
            ->format('Y-m-d'),
          now()
            ->addDays(45)
            ->format('Y-m-d'),
        ])
        ->sum('calculated_total_amount');

      $due_sixty_days = Invoice::dealerFinancing()
        ->where('company_id', $company->id)
        ->where('program_id', $this->program_id)
        ->where('financing_status', 'disbursed')
        ->whereBetween('due_date', [
          now()
            ->addDays(46)
            ->format('Y-m-d'),
          now()
            ->addDays(60)
            ->format('Y-m-d'),
        ])
        ->sum('calculated_total_amount');

      $due_seventy_five_days = Invoice::dealerFinancing()
        ->where('company_id', $company->id)
        ->where('program_id', $this->program_id)
        ->where('financing_status', 'disbursed')
        ->whereBetween('due_date', [
          now()
            ->addDays(61)
            ->format('Y-m-d'),
          now()
            ->addDays(75)
            ->format('Y-m-d'),
        ])
        ->sum('calculated_total_amount');

      $due_ninety_days = Invoice::dealerFinancing()
        ->where('company_id', $company->id)
        ->where('program_id', $this->program_id)
        ->where('financing_status', 'disbursed')
        ->whereBetween('due_date', [
          now()
            ->addDays(76)
            ->format('Y-m-d'),
          now()
            ->addDays(90)
            ->format('Y-m-d'),
        ])
        ->sum('calculated_total_amount');
    }

    return [
      'payment_account_number' => $this->payment_account_number,
      'company' => $company,
      'anchor' => $anchor,
      'total_due' => round($total_due, 2),
      'due_today' => round($due_today, 2),
      'due_seven_days' => round($due_seven_days, 2),
      'due_fourteen_days' => round($due_fourteen_days, 2),
      'due_twenty_one_days' => round($due_twenty_one_days, 2),
      'due_thirty_days' => round($due_thirty_days, 2),
      'due_fourty_five_days' => round($due_fourty_five_days, 2),
      'due_sixty_days' => round($due_sixty_days, 2),
      'due_seventy_five_days' => round($due_seventy_five_days, 2),
      'due_ninety_days' => round($due_ninety_days, 2),
    ];
  }
}
