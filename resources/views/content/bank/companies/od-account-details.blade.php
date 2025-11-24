@extends('layouts/layoutMaster')

@section('title', 'OD Accounts')

@section('vendor-style')
@endsection

@section('page-style')
<style>
  .table-responsive .dropdown,
  .table-responsive .btn-group,
  .table-responsive .btn-group-vertical {
    position: static;
  }
</style>
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/moment/moment.js')}}"></script>
@endsection

@section('page-script')

@endsection

@section('content')
<div class="d-flex justify-content-between">
  <h4 class="fw-bold mb-2">
    <span class="fw-light">{{ __('OD Account Details')}}</span>
  </h4>
  <div class="d-flex">
    <a target="_blank" href="{{ route('companies.od-accounts.discount-account-details', ['bank' => $bank, 'program_vendor_configuration' => $program_vendor_configuration]) }}" class="btn btn-primary btn-sm mx-1">{{ __('Daily Account Details')}}</a>
    <a target="_blank" href="{{ route('companies.od-accounts.total-outstanding', ['bank' => $bank, 'program_vendor_configuration' => $program_vendor_configuration]) }}" class="btn btn-primary btn-sm mx-1">{{ __('Total Outstanding')}}</a>
    <a target="_blank" href="{{ route('companies.od-accounts.daily-interest', ['bank' => $bank, 'program_vendor_configuration' => $program_vendor_configuration]) }}" class="btn btn-primary btn-sm mx-1">{{ __('Daily Discount Details')}}</a>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="row">
      <div class="col-sm-4">
        <div class="d-flex justify-content-between">
          <h5>{{ __('Payment/OD Account Number')}}</h5>
          <h6>{{ $program_vendor_configuration->payment_account_number }}</h6>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="d-flex justify-content-between">
          <h5>{{ __('Dealer')}}</h5>
          <h6>
            <a href="{{ route('companies.show', ['bank' => $bank, 'company' => $program_vendor_configuration->company]) }}">
              {{ $program_vendor_configuration->company->name }}
            </a>
          </h6>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="d-flex justify-content-between">
          <h5>{{ __('Anchor')}}</h5>
          <h6>
            <a href="{{ route('companies.show', ['bank' => $bank, 'company' => $program_vendor_configuration->program->anchor]) }}">
              {{ $program_vendor_configuration->program->anchor->name }}
            </a>
          </h6>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="d-flex justify-content-between">
          <h5>{{ __('Sanctioned Limit')}}</h5>
          <h6>{{ number_format($program_vendor_configuration->sanctioned_limit) }}</h6>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="d-flex justify-content-between">
          <h5>{{ __('Available Limit')}}</h5>
          <h6>{{ number_format($program_vendor_configuration->sanctioned_limit - $program_vendor_configuration->company->getUtilizedAmount($program_vendor_configuration->program)) }}</h6>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="d-flex justify-content-between">
          <h5>{{ __('Utilized Limit')}}</h5>
          <h6>{{ number_format($program_vendor_configuration->company->getUtilizedAmount($program_vendor_configuration->program)) }}</h6>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="d-flex justify-content-between">
          <h5>{{ __('Pipleine Requests')}}</h5>
          <h6>{{ number_format($program_vendor_configuration->company->pipelineAmount($program_vendor_configuration->program)) }}</h6>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="d-flex justify-content-between">
          <h5>{{ __('OD Expiry Date')}}</h5>
          <h6>{{ Carbon\Carbon::parse($program_vendor_configuration->limit_expiry_date)->format('d M Y') }}</h6>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="d-flex justify-content-between">
          <h5>{{ __('Total Outstanding')}}</h5>
          <h6>{{ number_format($program_vendor_configuration->company->overdueAmount($program_vendor_configuration->program)) }}</h6>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="d-flex justify-content-between">
          <h5>{{ __('Overdue Days')}}</h5>
          <h6>{{ number_format($program_vendor_configuration->company->daysPastDue($program_vendor_configuration->program)) }}</h6>
        </div>
      </div>
    </div>
  </div>
</div>
<h4 class="fw-bold my-1">
  <span class="fw-light">{{ __('OD Account Summary')}}</span>
</h4>
<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ __('Date')}}</th>
            <th>{{ __('Particulars')}}</th>
            <th>{{ __('Amount')}}</th>
            <th>{{ __('Line Balance')}}</th>
            <th>{{ __('Discount Balance')}}</th>
            <th>{{ __('Penal Discount Balance')}}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @foreach ($cbs_transactions as $cbs_transaction)
            <tr>
              <td>{{ Carbon\Carbon::parse($cbs_transaction->created_at)->format('d M Y')  }}</td>
              <td>
                @if ($cbs_transaction->transaction_type == 'Payment Disbursement')
                  @if ($cbs_transaction->paymentRequest->invoice->program->programType->name == 'Dealer Financing')
                    <span>Drawdown against {{ $cbs_transaction->paymentRequest->invoice->invoice_number }}</span>
                  @else
                    <span>Disbursement for {{ $cbs_transaction->paymentrequest->invoice->invoice_number }}</span>
                  @endif
                @else
                  @if ($cbs_transaction->transaction_type == 'Fees/Charges')
                    @if (Carbon\Carbon::parse($cbs_transaction->paid_date)->greaterThan(Carbon\Carbon::parse($cbs_transaction->paymentRequest->invoice->due_date)))
                      <span>Penal Payment againt {{ $cbs_transaction->paymentRequest->invoice->invoice_number }}</span>
                    @else
                      <span>Repayment for {{ $cbs_transaction->paymentRequest->invoice->invoice_number }}</span>
                    @endif
                  @endif
                @endif
                @if ($cbs_transaction->transaction_type == 'Funds Transfer')
                  <span>Credit Account transaction for OD Account {{ $program_vendor_configuration->payment_account_number }}</span>
                @endif
              </td>
              <td>{{ number_format($cbs_transaction->amount) }}</td>
              <td>{{ number_format($cbs_transaction->amount) }}</td>
              <td>-</td>
              <td>-</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
