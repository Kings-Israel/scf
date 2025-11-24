@extends('layouts/layoutMaster')

@section('title', 'Funding Limit Details')

@section('vendor-style')
@endsection

@section('page-style')

@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/moment/moment.js')}}"></script>
@endsection

@section('page-script')
<script>

</script>
@endsection

@section('content')
<div class="d-flex justify-content-between">
  <h4 class="fw-bold">
    <span class="fw-light">{{ __('Funding Limit Details for') }} {{ $company->name }}</span>
  </h4>
</div>

<div class="card">
  <div class="card-body">
    <div class="row">
      <div
        class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">{{ __('Top Level Borrower Limit') }}:</h6>
        <h5 class="px-2 text-right">
            {{ number_format($company->top_level_borrower_limit, 2) }}
        </h5>
      </div>
      <div class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">{{ __('Pipeline Requests') }}:</h6>
        <h5 class="px-2 text-right">
          {{ number_format($company->pipeline_amount, 2) }}
        </h5>
      </div>
      <div class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">{{ __('Current Exposure') }}:</h6>
        <h5 class="px-2 text-right">{{ number_format($company->utilized_amount, 2) }}</h5>
      </div>
      <div class="col-sm-4 text-align-center d-flex justify-content-between">
        <h6 class="mr-2 fw-light">{{ __('Available Limit') }}:</h6>
        <h5 class="px-2 text-right">
          {{ number_format($company->top_level_borrower_limit - ($company->pipeline_amount + $company->utilized_amount), 2) }}
        </h5>
      </div>
    </div>
  </div>
</div>
<hr>
<h4 class="fw-bold">
  <span class="fw-light">{{ __('Funding Limit Details for') }} {{ $company->name }} {{ __('with respect to') }} {{ $program->anchor->name }}</span>
</h4>
<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ __('Organization Name')}}</th>
            <th>{{ __('Sanctioned Limit')}}</th>
            <th>{{ __('Current Exposure')}}</th>
            <th>{{ __('Pipeline Requests')}}</th>
            <th>{{ __('Available Limit')}}</th>
            <th>{{ __('Limit Utilized')}}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr>
            <td>{{ $company->name }}</td>
            <td>{{ number_format($program_vendor_configuration->sanctioned_limit, 2) }}</td>
            <td>{{ number_format($program_vendor_configuration->utilized_amount, 2) }}</td>
            <td>{{ number_format($program_vendor_configuration->pipeline_amount, 2) }}</td>
            <td>{{ number_format($program_vendor_configuration->sanctioned_limit - ($program_vendor_configuration->utilized_amount + $program_vendor_configuration->pipeline_amount), 2) }}</td>
            <td>
              @if ($program_vendor_configuration->sanctioned_limit > 0)
                {{ number_format((($program_vendor_configuration->utilized_amount + $program_vendor_configuration->pipeline_amount) / $program_vendor_configuration->sanctioned_limit) * 100, 2) }}
              @else
                0
              @endif
              %
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
