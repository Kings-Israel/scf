@extends('layouts/buyerLayoutMaster')

@section('title', 'Anchor Settings')

@section('vendor-style')
@endsection

@section('page-style')
@endsection

@section('vendor-script')
@endsection

@section('page-script')
<script>
  function editTax(key) {
    $('#tax-'+key+'-value').addClass('d-none')
    $('#tax-'+key+'-input').removeClass('d-none')
    $('#vat-'+key+'-value').addClass('d-none')
    $('#vat-'+key+'-input').removeClass('d-none')

    $('#tax-'+key+'-edit').addClass('d-none')
    $('#tax-'+key+'-submit').removeClass('d-none')
  }

  function submitTax(key, id) {
    let value = $('#tax-'+key+'-input').val()
    let vat = $('#vat-'+key+'-input').val()
    $.post({
      "url": "/dealer/settings/anchor-settings/"+id+"/update",
      "dataType": "json",
      "data": {"value": value, "vat": vat},
      "success": function (data) {
        window.location.reload()
      },
      "error": function (jqXHR, textStatus, errorThrown) {
        console.log(errorThrown);
      }
    })
  }
</script>
@endsection

@section('content')
<h4 class="fw-light">
  {{ __('Anchor Settings')}}
</h4>
<div class="card p-2">
  <div class="table-responsive pb-2">
    <table class="table">
      <thead>
        <tr class="text-nowrap">
          <th>{{ __('Anchor')}}</th>
          <th>{{ __('Withholding Tax')}} (%)</th>
          <th>{{ __('Withholding VAT')}} (%)</th>
          <th>{{ __('Actions')}}</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        @foreach ($anchors as $key => $anchor)
          <tr class="text-nowrap">
            <td>
              {{ $anchor->program->anchor->name }} ({{ $anchor->payment_account_number }})
            </td>
            <td>
              <span id="tax-{{ $key }}-value">{{ $anchor->withholding_tax }}</span>
              <input type="number" step=".01" class="d-none w-50 form-control" value="{{ $anchor->withholding_tax }}" id="tax-{{ $key }}-input">
            </td>
            <td>
              <span id="vat-{{ $key }}-value">{{ $anchor->withholding_vat }}</span>
              <input type="number" step=".01" class="d-none w-50 form-control" value="{{ $anchor->withholding_vat }}" id="vat-{{ $key }}-input">
            </td>
            <td>
              <i class="tf-icons ti ti-edit ti-xs me-1 border rounded-circle p-2" id="tax-{{ $key }}-edit" onclick="editTax({{ $key }})"></i>
              <button class="btn btn-success btn-sm d-none" id="tax-{{ $key }}-submit" onclick="submitTax({{ $key }}, {{ $anchor->id }})">
                {{ __('Update')}}
              </button>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
