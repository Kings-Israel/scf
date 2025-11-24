<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationOnPaymentRequestUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
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

      // TODO: Refactor this to take users after the DB commit is done
      // // Send Notification to checker bank user
      // $bank_users = $bank->users;
      // foreach ($bank_users as $user) {
      //   if ($payment_request->invoice->program->programType->name == Program::VENDOR_FINANCING) {
      //     if ($user->id != auth()->id() && $user->hasPermissionTo('Approve Vendor Financing Requests Level 2')) {
      //       SendMail::dispatchAfterResponse($user->email, 'FinancingRequestApproved', [
      //         'financing_request' => $payment_request->id,
      //         'url' => config('app.url') . '/' . $bank->url,
      //         'name' => $user->name,
      //         'approver_name' => auth()->user()->name,
      //         'type' => 'vendor_financing',
      //       ]);
      //     }
      //   }
      //   if ($payment_request->invoice->program->programType->name == Program::DEALER_FINANCING) {
      //     if ($user->id != auth()->id() && $user->hasPermissionTo('Approve Dealer Financing Requests Level 2')) {
      //       SendMail::dispatchAfterResponse($user->email, 'FinancingRequestApproved', [
      //         'financing_request' => $payment_request->id,
      //         'url' => config('app.url') . '/' . $bank->url,
      //         'name' => $user->name,
      //         'approver_name' => auth()->user()->name,
      //         'type' => 'dealer_financing',
      //       ]);
      //     }
      //   }
      // }

      // Send mail to company concerning status update
        // $company_users = $payment_request->invoice->company->users;
        // foreach ($company_users as $company_user) {
        //   if ($payment_request->invoice->program->programType->name == Program::VENDOR_FINANCING) {
        //     if ($payment_request->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        //       SendMail::dispatchAfterResponse($company_user->email, 'FinancingRequestRejected', [
        //         'financing_request' => $payment_request->id,
        //         'url' => config('app.url'),
        //         'name' => $company_user->name,
        //         'type' => 'vendor_financing',
        //       ]);
        //     } else {
        //       SendMail::dispatchAfterResponse($company_user->email, 'FinancingRequestRejected', [
        //         'financing_request' => $payment_request->id,
        //         'url' => config('app.url'),
        //         'name' => $company_user->name,
        //         'type' => 'factoring',
        //       ]);
        //     }
        //   } else {
        //     SendMail::dispatchAfterResponse($company_user->email, 'FinancingRequestRejected', [
        //       'financing_request' => $payment_request->id,
        //       'url' => config('app.url'),
        //       'name' => $company_user->name,
        //       'type' => 'dealer_financing',
        //     ]);
        //   }
        // }

        // Send mail to anchor company users
            // $anchor_company_users = $payment_request->invoice->program->anchor->users;
            // // DB::afterCommit();
            // foreach ($anchor_company_users as $company_user) {
            //   if (
            //     $payment_request->invoice->program->programCode &&
            //     ($payment_request->invoice->program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
            //       $payment_request->invoice->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
            //   ) {
            //     SendMail::dispatchAfterResponse($company_user->email, 'FinancingRequestUpdated', [
            //       'financing_request' => $payment_request->id,
            //       'url' => config('app.url'),
            //       'name' => $company_user->name,
            //       'type' => 'factoring',
            //     ]);
            //   } else {
            //     SendMail::dispatchAfterResponse($company_user->email, 'FinancingRequestUpdated', [
            //       'financing_request' => $payment_request->id,
            //       'url' => config('app.url'),
            //       'name' => $company_user->name,
            //       'type' => 'vendor_financing',
            //     ]);
            //   }
            // }

            // // Send mail to company concerning status update
            // $company_users = $payment_request->invoice->company->users;
            // foreach ($company_users as $company_user) {
            //   if ($payment_request->invoice->program->programType->name == Program::VENDOR_FINANCING) {
            //     if ($payment_request->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
            //       SendMail::dispatchAfterResponse($company_user->email, 'FinancingRequestUpdated', [
            //         'financing_request' => $payment_request->id,
            //         'url' => config('app.url'),
            //         'name' => $company_user->name,
            //         'type' => 'vendor_financing',
            //       ]);
            //     } else {
            //       SendMail::dispatchAfterResponse($company_user->email, 'FinancingRequestUpdated', [
            //         'financing_request' => $payment_request->id,
            //         'url' => config('app.url'),
            //         'name' => $company_user->name,
            //         'type' => 'factoring',
            //       ]);
            //     }
            //   } else {
            //     SendMail::dispatchAfterResponse($company_user->email, 'FinancingRequestUpdated', [
            //       'financing_request' => $payment_request->id,
            //       'url' => config('app.url'),
            //       'name' => $company_user->name,
            //       'type' => 'dealer_financing',
            //     ]);
            //   }
            // }
    }
}
