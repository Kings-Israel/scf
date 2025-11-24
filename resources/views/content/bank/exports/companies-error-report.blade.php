<table>
  <thead>
  <tr>
    <th>{{ __('Company Name')}}. *</th>
    <th>{{ __('Top Level Borrower Limit')}} *</th>
    <th>{{ __('Limit Expiry Date')}} (dd/mm/yyyy) *</th>
    <th>{{ __('Company\'s Unique Identification Number')}} *</th>
    <th>{{ __('label.label.lbl_bank_cus_id')}}</th>
    <th>{{ __('Branch Code')}}</th>
    <th>{{ __('Organization Type')}}</th>
    <th>{{ __('Business Segment')}}</th>
    <th>{{ __('Industry')}}</th>
    <th>{{ __('Contact Person Name')}}</th>
    <th>{{ __('Email')}}</th>
    <th>{{ __('Mobile')}}</th>
    <th>{{ __('State/Province')}}</th>
    <th>{{ __('City')}}</th>
    <th>{{ __('Pin/Zip/Postal Code')}}</th>
    <th>{{ __('Address')}}</th>
    <th>{{ __('Created By')}}</th>
    <th>{{ __('Created At')}} (dd/mm/yyyy)</th>
    <th>{{ __('Last Updated By')}}</th>
    <th>{{ __('Last Updated At')}} (dd/mm/yyyy)</th>
    <th>{{ __('Errors')}}</th>
  </tr>
  </thead>
  <tbody>
    @foreach ($errors as $key => $error)
      <tr>
        <td>{{ $key }}</td>
        <td>{{ $error->first()->top_level_borrower_limit }}</td>
        <td>{{ $error->first()->limit_expiry_date ? Carbon\Carbon::parse($error->first()->limit_expiry_date)->format('d/m/Y') : '' }}</td>
        <td>{{ $error->first()->unique_identification_number }}</td>
        <td>{{ $error->first()->id }}</td>
        <td>{{ $error->first()->branch_code }}</td>
        <td>{{ $error->first()->organization_type }}</td>
        <td>{{ $error->first()->business_segment }}</td>
        <td>{{ $error->first()->industry }}</td>
        <td>{{ $error->first()->contact_person_name }}</td>
        <td>{{ $error->first()->email }}</td>
        <td>{{ $error->first()->mobile }}</td>
        <td>{{ $error->first()->state }}</td>
        <td>{{ $error->first()->city }}</td>
        <td>{{ $error->first()->postal_code }}</td>
        <td>{{ $error->first()->address }}</td>
        <td>{{ $error->first()->created_by }}</td>
        <td>{{ $error->first()->created_at->format('d/m/Y') }}</td>
        <td>{{ $error->first()->last_updated_by }}</td>
        <td>{{ $error->first()->updated_at->format('d/m/Y') }}</td>
        <td>
          {{ $error->details }}
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
