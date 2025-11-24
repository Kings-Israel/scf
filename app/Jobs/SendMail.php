<?php

namespace App\Jobs;

use App\Mail\FLDG;
use App\Mail\LoginOtp;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Program;
use App\Mail\PoCreation;
use App\Mail\StopSupply;
use App\Models\Document;
use App\Mail\LoanClosing;
use App\Mail\LoanOverdue;
use App\Mail\PoRejection;
use App\Mail\PoAcceptance;
use App\Mail\DueDateChange;
use App\Mail\FullRepayment;
use App\Mail\LoanDisbursal;
use App\Mail\ResetPassword;
use App\Mail\CompanyCreated;
use App\Mail\CompanyUpdated;
use App\Mail\InterestPosted;
use App\Mail\InvoiceCreated;
use App\Mail\InvoiceUpdated;
use App\Mail\ProgramChanged;
use App\Mail\ProgramCreated;
use App\Mail\AutoDebitAnchor;
use App\Mail\CompanyApproved;
use App\Mail\DocumentUpdated;
use App\Mail\InvoiceApproved;
use App\Mail\MappedToCompany;
use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use App\Mail\InvoiceRejection;
use App\Mail\PartialRepayment;
use App\Mail\PaymentRequested;
use App\Mail\RequestDocuments;
use App\Mail\RequestToUnblock;
use App\Models\CbsTransaction;
use App\Models\PaymentRequest;
use App\Mail\CompanyActivation;
use App\Mail\DfInterestPosting;
use App\Mail\DocumentsUploaded;
use App\Mail\IfPaymentReminder;
use App\Models\CompanyDocument;
use App\Mail\DisbursementFailed;
use App\Mail\FundingLimitChange;
use App\Mail\IfMaturingLoanList;
use App\Mail\CompanyDeactivation;
use App\Mail\DfMaturingLoansList;
use App\Mail\OverdueFullRepayment;
use App\Mail\BalanceInvoicePayment;
use App\Mail\BulkInvoicePaymentReceivedBySeller;
use App\Mail\BulkLoanClosing;
use App\Mail\BulkLoanDisbursal;
use App\Mail\BulkPaymentReminder;
use App\Mail\BulkRequestFinance;
use App\Mail\CompanyDetailsUpdated;
use App\Mail\ConfigurationsChanged;
use App\Mail\ProgramChangesUpdated;
use App\Mail\DrawdownOverdueBalance;
use Illuminate\Support\Facades\Mail;
use App\Mail\FinancingRequestUpdated;
use App\Mail\InvoicePaymentProcessed;
use App\Mail\PaymentRequestRejection;
use App\Mail\FinancingRequestApproved;
use App\Mail\FinancingRequestRejected;
use App\Mail\PaymentRequestGeneration;
use Illuminate\Queue\SerializesModels;
use App\Mail\FinancingRequestsApproved;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use App\Mail\ProductConfigurationApproval;
use App\Mail\RepaymentReceivedForOdAccount;
use App\Mail\RequestToIncreaseFundingLimit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Mail\CbsTransactionForOdCreditFailed;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Mail\CbsTransactionForLoanSettlementFailed;
use App\Mail\CompanyUserChanged;
use App\Mail\CompanyUserCreated;
use App\Mail\ConsolidateInvoiceApproval;
use App\Mail\InvoicePaymentReceivedBySeller;
use App\Mail\PasswordExpired;
use App\Mail\PasswordExpireSoon;
use App\Mail\PasswordExpiry;
use App\Mail\PipelineStageUpdated;
use App\Mail\ProgramMappingChanged;
use App\Models\BankUser;
use App\Models\CompanyUser;
use App\Models\EmailTemplate;
use App\Models\Pipeline;
use App\Models\ProgramVendorConfiguration;
use App\Models\ProposedConfigurationChange;
use App\Models\User;

