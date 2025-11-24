@extends('layouts/layoutMaster')

@section('title', 'Company Drafts')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/bs-stepper/bs-stepper.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/bootstrap-select/bootstrap-select.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/select2/select2.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css')}}" />
{{-- <link rel="stylesheet" href="{{asset('assets/vendor/libs/dropzone/dropzone.css')}}" /> --}}
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/bs-stepper/bs-stepper.js')}}"></script>
<script src="{{asset('assets/vendor/libs/bootstrap-select/bootstrap-select.js')}}"></script>
<script src="{{asset('assets/vendor/libs/select2/select2.js')}}"></script>
<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js')}}"></script>
<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js')}}"></script>
<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js')}}"></script>
{{-- <script src="{{asset('assets/vendor/libs/dropzone/dropzone.js')}}"></script> --}}
@endsection

@section('page-script')
@endsection

@section('content')
<h4 class="fw-bold mb-2 d-flex justify-content-between">
  <span class="fw-light">{{ __('Company Drafts')}}</span>
  <small>{{ __('Your saved drafts')}}.</small>
</h4>
<div class="row g-3">
  <div class="card">
    <div class="card-body p-1">
      <table class="table">
        <thead>
          <tr>
            <th>{{ __('Name')}}</th>
            <th>{{ __('Top Level Limit')}}</th>
            <th>{{ __('Limit Expiry Date')}}</th>
            <th>{{ __('Branch Code')}}</th>
            <th>{{ __('CUST ANCODE')}}</th>
            <th>{{ __('KRA PIN')}}</th>
            <th>{{ __('Created At')}}</th>
            <th>{{ __('Created By')}}</th>
            <th>{{ __('Actions')}}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($drafts as $draft)
            <tr @if($draft->publisher_id != auth()->id()) style="background: #CDCCD2" @endif>
              <td>
                <span>
                  {{ $draft->name }} @if ($draft->is_current) <span class="badge bg-label-success">{{ __('Latest')}}</span> @endif
                </span>
              </td>
              <td>{{ number_format($draft->top_level_borrower_limit) }}</td>
              <td>{{ Carbon\Carbon::parse($draft->limit_expiry_date)->format('d M Y') }}</td>
              <td>{{ $draft->branch_code }}</td>
              <td>{{ $draft->cust_ancode }}</td>
              <td>{{ $draft->kra_pin }}</td>
              <td>{{ $draft->created_at->format('d M Y H:i A') }}</td>
              <td>
                {{ App\Models\User::find($draft->publisher_id)->name }}
              </td>
              <td>
                @if ($draft->publisher_id == auth()->id())
                  <div class="d-flex flex-nowrap">
                    <a href="{{ route('companies.create', ['bank' => $bank, 'pipeline' => NULL, 'company' => $draft]) }}" class="btn btn-primary btn-sm mx-1">
                      {{ __('Continue')}}
                    </a>
                    <a href="{{ route('companies.draft.delete', ['bank' => $bank, 'company' => $draft]) }}" class="btn btn-danger btn-sm">
                      {{ __('Delete')}}
                    </a>
                  </div>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
