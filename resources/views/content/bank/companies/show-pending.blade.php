@extends('layouts/layoutMaster')

@section('title', 'Company Details')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/animate-css/animate.css')}}">
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/masonry/masonry.js')}}"></script>
@endsection

@section('page-script')
@endsection

@section('content')
<div class="d-flex justify-content-between">
  <div>
    <h4 class="fw-bold py-2 mb-2"><span class="fw-light">{{ __('Opportunity Details')}}</span></h4>

    <h6>{{ $pipeline->created_at->format('M d Y, H:i A') }}</h6>
  </div>
  @if ($pipeline->hasAllDocumentsApproved())
    <div>
      <a href="{{ route('companies.create', ['bank' => $bank, 'pipeline' => $pipeline]) }}" class="btn btn-primary">
        {{ __('Create Company')}}
      </a>
    </div>
  @endif
</div>

<!-- Examples -->
<div class="row mb-5">
  <div class="col-md-6 col-lg-4 mb-2">
    <div class="card">
      <div class="card-body">
        <h4 class="card-text text-center fw-bold">{{ $pipeline->company ? $pipeline->company : $pipeline->name }}</h4>
        <hr>
        <h5><strong>{{ __('Email')}}: </strong>{{ $pipeline->email }}</h5>
        <h5><strong>{{ __('Phone')}}: </strong>{{ $pipeline->phone_number }}</h5>
        <h5><strong>{{ __('City')}}: </strong>{{ Str::title($pipeline->region) }}</h5>
        <h5><strong>{{ __('Industry')}}: </strong>{{ Str::title($pipeline->department) }}</h5>
      </div>
    </div>
    <div class="card mt-2">
      <div class="card-header">
        <h4>{{ __('Required Documents') }}</h4>
      </div>
      <div class="card-body">
        <div class="flex">
          @foreach ($required_documents as $required_document)
            @if ($loop->last)
              <span>{{ $required_document }}</span>
            @else
              <span class="mx-1">{{ $required_document.',' }}</span>
            @endif
          @endforeach
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-lg-8">
    <div class="d-flex justify-content-between m-2">
      <h4 class="">{{ __('Compliance Documents')}}</h4>
    </div>
    <div class="accordion" id="accordionStyle1">
      @forelse ($pipeline->uploadedDocuments as $uploaded_documents)
        @foreach ($uploaded_documents->companyDocuments as $key => $document)
          @if ($document->crm_approval_status == 'approved')
            <div class="accordion-item card">
              <div class="accordion-header p-1 d-flex justify-content-between">
                <h2 class="my-auto">
                  <button type="button" class="accordion-button collapsed px-2 py-0" data-bs-toggle="collapse" data-bs-target="#accordionStyle1-{{ $key }}" aria-expanded="false">
                    {{ $document->original_name }}
                    @if ($document->document_name)
                        - {{ $document->document_name }}
                    @endif
                    <span class="badge {{ $document->resolveStatus() }} rounded-lg mx-1">{{ Str::title($document->status) }}</span>
                  </button>
                </h2>
                <div class="d-flex">
                  @if ($document->status == 'pending')
                    <button class="btn btn-label-danger mx-2" type="button" data-bs-toggle="modal" data-bs-target="#rejectDocumentModal-{{ $document->id }}">{{ __('Reject')}}</button>
                    <div class="modal modal-top fade" id="rejectDocumentModal-{{ $document->id }}" tabindex="-1">
                      <div class="modal-dialog">
                        <form class="modal-content" method="POST" action="{{ route('pipelines.pending.documents.status.update', ['bank' => $bank, 'pipeline' => $pipeline]) }}">
                          @csrf
                          <input type="hidden" name="status" value="rejected">
                          <input type="hidden" name="document_id" value="{{ $document->id }}">
                          <div class="modal-header">
                            <h5 class="modal-title" id="modalTopTitle">{{ __('Rejection Reason')}}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <div class="row">
                              <label for="nameSlideTop" class="form-label">{{ __('Enter Rejection Reason')}}</label>
                              <textarea class="form-control" id="" name="rejected_reason" rows="3"></textarea>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Close')}}</button>
                            <button type="submit" class="btn btn-primary">{{ __('Submit')}}</button>
                          </div>
                        </form>
                      </div>
                    </div>
                    <form action="{{ route('pipelines.pending.documents.status.update', ['bank' => $bank, 'pipeline' => $pipeline]) }}" method="post">
                      @csrf
                      <input type="hidden" name="status" value="approved">
                      <input type="hidden" name="document_id" value="{{ $document->id }}">
                      <button type="submit" class="btn btn-label-primary">{{ __('Approve')}}</button>
                    </form>
                  @elseif($document->status == 'pending')
                    <form action="{{ route('pipelines.pending.documents.status.update', ['bank' => $bank, 'pipeline' => $pipeline]) }}" method="post">
                      @csrf
                      <input type="hidden" name="status" value="approved">
                      <input type="hidden" name="document_id" value="{{ $document->id }}">
                      <button type="submit" class="btn btn-label-primary">{{ __('Approve')}}</button>
                    </form>
                  @elseif ($document->status == 'pending')
                    <button class="btn btn-sm btn-label-secondary mx-2" data-bs-toggle="modal" data-bs-target="#rejectDocumentModal-{{ $document->id }}">{{ __('Reject')}}</button>
                    <div class="modal modal-top fade" id="rejectDocumentModal-{{ $document->id }}" tabindex="-1">
                      <div class="modal-dialog">
                        <form class="modal-content" method="POST" action="{{ route('pipelines.pending.documents.status.update', ['bank' => $bank, 'pipeline' => $pipeline]) }}">
                          @csrf
                          <input type="hidden" name="status" value="rejected">
                          <input type="hidden" name="document_id" value="{{ $document->id }}">
                          <div class="modal-header">
                            <h5 class="modal-title" id="modalTopTitle">{{ __('Rejection Reason for')}} {{ $document->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <div class="row">
                              <label for="nameSlideTop" class="form-label">{{ __('Enter Rejection Reason')}}</label>
                              <textarea class="form-control" id="" name="rejection_reason" rows="3"></textarea>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Close')}}</button>
                            <button type="submit" class="btn btn-primary">{{ __('Submit')}}</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  @endif
                </div>
              </div>
              <div id="accordionStyle1-{{ $key }}" class="accordion-collapse collapse" data-bs-parent="#accordionStyle1">
                <div class="accordion-body">
                  <div class="d-flex">
                    <a href="{{ config('app.backend_url') }}/storage/{{ $document->path }}" target="_blank" class="btn btn-sm btn-success">{{ __('View')}} {{ $document->original_name }}</a>
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
          @endif
        @endforeach
      @empty
        <div class="accordion-item card">
          <div class="accordion-header p-1 d-flex justify-content-between show">
            <span class="badge bg-label-danger">{{ __('Compliance Documents Not Uploaded Yet')}}</span>
          </div>
        </div>
      @endforelse
    </div>
  </div>
</div>
<!-- Examples -->

@endsection
