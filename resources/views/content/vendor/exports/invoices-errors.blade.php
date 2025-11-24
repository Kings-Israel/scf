<table>
  <thead>
  <tr>
    <th>{{ __('Invoice Number')}}. *</th>
    <th>{{ __('Invoice Date')}} (dd/mm/yyyy) *</th>
    <th>{{ __('Net Invoice Amount')}} *</th>
    <th>{{ __('Invoice Due Date')}} (dd/mm/yyyy) *</th>
    <th>{{ __('Loan/OD Account')}} *</th>
    <th>{{ __('Errors')}}</th>
  </tr>
  </thead>
  <tbody>
    @foreach ($errors as $error)
      <tr>
        <td>{{ $error->first()->values['invoice_number'] }}</td>
        <td>{{ $error->first()->values['invoice_date_ddmmyyyy'] }}</td>
        <td>{{ $error->first()->values['net_invoice_amount'] }}</td>
        <td>{{ $error->first()->values['invoice_due_date_ddmmyyyy'] }}</td>
        <td>{{ $error->first()->values['loan_od_account'] }}</td>
        <td>
          {{-- @foreach ($error->errors as $data)
              @if ($loop->last)
                {{ $data }}
              @else
                {{ $data.', ' }}
              @endif
          @endforeach --}}
          {{ $error->details }}
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
