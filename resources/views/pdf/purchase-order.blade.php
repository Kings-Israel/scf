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
    <title>Purchase Order - {{ $purchase_order->purchase_order_number }}</title>
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
      #item-description {
        display: flex;
        flex-direction: column;
      }
      small {
        font-size: 12px;
        color: #666;
      }
    </style>
</head>
<body>
  <div class="">
    <div class="header">
      <h1>{{ __('PURCHASE ORDER') }}</h1>
      <h3><strong>{{ $purchase_order->anchor->name }}</strong></h3>
      <h3><strong>{{ $purchase_order->delivery_address }}</strong></h3>
    </div>
    <div class="invoice-details">
      <div class="invoice-details-left">
        <h3><strong>{{ __('Vendor') }}:</strong></h3>
        <h3>{{ $purchase_order->company->name }}</h3>
        {{-- <h3><strong>{{ __('Vendor')}}:</strong></h3>
        <h3>{{ $invoice->buyer ? $invoice->program->anchor->name : $invoice->company->name }}</h3> --}}
      </div>
      <div class="invoice-details-right">
        <div class="titles-left">
          <h3><strong>{{ __('PO No')}}:</strong></h3>
          <h3><strong>{{ __('Start Date')}}:</strong></h3>
          <h3><strong>{{ __('End Date')}}:</strong></h3>
          <h3><strong>{{ __('Amount')}}:</strong></h3>
        </div>
        <div class="titles-right">
          <h3 class="invoice-number">{{ $purchase_order->purchase_order_number }}</h3>
          <h3>{{ Carbon\Carbon::parse($purchase_order->duration_from)->format('d M Y') }}</h3>
          <h3>{{ Carbon\Carbon::parse($purchase_order->duration_to)->format('d M Y') }}</h3>
          <h3>{{ $purchase_order->currency }} {{ number_format($purchase_order->total_amount, 2) }}</h3>
        </div>
      </div>
    </div>
    <table class="invoice-items">
      <thead>
          <tr>
            <th>{{ __('Item Name')}}</th>
            <th>{{ __('Quantity')}}</th>
            <th>{{ __('Unit')}}</th>
            <th>{{ __('Price Per Quantity')}}</th>
            <th>{{ __('Total')}}</th>
          </tr>
      </thead>
      <tbody>
        @foreach($purchase_order->purchaseOrderItems as $row)
          <tr>
            <td>
              <div id="item-description">
                <span>{{ $row->item }}</span>
                <small>{{ $row->description }}</small>
              </div>
            </td>
            <td>{{ number_format($row->quantity, 2) }}</td>
            <td>{{ $row->unit }}</td>
            <td>{{ number_format($row->price_per_quantity, 2) }}</td>
            <td>{{ number_format($row->quantity * $row->price_per_quantity, 2) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <table class="total">
      <tbody>
        <tr>
          <td>{{ __('Total')}}</td>
          <td style="text-align: right">{{ number_format($purchase_order->total_amount, 2) }}</td>
        </tr>
      </tbody>
    </table>
    <div>
        @if ($purchase_order->remarks)
          <h5>{{ __('Notes')}}:</h5>
          <p>{{ $purchase_order->remarks }}</p>
        @endif
      </div>
  </div>
</body>
</html>
