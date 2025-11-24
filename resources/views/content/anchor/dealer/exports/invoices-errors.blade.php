<table>
  <thead>
  <tr>
    <th>{{ __('Invoice Number')}}. *</th>
    <th>{{ __('Invoice Date')}} (dd/mm/yyyy) *</th>
    <th>{{ __('Net Invoice Amount')}} *</th>
    <th>{{ __('Currency')}} *</th>
    <th>{{ __('Loan/OD Account No')}}. *</th>
    <th>{{ __('Pay Date')}} (dd/mm/yyyy)</th>
    <th>{{ __('Dealer Code')}}</th>
    <th>{{ __('Credit To')}}</th>
    <th>{{ __('Attachment(s) (for multiple attachments separate Name with commas without space)')}}</th>
    <th>{{ __('Errors')}}</th>
  </tr>
  </thead>
  <tbody>
    @foreach ($errors as $key => $error)
      <tr>
        <td>{{ $key }}</td>
        <td>{{ $error->first()->invoice_date }}</td>
        <td>{{ number_format($error->first()->net_invoice_amount, 2) }}</td>
        <td>{{ $error->first()->currency }}</td>
        <td>{{ $error->first()->loan_od_account }}</td>
        <td>{{ $error->first()->pay_date }}</td>
        <td>{{ $error->first()->attachments }}</td>
        <td>{{ $error->first()->dealer_code }}</td>
        <td>{{ $error->first()->credit_to }}</td>
        <td>
          {{ $error->details }}
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
