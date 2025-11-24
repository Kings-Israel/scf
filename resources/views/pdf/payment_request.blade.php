<!DOCTYPE html>
<html>
<head>
  @php
    $favicon = asset('assets/img/favicon/favicon.ico');
  @endphp
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="{{ $favicon }}" />
  <link rel="apple-touch-icon" sizes="180x180" href="{{ $favicon }}">
  <link rel="icon" type="image/png" sizes="32x32" href="{{ $favicon }}">
  <link rel="icon" type="image/png" sizes="16x16" href="{{ $favicon }}">
    <title>Payment Request - {{ $payment_request->reference_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12px;
        }
        .container {
            width: 90%;
            margin: 0 auto;
        }
        .header, .footer {
            text-align: center;
            margin: 20px 0;
        }
        .header > img {
          width: 50%;
        }
        .header h1, .footer p {
            margin: 0;
        }
        .header h1 {
            font-size: 24px;
            color: #333;
        }
        .footer p {
            font-size: 10px;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 auto;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
            color: #333;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        caption {
            padding: 10px;
            font-size: 14px;
            font-weight: bold;
            color: #555;
        }
        .taxes {
          width: 40%;
          margin-right: 0px;
          margin-top: 5px;
        }
        .fees {
          width: 40%;
          margin-right: 0px;
          margin-top: 5px;
        }
        .discount {
          width: 40%;
          margin-right: 0px;
          margin-top: 5px;
        }
        .total {
          width: 40%;
          margin-right: 0px;
          margin-top: 5px;
        }
        .invoice-details {
          display: flex;
          justify-content: space-between;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
          {{-- <img src="{{ $header }}" alt="Logo" class=""> --}}
          <h4>Payment Request Details</h4>
        </div>
        <div class="invoice-details">
          <table>
            <tbody>
              <tr>
                <td><strong>{{ __('Reference Number')}}</strong></td>
                <td>{{ $payment_request->reference_number }}</td>
              </tr>
              <tr>
                <td><strong>{{ __('Anchor')}}</strong></td>
                <td>{{ $payment_request->invoice->buyer ? $payment_request->invoice->buyer->name : $payment_request->invoice->program->anchor->name }}</td>
              </tr>
              <tr>
                <td><strong>{{ __('Vendor')}}</strong></td>
                <td>{{ $payment_request->invoice->buyer ? $payment_request->invoice->program->anchor->name : $payment_request->invoice->company->name }}</td>
              </tr>
              <tr>
                <td><strong>{{ __('Invoice Number')}}</strong></td>
                <td>{{ $payment_request->invoice->invoice_number }}</td>
              </tr>
              <tr>
                <td><strong>{{ __('PI Number')}}</strong></td>
                <td>{{ $payment_request->invoice->pi_number }}</td>
              </tr>
              <tr>
                <td><strong>{{ __('Invoice Amount')}}</strong></td>
                <td>{{ number_format($payment_request->invoice->invoice_total_amount, 2) }}</td>
              </tr>
              <tr>
                <td><strong>{{ __('Eligibility')}}</strong></td>
                <td>{{ number_format($payment_request->invoice->vendor_configurations->eligibility, 2) }}</td>
              </tr>
              <tr>
                <td><strong>{{ __('Eligible Payment Amount')}}</strong></td>
                <td>{{ number_format($payment_request->eligible_for_finance, 2) }}</td>
              </tr>
              <tr>
                <td><strong>{{ __('Request Date')}}</strong></td>
                <td>{{ $payment_request->created_at->format('d M Y') }}</td>
              </tr>
              <tr>
                <td><strong>{{ __('Payment Date')}}</strong></td>
                <td>{{ Carbon\Carbon::parse($payment_request->payment_request_date)->format('d M Y') }}</td>
              </tr>
              <tr>
                <td><strong>{{ __('Maturity Date')}}</strong></td>
                <td>{{ Carbon\Carbon::parse($payment_request->invoice->due_date)->format('d M Y') }}</td>
              </tr>
              @if ($payment_request->paymentAccounts->count() > 0)
                  @foreach ($payment_request->paymentAccounts as $payment_account)
                    @if ($payment_account->type != 'vendor_account' && $payment_account->type != 'program_fees')
                      <tr>
                        <td>{{ Str::title(Str::replace('_', ' ', $payment_account->type)) }}</td>
                        <td>{{ number_format($payment_account->amount, 2) }}</td>
                      </tr>
                    @endif
                  @endforeach
              @endif
              <tr>
                <td><strong>{{ __('Net Disbursal Amount')}}</strong></td>
                <td>{{ number_format($payment_request->amount, 2) }}</td>
              </tr>
              <tr>
                <td>{{ __('Status') }}</td>
                <td>{{ Str::title($payment_request->approval_stage) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
    </div>
</body>
</html>
