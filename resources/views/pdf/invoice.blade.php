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
    <title>Invoice - {{ $invoice->invoice_number }}</title>
    <style>
      body {
          font-family: Arial, sans-serif;
          margin: 0;
          padding: 0;
          font-size: 16px;
      }
      .header {
        margin-top: 10px;
        text-align: right;
        border-bottom: 2px solid #a6a6a6
      }
      .invoice-details {
        margin-top: 10px;
      }
      .invoice-details-right {
        text-align: right;
        margin-top: -10rem;
        margin-left: 50%;
        width: 50%;
      }
      .titles-left {
        text-align: left;
      }
      .titles-right {
        text-align: right;
        margin-top: -15rem;
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
        margin-top: 50px;
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
        background: #e0e0e0;
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
      .discount tr > td {
        border: none;
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
      <h1>{{ __('INVOICE') }}</h1>
    </div>
    <div class="invoice-details">
      <div class="invoice-details-left">
        <h3><strong>{{ __('Bill To') }}:</strong></h3>
        <h3>{{ $invoice->buyer ? $invoice->buyer->name : $invoice->program->anchor->name }}</h3>
        {{-- <h3><strong>{{ __('Vendor')}}:</strong></h3>
        <h3>{{ $invoice->buyer ? $invoice->program->anchor->name : $invoice->company->name }}</h3> --}}
      </div>
      <div class="invoice-details-right">
        <div class="titles-left">
          <h3><strong>{{ __('Invoice Number')}}:</strong></h3>
          <h3><strong>{{ __('Invoice Date')}}:</strong></h3>
          <h3><strong>{{ __('Due Date')}}:</strong></h3>
          <h3><strong>{{ __('Invoice Amount')}}:</strong></h3>
        </div>
        <div class="titles-right">
          <h3 class="invoice-number">{{ $invoice->invoice_number }}</h3>
          <h3>{{ Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</h3>
          <h3>{{ Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</h3>
          <h3>{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</h3>
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
          <td style="text-align: right">{{ number_format($invoice->total, 2) }}</td>
        </tr>
      </tbody>
    </table>

    @if (count($taxes) > 0)
      <table class="taxes">
        <tbody>
          @foreach($taxes as $row)
            <tr>
              <td>{{ __('Tax') }} ({{ $row->name }})</td>
              <td style="text-align: right">{{ number_format($row->value, 2) }}</td>
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

    @if ($discount > 0)
      <table class="discount">
        <tbody>
          <tr>
            <td>{{ __('Discount')}}</td>
            <td style="text-align: right">{{ number_format($discount, 2) }}</td>
          </tr>
        </tbody>
      </table>
    @else
      <table class="discount">
        <tbody>
          <tr>
            <td>{{ __('Discount') }}</td>
            <td style="text-align: right"><strong>0.00</strong></td>
          </tr>
        </tbody>
      </table>
    @endif

    <table class="total">
      <tbody>
        <tr>
          <td>{{ __('Total')}}</td>
          <td style="text-align: right">{{ number_format($invoice->total + $invoice->total_invoice_taxes - $invoice->total_invoice_discount, 2) }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</body>
</html>
