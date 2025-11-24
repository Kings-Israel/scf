@extends('layouts/buyerLayoutMaster')

@section('title', 'Company Details')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/animate-css/animate.css')}}">
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/masonry/masonry.js')}}"></script>
@endsection

@section('page-script')
<script src="{{asset('assets/js/ui-modals.js')}}"></script>
@endsection

@section('content')
<h4 class="fw-bold mb-2"><span class="fw-light">{{ __('Company Details')}}</span></h4>

<div class="d-flex justify-content-between">
  <div>
    <h4 class="fw-bold py-2 mb-1"><span class="fw-light">{{ __('Company ID')}}</span><strong>#{{ $company->business_identification_number }}</strong></h4>
    <h6>{{ $company->created_at->format('M d Y, H:i A') }}</h6>
  </div>
</div>

<!-- Examples -->
<div class="row mb-5">
  <div class="col-md-6 col-lg-4 mb-3">
    <div class="card">
      <div class="card-body">
        @if ($company->logo)
          <img class="img-fluid d-flex mx-auto my-4 rounded" src="{{ $company->logo }}" alt="Card image cap" />
        @endif
        <h4 class="card-text text-center fw-bold">{{ $company->name }}</h4>
        <small class="card-text text-center">{{ __('Company ID')}} <strong>#{{ $company->unique_identification_number }}</strong></small>
        <hr>
        <div class="d-flex mb-2" style="height: 25px">
          <h5 class="me-2"><strong>{{ __('Approval Status')}}:</strong></h5>
          <span class="badge {{ $company->resolveApprovalStatus() }} me-1">{{ Str::title($company->approval_status) }}</span>
        </div>
        <div class="d-flex mb-2" style="height: 25px">
          <h5 class="me-2"><strong>{{ __('Active Status')}}:</strong></h5>
          <span class="badge {{ $company->resolveStatus() }} me-1">{{ Str::title($company->status) }}</span>
        </div>
        <h5><strong>KRA PIN: </strong><span class="fw-light">{{ $company->kra_pin }}</span></h5>
        <h5><strong>{{ __('Branch Code')}}: </strong><span class="fw-light">{{ $company->branch_code }}</span></h5>
        <h5><strong>{{ __('Organization Type')}}: </strong><span class="fw-light">{{ $company->organization_type }}</span></h5>
        <h5><strong>{{ __('City')}}: </strong><span class="fw-light">{{ Str::title($company->city) }}</span></h5>
        <h5><strong>{{ __('Industry')}}: </strong><span class="fw-light">{{ Str::title($company->business_segment) }}</span></h5>
        <h5><strong>C{{ __('Customer Type')}}: </strong><span class="fw-light">{{ Str::title($company->customer_type) }}</span></h5>
      </div>
    </div>
    @if ($company->rejection_reason)
        <div class="card mt-2">
          <div class="card-header">
            <h6>{{ __('Rejection Reason')}}</h6>
          </div>
          <div class="card-body">
            <p>{{ $company->rejection_reason }}</p>
          </div>
        </div>
    @endif
  </div>
  <div class="col-md-6 col-lg-8">
    {{-- <div id="company-users" class="mb-2">
      <company-users-component bank={{ request()->route('bank')->url }} company={{ $company->id }}></company-users-component>
    </div> --}}
    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between m-2">
          <h4 class="">{{ __('Compliance Documents')}}</h4>
        </div>
      </div>
      <div class="card-body">
        @if ($company->pipeline)
          @forelse ($company->pipeline->uploadedDocuments as $uploaded_documents)
            @foreach ($uploaded_documents->companyDocuments as $key => $document)
              <div class="accordion-item card my-1 border-bottom border-secondary">
                <div class="accordion-header p-1 d-flex justify-content-between">
                  <h2 class="">
                    <button type="button" class="accordion-button collapsed px-2 py-0" data-bs-toggle="collapse" data-bs-target="#accordionStyle1-{{ $key }}" aria-expanded="false">
                      {{ $document->original_name }}
                      <span class="badge {{ $document->resolveStatus() }} rounded-lg mx-1">{{ Str::title($document->status) }}</span>
                    </button>
                  </h2>
                </div>
                <div id="accordionStyle1-{{ $key }}" class="accordion-collapse collapse" data-bs-parent="#accordionStyle1">
                  <div class="accordion-body">
                    <div class="d-flex">
                      <a href="{{ config('app.backend_url') }}/backend/storage/{{ $document->path }}" target="_blank" class="btn btn-sm btn-success">{{ __('View') }} {{ $document->original_name }}</a>
                    </div>
                    @if ($document->rejected_reason)
                      <hr>
                      <h6>{{ __('Rejected Reason') }}</h6>
                      <p>{{ $document->rejected_reason }}</p>
                    @endif
                    <hr>
                    <small class="text-muted">{{ __('Uploaded on')}} {{ $document->created_at->format('d M Y') }}</small>
                    <span class="badge bg-label-secondary h-75"></span>
                  </div>
                </div>
              </div>
            @endforeach
          @empty
            <div class="accordion-item card">
              <div class="accordion-header p-1 d-flex justify-content-between show">
                <span class="badge bg-label-danger">{{ __('Compliance Documents Not Uploaded Yet')}}</span>
              </div>
            </div>
          @endforelse
        @else
          <div class="accordion" id="accordionStyle1">
            @forelse ($company->documents as $key => $document)
              <div class="accordion-item card">
                <div class="accordion-header p-1 d-flex justify-content-between">
                  <h2 class="">
                    <button type="button" class="accordion-button collapsed px-2 py-0" data-bs-toggle="collapse" data-bs-target="#accordionStyle1-{{ $key }}" aria-expanded="false">
                      {{ $document->name }}
                      <span class="badge {{ $document->resolveStatus() }} rounded-lg mx-1">{{ Str::title($document->status) }}</span> @if ($document->expiry_date)<span class="badge bg-label-success mx-1">Expires in {{ Carbon\Carbon::parse($document->expiry_date)->format('Y') }}</span> @endif
                    </button>
                  </h2>
                </div>
                <div id="accordionStyle1-{{ $key }}" class="accordion-collapse collapse" data-bs-parent="#accordionStyle1">
                  <div class="accordion-body">
                    <div class="d-flex">
                      <a href="{{ $document->path }}" target="_blank" class="btn btn-sm btn-success">{{ __('View') }} {{ $document->original_name }}</a>
                    </div>
                    @if ($document->rejected_reason)
                      <hr>
                      <h6>{{ __('Rejected Reason')}}</h6>
                      <p>{{ $document->rejected_reason }}</p>
                    @endif
                    <hr>
                    <small class="text-muted">{{ __('Uploaded on')}} {{ $document->created_at->format('d M Y') }}</small>
                    <span class="badge bg-label-secondary h-75"></span>
                  </div>
                </div>
              </div>
            @empty
              <div class="accordion-item card">
                <div class="accordion-header p-1 d-flex justify-content-between show">
                  <span class="badge bg-label-danger">{{ __('Compliance Documents Not Uploaded Yet')}}</span>
                </div>
              </div>
            @endforelse
          </div>
        @endif
      </div>
    </div>
    <div class="card my-2">
      <div class="card-header">
        <h4>{{ __('Relationship Managers')}}</h4>
      </div>
      <div class="card-body">
        @foreach ($company->relationshipManagers as $manager)
          <div class="d-flex flex-nowrap justify-content-between">
            <h6><strong>{{ __('Name')}}: </strong><span class="fw-light">{{ $manager->name }}</span></h6>
            <h6><strong>{{ __('Email')}}: </strong><span class="fw-light">{{ $manager->email }}</span></h6>
            <h6><strong>{{ __('Phone Number')}}: </strong><span class="fw-light">{{ $manager->phone_number }}</span></h6>
          </div>
          <hr>
        @endforeach
      </div>
    </div>
  </div>
</div>
@endsection
