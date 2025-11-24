<?php

namespace App\Jobs;

use App\Models\AuthorizationMatrixRule;
use App\Models\Company;
use App\Models\CompanyAuthorizationMatrix;
use App\Models\CompanyUserAuthorizationGroup;
use App\Models\Invoice;
use App\Models\InvoiceApproval;
use App\Models\InvoiceFee;
use App\Models\Program;
use App\Models\ProgramVendorBankDetail;
use App\Models\ProgramVendorConfiguration;
use App\Models\User;
use App\Notifications\InvoiceUpdated;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class BulkApproveInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public array $invoices, public array $updated_invoice_fees, public array $anchor_users, public Company $company)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
      try {
        DB::beginTransaction();

        foreach ($this->invoices as $invoice_id) {
          $invoice = Invoice::find($invoice_id);

          $current_anchor_approvals = InvoiceApproval::where('invoice_id', $invoice->id)->whereIn('user_id', $this->anchor_users)->count();

          $invoice_setting = $invoice->company->invoiceSetting;
          if (count($this->updated_invoice_fees) > 0) {
            foreach ($this->updated_invoice_fees as $invoice_fee_key => $invoice_fee) {
              foreach ($invoice_fee as $data_id => $fee) {
                if ($data_id == $invoice->id) {
                  foreach ($fee as $fee_data) {
                    foreach ($fee_data as $fee_name => $data) {
                      $current_invoice_fee = InvoiceFee::where('invoice_id', $invoice->id)->where('name', $fee_name)->first();
                      if ($current_invoice_fee) {
                        $current_invoice_fee->amount = $fee_name != 'Credit Note Amount' ? (float) round(((((float) $data) / 100) * ($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount)), 2) : (float) $data;

                        if (count($current_invoice_fee->getDirty()) > 0) {
                          if ($current_anchor_approvals >= 1) {
                            // Remove all approvals, to go back to maker for approval
                            InvoiceApproval::whereIn('user_id', $this->anchor_users)->delete();
                          }
                        }
                        $current_invoice_fee->save();
                      } else {
                        $invoice_fee = new InvoiceFee;
                        $invoice_fee->invoice_id = $invoice->id;
                        $invoice_fee->name = $fee_name;
                        $invoice_fee->amount = $fee_name != 'Credit Note Amount' ? (float) ((((float) $data) / 100) * ($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount)) : (float) $data;
                        $invoice_fee->save();
                      }
                    }
                  }
                }
              }
            }
          }

          $program = Program::find($invoice->program_id);
          $utilized_amount = $program->utilized_amount;

          $invoice_total_amount =
            $invoice->total +
            $invoice->total_invoice_taxes -
            $invoice->total_invoice_fees -
            $invoice->total_invoice_discount;

          // Check if auto finance is enabled
          $company_invoice_setting = $invoice->company->programConfigurations
            ->where('program_id', $invoice->program_id)
            ->first();

          $vendor_configurations = ProgramVendorConfiguration::where('company_id', $invoice->company_id)
            ->where('program_id', $invoice->program_id)
            ->first();

          $invoice->approvals()->create([
            'user_id' => auth()->id(),
          ]);

          $approvals = InvoiceApproval::where('invoice_id', $invoice->id)->whereIn('user_id', $this->company->users->pluck('id'))->count();

          // Get authorization matrix
          $authorization_matrix = CompanyAuthorizationMatrix::where('company_id', $invoice->program->anchor->id)
            ->where('min_pi_amount', '<=', $invoice->total + $invoice->total_invoice_taxes)
            ->where('max_pi_amount', '>=', $invoice->total + $invoice->total_invoice_taxes)
            ->where('status', 'active')
            ->where('program_type_id', $invoice->program->program_type_id)
            ->first();

          $rules = AuthorizationMatrixRule::where('matrix_id', $authorization_matrix->id)->get();

          // Calculate number of required approvals based on rules
          $required_approvals = 0;
          foreach ($rules as $rule) {
            if (!$rule->operator) {
              $required_approvals += $rule->min_approval;
            } elseif ($rule->operator == 'and') {
              $required_approvals += $rule->min_approval;
            } else {
              $user_authorization_group = CompanyUserAuthorizationGroup::where('company_id', $invoice->program->anchor->id)->where('group_id', $rule->group_id)->where('user_id', auth()->id())->first();
              if ($user_authorization_group) {
                $user_rule = AuthorizationMatrixRule::where('matrix_id', $authorization_matrix->id)->where('group_id', $user_authorization_group->group_id)->first();
                $required_approvals = $user_rule->min_approval;
              }
            }
          }

          if ($required_approvals > 0) {
            if ($approvals < $required_approvals) {
              $invoice->update([
                'stage' => 'pending_checker',
              ]);

              // Company has approvers
              $users = User::whereIn('id', $this->company->users->pluck('id'))
                ->where('id', '!=', auth()->id())
                ->whereHas('roles', function ($query) {
                  $query->whereHas('permissions', function ($query) {
                    $query->where('name', 'Approve Invoices - Level 2');
                  });
                })
                ->get();

              if ($users->count() > 1) {
                foreach ($users as $user) {
                  SendMail::dispatchAfterResponse($user->email, 'InvoiceUpdated', [
                    'id' => $invoice->id,
                    'type' => 'vendor_financing',
                  ]);
                }
              }
            } else {
              // Check if all approvals are done and change to approved
              $invoice->update([
                'status' => 'approved',
                'stage' => 'approved',
                'pi_number' => 'PI_' . $invoice->id,
                'eligible_for_financing' => Carbon::parse($invoice->due_date)->subDays($invoice->program->min_financing_days) > now() ? true : false,
                'total_amount' => $invoice->total_amount ? $invoice->total : $invoice->total_amount,
              ]);

              $invoice->company->notify(new InvoiceUpdated($invoice));

              // Auto request finance
              if ($company_invoice_setting && $company_invoice_setting->auto_request_finance) {
                // Check if request will exceed vendor program limit
                $vendor_program_limit = $invoice->company->getProgramLimit($program);
                $vendor_utilized_amount = $invoice->company->getUtilizedAmount($program);

                if (
                  $vendor_utilized_amount + $invoice_total_amount < $vendor_program_limit &&
                  $utilized_amount + $invoice_total_amount < $program->program_limit
                ) {
                  $bank_details = ProgramVendorBankDetail::where('company_id', $invoice->company_id)->first();

                  $invoice->requestFinancing($vendor_configurations, $bank_details->id, now()->format('Y-m-d'));
                }
              }
            }

            activity($invoice->program->bank->id)
              ->causedBy(auth()->user())
              ->performedOn($invoice)
              ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Anchor'])
              ->log('approved invoice');
          }
        }

        DB::commit();
      } catch (\Throwable $th) {
        //throw $th;
        info($th);
        DB::rollBack();
      }
    }
}
