<?php

namespace App\Models;

use App\Jobs\SendMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditAccountRequest extends Model
{
  use HasFactory;

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  public function company(): BelongsTo
  {
    return $this->belongsTo(Company::class);
  }

  public function program(): BelongsTo
  {
    return $this->belongsTo(Program::class);
  }

  public function paymentAccounts(): HasMany
  {
    return $this->hasMany(PaymentRequestAccount::class);
  }

  /**
   * Get all of the cbsTransactions for the CreditAccountRequest
   */
  public function cbsTransactions(): HasMany
  {
    return $this->hasMany(CbsTransaction::class);
  }

  public function notifyUsers($type)
  {
    switch ($type) {
      case 'LoanClosing':
        // If dealer financing, notify dealer users
        if ($this->program->programType->name == 'Dealer Financing') {
          $dealer_users = $this->company->users;
          foreach ($dealer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'LoanClosing', ['invoice_id' => $this->invoice_id]);
          }
        }
        // Notify bank RMs users
        $bank_users = $this->program->bankUserDetails;
        foreach ($bank_users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'LoanClosing', ['invoice_id' => $this->invoice_id]);
        }
        break;
      case 'LoanDisbursal':
        // If dealer financing, notify dealer users
        if ($this->program->programType->name == 'Dealer Financing') {
          $dealer_users = $this->company->users;
          foreach ($dealer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'LoanDisbursal', ['invoice_id' => $this->invoice_id]);
          }
          $anchor_users = $this->program->anchor->users;
          foreach ($anchor_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'LoanDisbursal', ['invoice_id' => $this->invoice_id]);
          }
        }
        // Notify bank RMs users
        $bank_users = $this->program->bankUserDetails;
        foreach ($bank_users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'LoanDisbursal', ['invoice_id' => $this->invoice_id]);
        }
        break;
      case 'FullRepayment':
        // If dealer financing, notify dealer users
        if ($this->program->programType->name == 'Dealer Financing') {
          $dealer_users = $this->company->users;
          foreach ($dealer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'FullRepayment', ['invoice_id' => $this->invoice_id]);
          }
        }
        // Notify bank RMs users
        $bank_users = $this->program->bankUserDetails;
        foreach ($bank_users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'FullRepayment', ['invoice_id' => $this->invoice_id]);
        }
        break;
      case 'PartialRepayment':
        // If dealer financing, notify dealer users
        if ($this->program->programType->name == 'Dealer Financing') {
          $dealer_users = $this->company->users;
          foreach ($dealer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'PartialRepayment', ['invoice_id' => $this->invoice_id]);
          }
        }
        // Notify bank RMs users
        $bank_users = $this->program->bankUserDetails;
        foreach ($bank_users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'PartialRepayment', ['invoice_id' => $this->invoice_id]);
        }
        break;
      case 'OverdueFullRepayment':
        // If dealer financing, notify dealer users
        if ($this->program->programType->name == 'Dealer Financing') {
          $dealer_users = $this->company->users;
          foreach ($dealer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'OverdueFullRepayment', [
              'invoice_id' => $this->invoice_id,
              'amount' => $this->overdue_amount,
            ]);
          }
        }
        break;
      case 'InvoicePaymentProcessed':
        // If dealer financing, notify dealer users
        if ($this->program->programType->name == 'Dealer Financing') {
          $dealer_users = $this->company->users;
          foreach ($dealer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'InvoicePaymentProcessed', [
              'invoice_id' => $this->invoice_id,
            ]);
          }
        }
        break;
      case 'BalanceInvoicePayment':
        // If dealer financing, notify dealer users
        if ($this->program->programType->name == 'Dealer Financing') {
          $dealer_users = $this->company->users;
          foreach ($dealer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'BalanceInvoicePayment', ['invoice_id' => $this->invoice_id]);
          }
        }
        break;
      case 'InvoicePaymentReceivedBySeller':
        // If dealer financing, notify dealer users
        if ($this->program->programType->name == 'Dealer Financing') {
          $dealer_users = $this->company->users;
          foreach ($dealer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'InvoicePaymentReceivedBySeller', [
              'invoice_id' => $this->invoice_id,
            ]);
          }
        }
        break;
      case 'PaymentRequestRejection':
        // Send to maker approver
        $maker = $this->approvals()
          ->pluck('user_id')
          ->first();
        $user = User::find($maker);
        if (auth()->check()) {
          SendMail::dispatchAfterResponse($user->email, 'PaymentRequestRejection', [
            'payment_request_id' => $this->id,
            'user_name' => auth()->user()->name,
          ]);
        }
        break;

      default:
        # code...
        break;
    }
  }
}
