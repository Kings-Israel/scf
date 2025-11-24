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
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Payment Instruction - {{ $invoice->invoice_number }}</title>
    <style>
      body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        font-size: 14px;
      }
      .header {
        text-align: right;
        border-bottom: 2px solid #a6a6a6
      }
      .invoice-details-right {
        text-align: right;
        margin-top: -15rem;
        margin-left: 50%;
        width: 50%;
      }
      .titles-left {
        text-align: left;
      }
      .titles-right {
        text-align: right;
        margin-top: -20rem;
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
      .invoice-items {
        margin-top: 20px;
      }
      .invoice-items th {
        background: #000;
        color: #fff;
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
      .subtotal {
        width: 40%;
        margin-right: 0px;
        margin-top: 5px;
      }
      .subtotal tr > td {
        border: none;
      }
      .total {
        background: #d6d6d6;
        width: 40%;
        margin-right: 0px;
        margin-top: 5px;
      }
      .total tr > td {
        border: none;
      }
      .taxes tr > td {
        border: none;
      }
      .fees tr > td {
        border: none;
      }
      .discount tr > td {
        border: none;
      }
      .invoice-title {
        padding-top: 6px;
      }
      .invoice-number {
        width: 80%;
        margin-left: 105px;
      }
    </style>
</head>
<body>
    <div class="">
        <div class="header">
          <h1>PAYMENT INSTRUCTION</h1>
        </div>
        <div class="invoice-details">
          <div class="invoice-details-left">
            <h3>{{ $invoice->buyer ? $invoice->buyer->name : $invoice->program->anchor->name }}</h3>
            <h3>{{ $invoice->buyer ? $invoice->buyer->address.' '.$invoice->buyer->postal_code.' '.$invoice->buyer->city : $invoice->program->anchor->address.' '.$invoice->program->anchor->postal_code.' '.$invoice->program->anchor->city }}</h3>
            <p></p>
            <p>{{ __('Vendor') }}:</p>
            <h3>{{ $invoice->buyer ? $invoice->program->anchor->name : $invoice->company->name }}</h3>
            <h3>{{ $invoice->buyer ? $invoice->program->anchor->address.' '.$invoice->program->anchor->postal_code.' '.$invoice->program->anchor->city : $invoice->company->address.' '.$invoice->company->postal_code.' '.$invoice->company->city }}</h3>
          </div>
          <div class="invoice-details-right">
            <div class="titles-left">
              <p class="invoice-title">{{ __('PI No')}}:</p>
              <p class="invoice-title">{{ __('Invoice Number')}}:</p>
              <p class="invoice-title">{{ __('Status')}}:</p>
              <p class="invoice-title">{{ __('Invoice Date')}}:</p>
              <p class="invoice-title">{{ __('Due Date')}}:</p>
              <p class="invoice-title">{{ __('Invoice Amount')}}:</p>
              <p class="invoice-title">{{ __('PI Amount')}}:</p>
            </div>
            <div class="titles-right">
              <h3>{{ $invoice->pi_number }}</h3>
              <h3 class="invoice-number">{{ $invoice->invoice_number }}</h3>
              <h3>{{ Str::title($invoice->status) }}</h3>
              <h3>{{ Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</h3>
              <h3>{{ Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</h3>
              <h3>{{ number_format($invoice->total, 2) }}</h3>
              <h3>{{ number_format($invoice->drawdown_amount ? $invoice->drawdown_amount :  + $invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_fees - $invoice->total_invoice_discount, 2) }}</h3>
            </div>
          </div>
          <div>
            @if ($invoice->remarks)
              <h5>{{ __('Remarks')}}:</h5>
              <p>{{ $invoice->remarks }}</p>
            @endif
          </div>
        </div>
        <table class="invoice-items">
            <thead>
                <tr>
                  <th>{{ __('Item')}}</th>
                  <th>{{ __('Quantity')}}</th>
                  <th>{{ __('Price Per Quantity')}}</th>
                  <th>{{ __('Unit')}}</th>
                  <th>{{ __('Discount')}}</th>
                  <th>{{ __('Taxes')}}</th>
                  <th>{{ __('Total')}}</th>
                </tr>
            </thead>
            <tbody>
              @foreach($items as $row)
                <tr>
                  <td>{{ $row->item }}</td>
                  <td>{{ number_format($row->quantity, 2) }}</td>
                  <td>{{ number_format($row->price_per_quantity, 2) }}</td>
                  <td>{{ $row->unit }}</td>
                  <td>{{ $row->discount }}</td>
                  <td>{{ $row->taxes }}</td>
                  <td>{{ number_format(($row->quantity * $row->price_per_quantity) - $row->taxes - $row->discount, 2) }}</td>
                </tr>
              @endforeach
            </tbody>
        </table>

        <table class="subtotal">
          <tbody>
            <tr>
              <td>{{ __('Subtotal')}}</td>
              <td style="text-align: right"><strong>{{ number_format($invoice->total, 2) }}</strong></td>
            </tr>
          </tbody>
        </table>

        @if ($discount > 0)
          <table class="discount">
            <tbody>
              <tr>
                <td>{{ __('Discount')}}</td>
                <td style="text-align: right"><strong>{{ $invoice->currency }} {{ number_format($discount, 2) }}</strong></td>
              </tr>
            </tbody>
          </table>
        @else
          <table class="discount">
            <tbody>
              <tr>
                <td>{{ __('Discount')}}</td>
                <td style="text-align: right"><strong>{{ $invoice->currency }} 0.00</strong></td>
              </tr>
            </tbody>
          </table>
        @endif

        @if (count($taxes) > 0)
          <table class="taxes">
            <tbody>
              @foreach($taxes as $row)
                <tr>
                  <td>{{ __('Tax') }} ({{ $row->name }})</td>
                  <td style="text-align: right"><strong>{{ number_format($row->value, 2) }}</strong></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @else
          <table class="taxes">
            <tbody>
              <tr>
                <td>{{ __('Tax') }}</td>
                <td style="text-align: right"><strong>0.00</strong></td>
              </tr>
            </tbody>
          </table>
        @endif

        @if(count($fees) > 0)
          <table class="fees">
            <tbody>
              @foreach($fees as $row)
                <tr>
                  <td>{{ $row->name }} @if ($row->name === 'Withholding Tax' || $row->name === 'Withholding VAT') (%) @endif</td>
                  @if ($row->name === 'Withholding Tax' || $row->name === 'Withholding VAT')
                    <td>
                      {{ $invoice->currency }} {{ number_format($invoice->getTaxPercentage($row->amount)) }}
                    </td>
                  @else
                    <td></td>
                  @endif
                  <td style="text-align: right"><strong>{{ number_format($row->amount, 2) }}</strong></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @else
          <table class="fees">
            <tbody>
              <tr>
                <td>{{ __('WHT') }}</td>
                <td>0.00%</td>
                <td style="text-align: right"><strong>0.00</strong></td>
              </tr>
              <tr>
                <td>{{ __('WHT VAT') }}</td>
                <td>0.00%</td>
                <td style="text-align: right"><strong>0.00</strong></td>
              </tr>
              <tr>
                <td>{{ __('Credit Note Amount') }}</td>
                <td></td>
                <td style="text-align: right"><strong>{{ $invoice->currency }} 0.00</strong></td>
              </tr>
            </tbody>
          </table>
        @endif

        <table class="total">
          <tbody>
            <tr>
              <td>{{ __('Total')}}</td>
              <td style="text-align: right"><strong>{{ $invoice->drawdown_amount ? number_format($invoice->drawdown_amount, 2) : number_format($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_fees - $invoice->total_invoice_discount, 2) }}</strong></td>
            </tr>
          </tbody>
        </table>
    </div>
</body>
</html>
