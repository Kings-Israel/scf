@extends('layouts/anchorDealerLayoutMaster')

@section('title', 'Configurations')

@section('vendor-style')
@endsection

@section('page-style')
@endsection

@section('vendor-script')
@endsection

@section('page-script')
<script>
  function editTax(key) {
    $('#vendor-'+key+'-edit').addClass('d-none')
    $('#vendor-'+key+'-submit').removeClass('d-none')

    $('#tax-'+key+'-value').addClass('d-none')
    $('#tax-'+key+'-input').removeClass('d-none')
    $('#vat-'+key+'-value').addClass('d-none')
    $('#vat-'+key+'-input').removeClass('d-none')
    $('#terms-'+key+'-value').addClass('d-none')
    $('#terms-'+key+'-input').removeClass('d-none')
  }

  function submitTax(key, id) {
    let tax = $('#tax-'+key+'-input').val()
    let vat = $('#vat-'+key+'-input').val()
    let terms = $('#terms-'+key+'-input').val()
    $('#vendor-'+key+'-submit').text('Processing...')
    $('#vendor-'+key+'-submit').attr('disabled', 'disabled')
    $.post({
      "url": "/anchor/dealer/configurations/vendor-settings/"+id+"/update",
      "dataType": "json",
      "data": {"tax": tax, "vat": vat, "terms": terms},
      "success": function (data) {
        window.location.reload()
      },
      "error": function (jqXHR, textStatus, errorThrown) {
        console.log(errorThrown);
        $('#vendor-'+key+'-submit').text('Update')
        $('#vendor-'+key+'-submit').removeAttr('disabled')
      }
    })
  }
</script>
@endsection

@section('content')
<h4 class="fw-bold">
  <span class="fw-light">{{ __('Dealer Settings') }}</span>
</h4>
<div class="card p-1">
  <div class="table-responsive pb-2">
    <table class="table">
      <thead>
        <tr class="text-nowrap">
          <th>{{ __('Dealer') }}</th>
          <th>{{ __('Withholding Tax') }} (%)</th>
          <th>{{ __('Withholding VAT') }}(%)</th>
          <th>{{ __('Payment Terms') }}({{ __('Days') }})</th>
          <th>{{ __('Actions') }}</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        @foreach ($vendors as $key => $vendor)
          <tr class="text-nowrap">
            <td>
              {{ $vendor->company->name }} ({{ $vendor->payment_account_number }})
              @if ($vendor->anchorConfigurationChange)
                <i class="tf-icons ti ti-info-circle text-sm text-danger" title="Configuration updation awaiting approval"></i>
              @endif
            </td>
            <td>
              <span id="tax-{{ $key }}-value">
                {{ $vendor->withholding_tax }}
              </span>
              <input type="number" step=".01" min="0" max="100" step=".01" class="d-none form-control" value="{{ $vendor->withholding_tax }}" id="tax-{{ $key }}-input">
            </td>
            <td>
              <span id="vat-{{ $key }}-value">
                {{ $vendor->withholding_vat }}
              </span>
              <input type="number" step=".01" min="0" max="100" class="d-none form-control" value="{{ $vendor->withholding_vat }}" id="vat-{{ $key }}-input">
            </td>
            <td>
              <span id="terms-{{ $key }}-value">
                {{ $vendor->payment_terms }}
              </span>
              <input type="number" class="d-none form-control" min="0" value="{{ $vendor->payment_terms }}" id="terms-{{ $key }}-input">
            </td>
            <td>
              @if ($vendor->anchorConfigurationChange)
                <i class="tf-icons ti ti-clock text-warning" title="Configuration updation awaiting approval" data-bs-toggle="modal"
                  data-bs-target="#update-configuration-{{ $vendor->id }}"></i>
                <div class="modal modal-top fade modal-lg" id="update-configuration-{{ $vendor->id }}" tabindex="-1">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalTopTitle">{{ __('Proposed Update') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        @foreach ($vendor->anchorConfigurationChange->data as $key => $config_change)
                          <div class="flex">
                            <span>
                              {{ Str::title(str_replace('_', ' ', $key)) }}:
                            </span>
                            <span>
                              {{ $config_change }}
                            </span>
                          </div>
                        @endforeach
                      </div>
                      <div class="modal-footer">
                        @if ($vendor->anchorConfigurationChange->can_approve)
                          <a class="btn btn-danger btn-sm" href="{{ route('anchor.program_vendor_configuration.change.approve', ['program_vendor_configuration' => $vendor, 'status' => 'reject']) }}">{{ __('Discard') }}</a>
                          <a class="btn btn-success btn-sm" href="{{ route('anchor.program_vendor_configuration.change.approve', ['program_vendor_configuration' => $vendor, 'status' => 'approve']) }}">{{ __('Approve') }}</a>
                        @endif
                      </div>
                    </div>
                  </div>
                </div>
              @else
                <i class="tf-icons ti ti-edit ti-xs me-1 border rounded-circle p-2" style="cursor: pointer" id="vendor-{{ $key }}-edit" onclick="editTax({{ $key }})"></i>
                <button class="btn btn-success btn-sm d-none" id="vendor-{{ $key }}-submit" onclick="submitTax({{ $key }}, {{ $vendor->id }})">
                  {{ __('Update')}}
                </button>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