class SendMail implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public $brand_img;
  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct(public string $to, public string $mailable, public array $content)
  {
    $this->brand_img = config('app.url') . '/assets/img/branding/logo-name.png';
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {
    if (config('app.env') == 'local' || config('app.env') == 'uat') {
      return true;
    }

    if (!filter_var($this->to, FILTER_VALIDATE_EMAIL)) {
      return true;
    }

    // Check if user is active
    $user = User::where('email', $this->to)
      ->where('is_active', true)
      ->whereNotIn('email', ['admin@yofinvoice.com', 'adm@yofinvoice.com'])
      ->first();

    if (!$user) {
      // return true;
    } else {
      // User only receives notifications on SMS
      if ($user->receive_notifications === 'sms') {
        return true;
      }

      // Bank User
      $bank_user = BankUser::where('user_id', $user->id)->first();
      // Company User
      $company_user = CompanyUser::where('user_id', $user->id)->first();

      // Don't send mail if user is inactive
      if ($bank_user && !$bank_user->active) {
        return true;
      }

      if ($company_user && !$company_user->active) {
        return true;
      }

      switch ($this->mailable) {
        case 'LoginOtp':
          Mail::to($this->to)->send(new LoginOtp($this->content['otp'], $this->brand_img));
          break;
        case 'ResetPassword':
          Mail::to($this->to)->send(
            new ResetPassword($this->content['user_name'], $this->content['link'], $this->brand_img)
          );
          break;
        case 'PasswordExpiresSoon':
          $user = User::find($this->content['user']);
          Mail::to($this->to)->send(new PasswordExpiry($user, $this->content['link'], $this->brand_img));
          break;
        case 'PasswordExpired':
          $user = User::find($this->content['user']);
          Mail::to($this->to)->send(new PasswordExpired($user, $this->content['link'], $this->brand_img));
          break;
        case 'CompanyCreated':
          $company = Company::find($this->content['company']);
          Mail::to($this->to)->send(new CompanyCreated($company, $this->brand_img));
          break;
        case 'CompanyUpdated':
          $company = Company::find($this->content['company']);
          Mail::to($this->to)->send(new CompanyUpdated($company, $this->brand_img));
          break;
        case 'CompanyApproved':
          Mail::to($this->to)->send(
            new CompanyApproved($this->content['company'], $this->content['name'], $this->brand_img)
          );
          break;
        case 'CompanyActivated':
          $company = Company::find($this->content['company_id']);
          Mail::to($this->to)->send(new CompanyActivation($company, $this->brand_img));
          break;
        case 'CompanyDeactivated':
          $company = Company::find($this->content['company_id']);
          Mail::to($this->to)->send(new CompanyDeactivation($company, $this->brand_img));
          break;
        case 'CompanyUserCreated':
          $user = User::find($this->content['user_id']);
          Mail::to($this->to)->send(new CompanyUserCreated($user, $this->content['user_name'], $this->brand_img));
          break;
        case 'CompanyUserChanged':
          $user = User::find($this->content['user_id']);
          Mail::to($this->to)->send(new CompanyUserChanged($user, $this->content['user_name'], $this->brand_img));
          break;
        case 'DocumentUpdated':
          $document = Document::find($this->content['document_id']);
          if (!$document) {
            $document = CompanyDocument::find($this->content['document_id']);
          }
          Mail::to($this->to)->send(
            new DocumentUpdated(
              $this->content['user_name'],
              $document,
              $this->content['status'],
              $this->content['url'],
              $this->brand_img
            )
          );
          break;
        case 'DocumentsUploaded':
          $company = Company::find($this->content['company_id']);
          Mail::to($this->to)->send(new DocumentsUploaded($company, $this->content['documents'], $this->brand_img));
          break;
        case 'MappedToCompany':
          Mail::to($this->to)->send(new MappedToCompany($this->content, $this->brand_img));
          break;
        case 'RequestDocuments':
          Mail::to($this->to)->send(
            new RequestDocuments($this->content['link'], $this->content['documents'], $this->brand_img)
          );
          break;
        case 'ProgramCreated':
          $program = Program::find($this->content['program_id']);
          Mail::to($this->to)->send(new ProgramCreated($program, $this->brand_img));
          break;
        case 'ProgramChanged':
          Mail::to($this->to)->send(
            new ProgramChanged(
              $this->content['program'],
              $this->content['url'],
              $this->content['name'],
              $this->content['type'],
              $this->brand_img
            )
          );
          break;
        case 'FinancingRequestUpdated':
          Mail::to($this->to)->send(
            new FinancingRequestUpdated(
              $this->content['financing_request'],
              $this->content['url'],
              $this->content['name'],
              $this->content['type'],
              $this->brand_img
            )
          );
          break;
        case 'FinancingRequestApproved':
          Mail::to($this->to)->send(
            new FinancingRequestApproved(
              $this->content['financing_request'],
              $this->content['url'],
              $this->content['name'],
              $this->content['approver_name'],
              $this->content['type'],
              $this->brand_img
            )
          );
          break;
        case 'FinancingRequestsApproved':
          Mail::to($this->to)->send(
            new FinancingRequestsApproved($this->content['financing_requests'], $this->brand_img)
          );
          break;
        case 'FinancingRequestRejected':
          Mail::to($this->to)->send(
            new FinancingRequestRejected(
              $this->content['financing_request'],
              $this->content['url'],
              $this->content['name'],
              $this->content['type'],
              $this->brand_img
            )
          );
          break;
        case 'CompanyDetailsUpdated':
          $company = Company::find($this->content['company_id']);
          Mail::to($this->to)->send(
            new CompanyDetailsUpdated($company, $this->content['name'], $this->content['url'], $this->brand_img)
          );
          break;
        case 'DisbursementFailed':
          Mail::to($this->to)->send(
            new DisbursementFailed(
              $this->content['id'],
              $this->content['url'],
              $this->content['name'],
              $this->content['type'],
              $this->brand_img
            )
          );
          break;
        case 'InterestPosted':
          Mail::to($this->to)->send(
            new InterestPosted(
              $this->content['invoice'],
              $this->content['principle_amount'],
              $this->content['amount'],
              $this->content['type'],
              $this->brand_img
            )
          );
          break;
        case 'DfInterestPosting':
          $invoice = Invoice::find($this->content['invoice']);
          Mail::to($this->to)->send(new DfInterestPosting($invoice, $this->brand_img));
          break;
        case 'ProgramChangesUpdated':
          $program = Program::find($this->content['program_id']);
          Mail::to($this->to)->send(
            new ProgramChangesUpdated(
              $program,
              $this->content['status'],
              $this->content['user'],
              $this->content['link'],
              $this->brand_img
            )
          );
          break;
        case 'LoanClosing':
          $invoice = Invoice::find($this->content['invoice_id']);
          Mail::to($this->to)->send(new LoanClosing($invoice, $this->brand_img));
          break;
        case 'LoanDisbursal':
          $invoice = Invoice::find($this->content['invoice_id']);
          Mail::to($this->to)->send(new LoanDisbursal($invoice, $this->brand_img));
          break;
        case 'InvoicePaymentReceivedBySeller':
          $invoice = Invoice::find($this->content['invoice_id']);
          Mail::to($this->to)->send(new InvoicePaymentReceivedBySeller($invoice, $this->brand_img));
          break;
        case 'BulkLoanDisbursal':
          Mail::to($this->to)->send(new BulkLoanDisbursal($this->content['invoices'], $this->brand_img));
          break;
        case 'BulkLoanClosing':
          Mail::to($this->to)->send(new BulkLoanClosing($this->content['invoices'], $this->brand_img));
          break;
        case 'BulkInvoicePaymentReceivedBySeller':
          Mail::to($this->to)->send(
            new BulkInvoicePaymentReceivedBySeller($this->content['invoices'], $this->brand_img)
          );
          break;
        case 'LoanOverdue':
          $invoice = Invoice::find($this->content['invoice_id']);
          Mail::to($this->to)->send(new LoanOverdue($invoice, $this->brand_img));
          break;
        case 'DrawdownOverdueBalance':
          $invoice = Invoice::find($this->content['invoice_id']);
          Mail::to($this->to)->send(new DrawdownOverdueBalance($invoice, $this->brand_img));
          break;
        case 'AutoDebitAnchor':
          $invoice = Invoice::find($this->content['invoice_id']);
          Mail::to($this->to)->send(new AutoDebitAnchor($invoice, $this->content['amount'], $this->brand_img));
          break;
        case 'OverdueFullRepayment':
          $invoice = Invoice::find($this->content['invoice_id']);
          Mail::to($this->to)->send(new OverdueFullRepayment($invoice, $this->content['amount'], $this->brand_img));
          break;
        case 'FullRepayment':
          $invoice = Invoice::find($this->content['invoice_id']);
          Mail::to($this->to)->send(new FullRepayment($invoice, $this->brand_img));
          break;
        case 'PartialRepayment':
          $invoice = Invoice::find($this->content['invoice_id']);
          Mail::to($this->to)->send(new PartialRepayment($invoice, $this->brand_img));
          break;
        case 'ProductConfigurationsApproval':
          Mail::to($this->to)->send(new ProductConfigurationApproval($this->content['user_name'], $this->brand_img));
          break;
        case 'DfMaturingLoansList':
          Mail::to($this->to)->send(
            new DfMaturingLoansList($this->content['invoices'], $this->content['end_date'], $this->brand_img)
          );
          break;
        case 'IfMaturingLoansList':
          Mail::to($this->to)->send(
            new IfMaturingLoanList($this->content['invoices'], $this->content['end_date'], $this->brand_img)
          );
          break;
        case 'IfPaymentReminder':
          $invoice = Invoice::find($this->content['invoice_id']);
          Mail::to($this->to)->send(new IfPaymentReminder($invoice, $this->brand_img));
          break;
        case 'RepaymentReceivedForOdAccount':
          Mail::to($this->to)->send(
            new RepaymentReceivedForOdAccount(
              $this->content['od_account'],
              $this->content['amount'],
              $this->content['distributor_name'],
              $this->content['debit_from'],
              $this->content['name'],
              $this->brand_img
            )
          );
          break;
        case 'FundingLimitChange':
          $company = Company::find($this->content['company_id']);
          Mail::to($this->to)->send(
            new FundingLimitChange($company, $this->content['old_value'], $this->content['new_value'], $this->brand_img)
          );
          break;
        case 'CbsTransactionForLoanSettlementFailed':
          $cbs_transaction = CbsTransaction::find($this->content['cbs_transaction_id']);
          Mail::to($this->to)->send(new CbsTransactionForLoanSettlementFailed($cbs_transaction, $this->brand_img));
          break;
        case 'CbsTransactionForOdCreditFailed':
          $cbs_transaction = CbsTransaction::find($this->content['cbs_transaction_id']);
          Mail::to($this->to)->send(new CbsTransactionForOdCreditFailed($cbs_transaction, $this->brand_img));
          break;
        case 'InvoicePaymentProcessed':
          $invoice = Invoice::find($this->content['invoice_id']);
          Mail::to($this->to)->send(new InvoicePaymentProcessed($invoice, $this->brand_img));
          break;
        case 'PaymentRequestGeneration':
          $payment_request = PaymentRequest::find($this->content['payment_request_id']);
          Mail::to($this->to)->send(new PaymentRequestGeneration($payment_request, $this->brand_img));
          break;
        case 'PaymentRequestRejection':
          $payment_request = PaymentRequest::find($this->content['payment_request_id']);
          Mail::to($this->to)->send(
            new PaymentRequestRejection($payment_request, $this->content['user_name'], $this->brand_img)
          );
          break;
        case 'BalanceInvoicePayment':
          $invoice = Invoice::find($this->content['invoice_id']);
          Mail::to($this->to)->send(new BalanceInvoicePayment($invoice, $this->brand_img));
          break;
        case 'InvoiceCreated':
          $invoice = Invoice::find($this->content['id']);
          Mail::to($this->to)->send(new InvoiceCreated($invoice, $this->content['type']));
          break;
        case 'InvoiceUpdated':
          $invoice = Invoice::find($this->content['id']);
          Mail::to($this->to)->send(new InvoiceUpdated($invoice, $this->content['type']));
          break;
        case 'InvoiceApproval':
          $invoice = Invoice::find($this->content['id']);
          Mail::to($this->to)->send(new InvoiceApproved($invoice, $this->content['type']));
          break;
        case 'ConsolidateInvoiceApproval':
          $company = Company::find($this->content['company_id']);
          Mail::to($this->to)->send(new ConsolidateInvoiceApproval($company, $this->content['invoices']));
          break;
        case 'InvoiceRejection':
          $invoice = Invoice::find($this->content['invoice_id']);
          Mail::to($this->to)->send(new InvoiceRejection($invoice, $this->content['type']));
          break;
        case 'PaymentRequested':
          $payment_request = PaymentRequest::with('invoice.company')->find($this->content['payment_request_id']);
          Mail::to($this->to)->send(
            new PaymentRequested(
              $payment_request,
              $this->content['link'],
              $this->content['type'],
              $this->content['noa']
            )
          );
          break;
        case 'DueDateChanged':
          $invoice = Invoice::find($this->content['invoice_id']);
          Mail::to($this->to)->send(new DueDateChange($invoice, $this->content['old_date']));
          break;
        case 'PoCreation':
          $purchase_order = PurchaseOrder::find($this->content['purchase_order_id']);
          Mail::to($this->to)->send(new PoCreation($purchase_order));
          break;
        case 'PoAcceptance':
          $purchase_order = PurchaseOrder::find($this->content['purchase_order_id']);
          Mail::to($this->to)->send(new PoAcceptance($purchase_order));
          break;
        case 'PoRejection':
          $purchase_order = PurchaseOrder::find($this->content['purchase_order_id']);
          Mail::to($this->to)->send(new PoRejection($purchase_order));
          break;
        case 'RequestToUnblock':
          $company = Company::find($this->content['company_id']);
          Mail::to($this->to)->send(new RequestToUnblock($company));
          break;
        case 'RequestToIncreaseFundingLimit':
          $company = Company::find($this->content['company_id']);
          Mail::to($this->to)->send(
            new RequestToIncreaseFundingLimit(
              $company,
              $this->content['approved_limit'],
              $this->content['current_exposure'],
              $this->content['pipeline_requests'],
              $this->content['available_limit']
            )
          );
          break;
        case 'ConfigurationsChanged':
          $company = Company::find($this->content['company_id']);
          Mail::to($this->to)->send(new ConfigurationsChanged($company, $this->content['link']));
          break;
        case 'StopSupply':
          $company = Company::find($this->content['company_id']);
          $dealer = Company::find($this->content['dealer_id']);
          Mail::to($this->to)->send(new StopSupply($company, $dealer, $this->content['stop_supply_days']));
          break;
        case 'FLDG':
          $company = Company::find($this->content['company_id']);
          Mail::to($this->to)->send(new FLDG($company, $this->content['dealers']));
          break;
        case 'BulkFinanceRequest':
          Mail::to($this->to)->send(new BulkRequestFinance($this->content['financing_requests'], $this->brand_img));
          break;
        case 'PipelineStageUpdated':
          $pipeline = Pipeline::find($this->content['pipeline_id']);
          Mail::to($this->to)->send(new PipelineStageUpdated($pipeline, $this->content['stage'], $this->brand_img));
          break;
        case 'BulkPaymentReminder':
          Mail::to($this->to)->send(
            new BulkPaymentReminder($this->content['invoices'], $this->content['due_date'], $this->brand_img)
          );
          break;
        case 'ProgramMappingChanged':
          $vendor_configuration = ProgramVendorConfiguration::find($this->content['vendor_configuration_id']);
          $program = Program::find($vendor_configuration->program_id);
          $company = null;
          if ($program->programType->name === Program::DEALER_FINANCING) {
            $company = Company::find($vendor_configuration->company_id);
          } else {
            if ($program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
              $company = Company::find($vendor_configuration->company_id);
            } else {
              $company = Company::find($vendor_configuration->buyer_id);
            }
          }

          $user_name = $this->content['user_name'];

          Mail::to($this->to)->send(new ProgramMappingChanged($program, $company, $user_name, $this->brand_img));
          break;
        default:
          # code...
          break;
      }
    }
  }
}
