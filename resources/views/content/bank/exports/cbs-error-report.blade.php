<table>
  <thead>
  <tr>
    <th>CBS ID</th>
    <th>Debit From A/C No *</th>
    <th>Debit From A/C Name</th>
    <th>Credit To A/C No *</th>
    <th>Credit To A/C Name</th>
    <th>Amount (Ksh) *</th>
    <th>Transaction Created Date (dd/mm/yyyy)</th>
    <th>Pay Date (dd/mm/yyyy)</th>
    <th>Transaction Date (dd/mm/yyyy)</th>
    <th>Transaction Reference No. *</th>
    <th>Status (Created/Successful/Failed/Permanently Failed) *</th>
    <th>Transaction Type *</th>
    <th>Product</th>
    <th>Errors</th>
  </tr>
  </thead>
  <tbody>
    @foreach ($errors as $error)
      <tr>
        <td>{{ array_key_exists('cbs_id', $error->values) ? $error->values['cbs_id'] : '' }}</td>
        <td>{{ $error->values['debit_from_ac_no'] }}</td>
        <td>{{ array_key_exists('debit_from_ac_name', $error->values) ? $error->values['debit_from_ac_name'] : '' }}</td>
        <td>{{ $error->values['credit_to_ac_no'] }}</td>
        <td>{{ array_key_exists('credit_to_ac_name', $error->values) ? $error->values['credit_to_ac_name'] : '' }}</td>
        <td>{{ $error->values['amount_ksh'] }}</td>
        <td>{{ $error->values['transaction_created_date_ddmmyyyy'] }}</td>
        <td>{{ $error->values['pay_date_ddmmyyyy'] }}</td>
        <td>{{ $error->values['transaction_date_ddmmyyyy'] }}</td>
        <td>{{ array_key_exists('transaction_reference_no', $error->values) ? $error->values['transaction_reference_no'] : '' }}</td>
        <td>{{ $error->values['status_createdsuccessfulfailedpermanently_failed'] }}</td>
        <td>{{ $error->values['transaction_type'] }}</td>
        <td>{{ $error->values['product'] }}</td>
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
