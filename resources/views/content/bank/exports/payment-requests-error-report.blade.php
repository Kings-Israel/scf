<table>
  <thead>
  <tr>
    <th>Payment Reference No. *</th>
    <th>Invoice No *</th>
    <th>Vendor *</th>
    <th>Anchor *</th>
    <th>PI Amount (Ksh) *</th>
    <th>Eligibility (%) *</th>
    <th>Eligible Payment Amount (Ksh)</th>
    <th>Requested Payment Amount (Ksh)</th>
    <th>Request Date *</th>
    <th>Requested Disbursement Date *</th>
    <th>Due Date *</th>
    <th>Discount Rate *</th>
    <th>Approved By</th>
    <th>Rejection Remark</th>
    <th>Status</th>
    <th>Product</th>
    <th>Created By</th>
    <th>Created At</th>
    <th>Last Updated By</th>
    <th>Last Updated At</th>
    <th>Errors</th>
  </tr>
  </thead>
  <tbody>
    @foreach ($errors as $error)
      <tr>
        <td>{{ $error->first()->values['payment_reference_no'] }}</td>
        <td>{{ $error->first()->values['invoice_no'] }}</td>
        <td>{{ $error->first()->values['vendor'] }}</td>
        <td>{{ $error->first()->values['anchor'] }}</td>
        <td>{{ $error->first()->values['pi_amount_ksh'] }}</td>
        <td>{{ $error->first()->values['eligibility'] }}</td>
        <td>{{ $error->first()->values['eligible_payment_amount_ksh'] }}</td>
        <td>{{ $error->first()->values['requested_payment_amount_ksh'] }}</td>
        <td>{{ $error->first()->values['request_date'] }}</td>
        <td>{{ $error->first()->values['requested_disbursement_date'] }}</td>
        <td>{{ $error->first()->values['due_date'] }}</td>
        <td>{{ $error->first()->values['discount_rate'] }}</td>
        <td>{{ array_key_exists('approved_by', $error->first()->values) ? $error->first()->values['approved_by'] : '' }}</td>
        <td>{{ array_key_exists('rejection_remark', $error->first()->values) ? $error->first()->values['rejection_remark'] : '' }}</td>
        <td>{{ $error->first()->values['status'] }}</td>
        <td>{{ array_key_exists('product_code', $error->first()->values) ? $error->first()->values['product_code'] : '' }}</td>
        <td>{{ array_key_exists('created_by', $error->first()->values) ? $error->first()->values['created_by'] : '' }}</td>
        <td>{{ array_key_exists('created_at', $error->first()->values) ? $error->first()->values['created_at'] : '' }}</td>
        <td>{{ array_key_exists('last_updated_by', $error->first()->values) ? $error->first()->values['last_updated_by'] : '' }}</td>
        <td>{{ array_key_exists('last_updated_at', $error->first()->values) ? $error->first()->values['last_updated_at'] : '' }}</td>
        <td>
          {{ $error->details }}
          {{-- @foreach ($error->errors as $data)
              @if ($loop->last)
                {{ $data }}
              @else
                {{ $data.', ' }}
              @endif
          @endforeach --}}
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
