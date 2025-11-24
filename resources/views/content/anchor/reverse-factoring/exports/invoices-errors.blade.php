@if ($company_type == 'anchor')
  <table>
    <thead>
      <tr>
        <th>{{ __('Invoice Number')}}. *</th>
        <th>{{ __('Invoice Date')}} (dd/mm/yyyy) *</th>
        <th>{{ __('Net Invoice Amount')}} *</th>
        <th>{{ __('Currency')}} *</th>
        <th>{{ __('Invoice Due Date')}} (dd/mm/yyyy) *</th>
        <th>{{ __('Type (Invoice/BOE/NOA)')}} *</th>
        <th>{{ __('Loan/OD Account No')}}. *</th>
        <th>{{ __('Pay Date')}} (dd/mm/yyyy)</th>
        <th>{{ __('Attachment(s) (for multiple attachments separate Name with commas without space)')}}</th>
        <th>{{ __('Tax Amount')}}</th>
        <th>{{ __('Total Payable')}}</th>
        <th>{{ __('Credit Note Amount')}}</th>
        <th>{{ __('Errors')}}</th>
      </tr>
  </thead>
  <tbody>
      @foreach ($errors as $error)
        <tr>
          <td>{{ $error->first()->invoice_number }}</td>
          <td>{{ $error->first()->invoice_date ? Carbon\Carbon::parse($error->first()->invoice_date)->format('d/m/Y') : '' }}</td>
          <td>{{ number_format($error->first()->net_invoice_amount, 2) }}</td>
          <td>{{ $error->first()->currency }}</td>
          <td>{{ $error->first()->due_date ? Carbon\Carbon::parse($error->first()->due_date)->format('d/m/Y') : '' }}</td>
          <td>{{ $error->first()->type }}</td>
          <td>{{ $error->first()->loan_od_account }}</td>
          <td>{{ $error->first()->pay_date ? Carbon\Carbon::parse($error->first()->pay_date)->format('d/m/Y') : '' }}</td>
          <td>{{ $error->first()->attachments }}</td>
          <td>{{ $error->first()->tax_amount }}</td>
          <td>{{ $error->first()->total_payable }}</td>
          <td>{{ $error->first()->credit_note_amount }}</td>
          <td>
            {{ $error->details }}
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
@elseif ($company_type == 'dealer')
  <table>
    <thead>
      <tr>
        <th>{{ __('Invoice / Unique Ref. No.')}}. *</th>
        <th>{{ __('Invoice Date')}} (dd/mm/yyyy) *</th>
        <th>{{ __('Payment/OD Account No')}}. *</th>
        <th>{{ __('Payment Date')}} (dd/mm/yyyy)</th>
        <th>{{ __('Invoice Due Date')}} (dd/mm/yyyy) *</th>
        <th>{{ __('Invoice Amount')}} *</th>
        <th>{{ __('Drawdown Amount')}} *</th>
        <th>{{ __('Dealer Code')}}</th>
        <th>{{ __('Credit To')}}</th>
        <th>{{ __('Attachment(s) (for multiple attachments separate Name with commas without space)')}}</th>
        <th>{{ __('Errors')}}</th>
      </tr>
  </thead>
  <tbody>
      @foreach ($errors as $error)
        <tr>
          <td>{{ $error->first()->invoice_number }}</td>
          <td>{{ $error->first()->invoice_date ? Carbon\Carbon::parse($error->first()->invoice_date)->format('d/m/Y') : '' }}</td>
          <td>{{ $error->first()->loan_od_account }}</td>
          <td>{{ $error->first()->pay_date ? Carbon\Carbon::parse($error->first()->pay_date)->format('d/m/Y') : '' }}</td>
          <td>{{ $error->first()->due_date ? Carbon\Carbon::parse($error->first()->due_date)->format('d/m/Y') : '' }}</td>
          <td>{{ $error->first()->net_invoice_amount }}</td>
          <td>{{ $error->first()->drawdown_amount }}</td>
          <td>{{ $error->first()->dealer_code }}</td>
          <td>{{ $error->first()->credit_to }}</td>
          <td>{{ $error->first()->attachments }}</td>
          <td>
            {{ $error->details }}
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
@else
  <table>
    <thead>
    <tr>
      <th>{{ __('Invoice Number')}}. *</th>
      <th>{{ __('Invoice Date')}} (dd/mm/yyyy) *</th>
      <th>{{ __('Net Invoice Amount')}} *</th>
      <th>{{ __('Invoice Due Date')}} (dd/mm/yyyy)</th>
      <th>{{ __('Loan/OD Account No')}}. *</th>
      <th>{{ __('Errors')}}</th>
    </tr>
    </thead>
    <tbody>
      @foreach ($errors as $key => $error)
        <tr>
          <td>{{ $key }}</td>
          <td>{{ $error->first()->invoice_date ? Carbon\Carbon::parse($error->first()->invoice_date)->format('d/m/Y') : '' }}</td>
          <td>{{ round($error->first()->net_invoice_amount, 2) }}</td>
          <td>{{ $error->first()->pay_date ? Carbon\Carbon::parse($error->first()->pay_date)->format('d/m/Y') : '' }}</td>
          <td>{{ $error->first()->loan_od_account }}</td>
          <td>
            {{ $error->details }}
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
@endif
