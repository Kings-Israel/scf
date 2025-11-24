@extends('layouts/layoutMaster')

@section('title', 'Programs Drafts')

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
  <span class="fw-light">{{ __('Programs Drafts')}}</span>
  <small>{{ __('Your saved drafts')}}.</small>
</h4>
<div class="row g-3">
  <div class="card">
    <div class="card-body p-1">
      <table class="table">
        <thead>
          <tr>
            <th>{{ __('Name')}}</th>
            <th>{{ __('Type')}}</th>
            <th>{{ __('Anchor')}}</th>
            <th>{{ __('Approval Date')}}</th>
            <th>{{ __('Eligibility')}}</th>
            <th>{{ __('Has Discounts')}}</th>
            <th>{{ __('Has Fees')}}</th>
            <th>{{ __('Created')}}</th>
            <th>{{ __('Actions')}}</th>
          </tr>
        </thead>
        <tbody class="text-nowrap">
          @foreach ($drafts as $draft)
            <tr @if($draft->publisher_id != auth()->id()) style="background: #CDCCD2" @endif>
              <td>
                <span>
                  {{ $draft->name }} @if ($draft->is_current) <span class="badge bg-label-success">{{ __('Latest')}}</span> @endif
                </span>
              </td>
              <td>{{ $draft->programType->name }}</td>
              <td>{{ $draft->anchor?->name }}</td>
              <td>{{ Carbon\Carbon::parse($draft->approval_date)->format('d M Y') }}</td>
              <td>{{ $draft->eligibility }}%</td>
              <td>{{ $draft->discountDetails->count() > 0 ? 'Yes' : 'No' }}</td>
              <td>{{ $draft->fees->count() > 0 ? 'Yes' : 'No' }}</td>
              <td>{{ $draft->created_at->format('d M Y H:i A') }}</td>
              <td>
                @if ($draft->publisher_id == auth()->id())
                  <a href="{{ route('programs.create', ['bank' => $bank, 'program' => $draft]) }}" class="btn btn-primary btn-sm">
                    {{ __('Continue')}}
                  </a>
                  <a href="{{ route('programs.draft.delete', ['bank' => $bank, 'program' => $draft]) }}" class="btn btn-danger btn-sm">
                    {{ __('Delete')}}
                  </a>
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
