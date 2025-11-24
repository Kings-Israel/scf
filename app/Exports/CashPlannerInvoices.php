<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceProcessing;
use App\Models\Program;
use Illuminate\Support\Str;
use App\Models\ProgramDiscount;
use App\Models\ProgramVendorFee;
use App\Models\ProgramVendorDiscount;
use App\Models\ProgramVendorBankDetail;
use App\Models\ProgramVendorConfiguration;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CashPlannerInvoices implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize
{
  public $program;

  public function __construct($program, public Company $company, public $selected_date = null)
  {
    $this->program = $program;
  }
  /**
   * @return \Illuminate\Support\Collection
   */
  public function collection()
  {
    $invoices_in_processing = InvoiceProcessing::where('company_id', $this->company->id)->pluck('invoice_id');

    return Invoice::where('program_id', $this->program)
      ->where('status', 'approved')
      ->whereDate('due_date', '>=', now())
      ->where('company_id', $this->company->id)
      ->whereDoesntHave('paymentRequests')
      ->whereNotIn('id', $invoices_in_processing)
      ->latest()
      ->where('eligible_for_financing', true)
      ->orderBy('due_date', 'DESC')
      ->get()
      ->filter(function ($value, $index) {
        return Carbon::parse($value->due_date)->subDays($value->program->min_financing_days) > now() &&
          Carbon::parse($this->selected_date)->diffInDays(Carbon::parse($value->due_date)) <=
            $value->program->max_financing_days;
      });
  }

  public function headings(): array
  {
    return [
      'Anchor',
      'Invoice Number',
      'Invoice Amount',
      'Invoice Date',
      'Due Date',
      'Status',
      'PI Amount',
      'Discount & Fees',
      'Net Pay',
    ];
  }

  public function map($invoice): array
  {
    $vendor_discount_details = ProgramVendorDiscount::where('company_id', $invoice->company->id)
      ->where('program_id', $invoice->program_id)
      ->first();
    $vendor_configurations = ProgramVendorConfiguration::where('company_id', $invoice->company->id)
      ->where('program_id', $invoice->program_id)
      ->first();
    // Get fees for vendor
    $vendor_fees = ProgramVendorFee::where('company_id', $invoice->company_id)
      ->where('program_id', $invoice->program_id)
      ->get();
    // Get Tax on Discount Value
    $tax_on_discount = ProgramDiscount::where('program_id', $invoice->program_id)->first()?->tax_on_discount;

    $eligibility = $vendor_configurations->eligibility;
    $total_amount = $invoice->invoice_total_amount;
    $total_roi = $vendor_discount_details->total_roi;
    $legible_amount = ($eligibility / 100) * $total_amount;

    $fees_amount = 0;
    $anchor_bearing_fees = 0;
    $vendor_bearing_fees = 0;
    $fees_tax_amount = 0;
    if ($vendor_fees->count() > 0) {
      foreach ($vendor_fees as $fee) {
        if ($fee->type === 'amount') {
          if ($fee->charge_type === 'daily') {
            $fees_amount += $fee->value * now()->diffInDays(Carbon::parse($invoice->due_date));

            if ($fee->taxes) {
              $fees_tax_amount += round(
                ($fee->taxes / 100) * $fee->value * now()->diffInDays(Carbon::parse($invoice->due_date)),
                2
              );
            }

            if ($this->program->programType->name == Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += $fee->value * now()->diffInDays(Carbon::parse($invoice->due_date));
            } else {
              $anchor_bearing_fees +=
                ($fee->anchor_bearing_discount / 100) *
                $fee->value *
                now()->diffInDays(Carbon::parse($invoice->due_date));
              $vendor_bearing_fees +=
                ($fee->vendor_bearing_discount / 100) *
                $fee->value *
                now()->diffInDays(Carbon::parse($invoice->due_date));
            }
          } else {
            $fees_amount += $fee->value;

            if ($fee->taxes) {
              $fees_tax_amount += round(($fee->taxes / 100) * $fee->value, 2);
            }

            if ($this->program->programType->name == Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += $fee->value;
            } else {
              $anchor_bearing_fees += ($fee->anchor_bearing_discount / 100) * $fee->value;
              $vendor_bearing_fees += ($fee->vendor_bearing_discount / 100) * $fee->value;
            }
          }
        }

        if ($fee->type === 'percentage') {
          if ($fee->charge_type === 'daily') {
            $fees_amount +=
              ($fee->value / 100) * $legible_amount * now()->diffInDays(Carbon::parse($invoice->due_date));

            if ($fee->taxes) {
              $fees_tax_amount += round(
                ($fee->taxes / 100) *
                  (($fee->value / 100) * $legible_amount * now()->diffInDays(Carbon::parse($invoice->due_date))),
                2
              );
            }

            if ($this->program->programType->name == Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += round(
                ($fee->value / 100) * $legible_amount * now()->diffInDays(Carbon::parse($invoice->due_date)),
                2
              );
            } else {
              $anchor_bearing_fees += round(
                ($fee->anchor_bearing_discount / 100) *
                  (($fee->value / 100) * $legible_amount * now()->diffInDays(Carbon::parse($invoice->due_date))),
                2
              );
              $vendor_bearing_fees += round(
                ($fee->vendor_bearing_discount / 100) *
                  (($fee->value / 100) * $legible_amount * now()->diffInDays(Carbon::parse($invoice->due_date))),
                2
              );
            }
          } else {
            $fees_amount += ($fee->value / 100) * $legible_amount;

            if ($fee->taxes) {
              $fees_tax_amount += round(($fee->taxes / 100) * (($fee->value / 100) * $legible_amount), 2);
            }

            if ($this->program->programType->name == Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += round(($fee->value / 100) * $legible_amount, 2);
            } else {
              $anchor_bearing_fees += round(
                ($fee->anchor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount),
                2
              );
              $vendor_bearing_fees += round(
                ($fee->vendor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount),
                2
              );
            }
          }
        }

        if ($fee->type === 'per amount') {
          if ($fee->charge_type === 'daily') {
            $fees_amount +=
              floor($legible_amount / $fee->per_amount) *
              $fee->value *
              now()->diffInDays(Carbon::parse($invoice->due_date));

            if ($fee->taxes) {
              $fees_tax_amount += round(
                ($fee->taxes / 100) *
                  (floor($legible_amount / $fee->per_amount) *
                    $fee->value *
                    now()->diffInDays(Carbon::parse($invoice->due_date))),
                2
              );
            }

            if ($this->program->programType->name == Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += round(
                floor($legible_amount / $fee->per_amount) *
                  $fee->value *
                  now()->diffInDays(Carbon::parse($invoice->due_date)),
                2
              );
            } else {
              $anchor_bearing_fees += round(
                ($fee->anchor_bearing_discount / 100) *
                  (floor($legible_amount / $fee->per_amount) *
                    $fee->value *
                    now()->diffInDays(Carbon::parse($invoice->due_date))),
                2
              );
              $vendor_bearing_fees += round(
                ($fee->vendor_bearing_discount / 100) *
                  (floor($legible_amount / $fee->per_amount) *
                    $fee->value *
                    now()->diffInDays(Carbon::parse($invoice->due_date))),
                2
              );
            }
          } else {
            $fees_amount += floor($legible_amount / $fee->per_amount) * $fee->value;

            if ($fee->taxes) {
              $fees_tax_amount += round(
                ($fee->taxes / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
                2
              );
            }

            if ($this->program->programType->name == Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += round(floor($legible_amount / $fee->per_amount) * $fee->value, 2);
            } else {
              $anchor_bearing_fees += round(
                ($fee->anchor_bearing_discount / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
                2
              );
              $vendor_bearing_fees += round(
                ($fee->vendor_bearing_discount / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
                2
              );
            }
          }
        }
      }
    }

    $discount =
      ($vendor_discount_details->vendor_discount_bearing / $total_roi) *
      ($eligibility / 100) *
      $total_amount *
      ($total_roi / 100) *
      ((now()->diffInDays(Carbon::parse($invoice->due_date)) + 1) / 365);

    $original_discount =
      ($eligibility / 100) *
      $total_amount *
      ($total_roi / 100) *
      ((now()->diffInDays(Carbon::parse($invoice->due_date)) + 1) / 365);

    // Tax on discount
    $discount_tax_amount = 0;
    if ($tax_on_discount && $tax_on_discount > 0) {
      $discount_tax_amount = ($tax_on_discount / 100) * $original_discount;
    }

    if ($invoice->program->programType->name == Program::VENDOR_FINANCING) {
      if ($invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        return [
          $invoice->program->anchor->name,
          $invoice->invoice_number,
          number_format($invoice->total, 2),
          Carbon::parse($invoice->invoice_date)->format('d M Y'),
          Carbon::parse($invoice->due_date)->format('d M Y'),
          Str::title($invoice->status),
          number_format($invoice->invoice_total_amount, 2),
          number_format($discount + $vendor_bearing_fees + $fees_tax_amount + $discount_tax_amount, 2),
          number_format($invoice->actual_remittance_amount, 2),
        ];
      } else {
        return [
          $invoice->buyer->name,
          $invoice->invoice_number,
          number_format($invoice->total, 2),
          Carbon::parse($invoice->invoice_date)->format('d M Y'),
          Carbon::parse($invoice->due_date)->format('d M Y'),
          Str::title($invoice->status),
          number_format($invoice->invoice_total_amount, 2),
          number_format($discount + $vendor_bearing_fees + $fees_tax_amount + $discount_tax_amount, 2),
          number_format($invoice->actual_remittance_amount, 2),
        ];
      }
    }
  }
}
