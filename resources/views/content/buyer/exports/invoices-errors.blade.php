<table>
  <thead>
  <tr>
    <th>{{ __('Invoice / Unique Ref No')}}. *</th>
    <th>{{ __('Invoice Date')}} (dd/mm/yyyy) *</th>
    <th>{{ __('Payment/OD Account No')}}. *</th>
    <th>{{ __('Type (Invoice/BOE/NOA)')}} *</th>
    <th>{{ __('Net Invoice Amount')}} *</th>
    <th>{{ __('Invoice Due Date')}} (dd/mm/yyyy) *</th>
    <th>{{ __('Pay Date')}} (dd/mm/yyyy)</th>
    <th>{{ __('Tax Amount')}}</th>
    <th>{{ __('Credit Note Amount')}}</th>
    <th>{{ __('Attachment(s) (for multiple attachments separate Name with commas without space)')}}</th>
    <th>{{ __('Errors')}}</th>
  </tr>
  </thead>
  <tbody>
    @foreach ($errors as $error)
      <tr>
        <td>{{ $error->values['invoice_unique_ref_no'] }}</td>
        <td>{{ $error->values['invoice_date_ddmmyyyy'] }}</td>
        <td>{{ $error->values['paymentod_account_no'] }}</td>
        <td>{{ array_key_exists('type_invoiceboenoa', $error->values) ? $error->values['type_invoiceboenoa'] : '' }}</td>
        <td>{{ $error->values['net_invoice_amount'] }}</td>
        <td>{{ $error->values['invoice_due_date_ddmmyyyy'] }}</td>
        <td>{{ array_key_exists('pay_date_ddmmyyyy', $error->values) ? $error->values['pay_date_ddmmyyyy'] : '' }}</td>
        <td>{{ array_key_exists('tax_amount', $error->values) ? $error->values['tax_amount'] : '' }}</td>
        <td>{{ array_key_exists('credit_note_amount', $error->values) ? $error->values['credit_note_amount'] : '' }}</td>
        <td></td>
        <td>
          @foreach ($error->errors as $data)
              @if ($loop->last)
                {{ $data }}
              @else
                {{ $data.', ' }}
              @endif
          @endforeach
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
