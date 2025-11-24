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
<h4 class="fw-bold mb-2"><span class="fw-light">{{ __('Company Details')}}</span></h4>

<div class="d-flex justify-content-between">
  <div>
    <h6><span class="fw-light">{{ __('Created On')}}</span>: {{ $company->created_at->format('M d Y, H:i A') }}</h6>
  </div>
  <div>
    @can('Add/Edit Users')
      @if (!$company->proposedUpdate && $company->IsMappedToProgram())
        <a href="{{ route('companies.users.map', ['bank' => request()->route('bank'), 'company' => $company, 'mode' => 'map']) }}" class="btn btn-primary btn-sm">{{ __('Map Existing User')}}</a>
        <a href="{{ route('companies.users.map', ['bank' => request()->route('bank'), 'company' => $company, 'mode' => 'create']) }}" class="btn btn-info btn-sm">{{ __('Create New User')}}</a>
      @endif
    @endcan
    @can('Add/Edit Companies')
      @if ($company->approval_status == 'approved' && !$company->proposedUpdate)
        <a href="{{ route('companies.edit', ['bank' => request()->route('bank'), 'company' => $company]) }}" class="btn btn-warning btn-sm">{{ __('Edit')}}</a>
      @elseif($company->approval_status == 'pending')
        <div class="d-flex">
          @if ($company->created_by != auth()->id())
            <form action="{{ route('companies.status.update', ['bank' => $bank, 'company' => $company]) }}" method="post">
              @csrf
              <input type="hidden" name="status" value="approved">
              <button class="btn btn-primary btn-sm mx-2" type="submit">{{ __('Approve')}}</button>
            </form>
          @endif
          @if ($company->approval_status == 'pending' && $company->created_by != auth()->id())
            <button class="btn btn-danger btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#updateApprovalStatus">{{ __('Reject')}}</button>
            <div class="modal modal-top fade" id="updateApprovalStatus" tabindex="-1">
              <div class="modal-dialog">
                <form class="modal-content" method="POST" action="{{ route('companies.status.update', ['bank' => $bank, 'company' => $company]) }}">
                  @csrf
                  <div class="modal-header">
                    <h6 class="modal-title" id="modalTopTitle">{{ __('Update Company Status')}}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <div class="form-group">
                      <label for="nameSlideTop" class="form-label">{{ __('Enter Rejection Reason')}}</label>
                      <textarea class="form-control" id="" name="rejection_reason"></textarea>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn bg-label-secondary" data-bs-dismiss="modal">{{ __('Close')}}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Submit')}}</button>
                  </div>
                </form>
              </div>
            </div>
          @endif
        </div>
      @elseif($company->approval_status == 'rejected' && $company->created_by == auth()->id())
        <a href="{{ route('companies.edit', ['bank' => request()->route('bank'), 'company' => $company]) }}" class="btn btn-warning btn-sm">{{ __('Edit')}}</a>
      @endif
    @endcan
    @can('Company Changes Checker')
      @if ($company->proposedUpdate)
        <button class="btn btn-label-danger btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#updateCompany">{{ __('View Proposed Changes')}}</button>
        <div class="modal modal-top fade" id="updateCompany" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h6 class="modal-title" id="modalTopTitle">{{ __('Proposed Company Update')}}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="row">
                  @if (array_key_exists('Company Details', $company->proposedUpdate->changes) && count($company->proposedUpdate->changes['Company Details']) > 0)
                    <div class="col-12">
                      <h4 class="mb-0">{{ __('General Details') }}</h4>
                      @foreach ($company->proposedUpdate->changes['Company Details'] as $key => $details_changes)
                        <div>
                          <span><strong>{{ Str::title(Str::replace('_', ' ', $key)) }}:</strong></span>
                          <span>{{ $details_changes }}</span>
                        </div>
                      @endforeach
                    </div>
                    <hr>
                  @endif
                  @if (array_key_exists('Relationship Manager', $company->proposedUpdate->changes) && count($company->proposedUpdate->changes['Relationship Manager']) > 0)
                    <div class="col-12">
                      <div class="row">
                        <h4 class="mb-0">{{ __('Relationship Manager(s)')}}</h4>
                        @foreach ($company->proposedUpdate->changes['Relationship Manager'] as $id => $relationship_manager)
                          <div class="col-6">
                            @foreach ($relationship_manager as $key => $details)
                              @if ($key != 'company_id')
                                <div>
                                  <span><strong>{{ Str::title(Str::replace('_', ' ', $key)) }}:</strong></span>
                                  <span>{{ $details }}</span>
                                </div>
                              @endif
                            @endforeach
                            <hr>
                          </div>
                        @endforeach
                      </div>
                    </div>
                    <hr>
                  @endif
                  @if (array_key_exists('Bank Details', $company->proposedUpdate->changes) && count($company->proposedUpdate->changes['Bank Details']) > 0)
                    <div class="col-12">
                      <div class="row">
                        <h4 class="mb-0">{{ __('Bank Details')}}</h4>
                        @foreach ($company->proposedUpdate->changes['Bank Details'] as $id => $bank_details)
                          <div class="col-6">
                            @foreach ($bank_details as $key => $details)
                              @if ($key != 'company_id')
                                <div>
                                  <span><strong>{{ Str::title(Str::replace('_', ' ', $key)) }}:</strong></span>
                                  <span>{{ $details }}</span>
                                </div>
                              @endif
                            @endforeach
                            <hr>
                          </div>
                        @endforeach
                      </div>
                    </div>
                    <hr>
                  @endif
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Close')}}</button>
                @if (auth()->id() != $company->proposedUpdate->user_id && auth()->user()->hasPermissionTo('Company Changes Checker'))
                  <a class="btn btn-danger" href="{{ route('companies.updates.approve', ['bank' => $bank, 'company' => $company, 'status' => 'reject']) }}">{{ __('Reject')}}</a>
                  <a class="btn btn-primary" href="{{ route('companies.updates.approve', ['bank' => $bank, 'company' => $company, 'status' => 'approve']) }}">{{ __('Approve')}}</a>
                @endif
              </div>
            </div>
          </div>
        </div>
      @endif
    @endcan
    @if (!$company->proposedUpdate && collect($company->roles?->pluck('name'))->contains('anchor') || collect($company->roles?->pluck('name'))->contains('buyer'))
      @can('Manage Authorization Group')
        <a href="{{ route('companies.manage-authorization-groups', ['bank' => request()->route('bank'), 'company' => $company]) }}" class="btn btn-danger btn-sm">{{ __('Manage Authorization Groups') }}</a>
      @endcan
      @can('Manage Authorization Matrix')
        <a href="{{ route('companies.manage-authorization-matrix', ['bank' => request()->route('bank'), 'company' => $company]) }}" class="btn btn-danger btn-sm">{{ __('Manage Authorization Matrix') }}</a>
      @endcan
    @endif
  </div>
</div>

<div class="row mb-5">
  <div class="col-md-6 col-lg-4 mb-3">
    <div class="card">
      <div class="card-body">
        @if ($company->logo)
          <img class="img-fluid d-flex mx-auto my-4 rounded" src="{{ $company->logo }}" alt="Card image cap" />
        @endif
        <h4 class="card-text text-center fw-bold">{{ $company->name }}</h4>
        @if (!$company->IsMappedToProgram())
          <a href="{{ route('programs.index', ['bank' => request()->route('bank')]) }}" class="mx-auto d-flex justify-content-center">
            <span class="badge bg-label-danger my-1">{{ __('Company is not mapped to any program')}}</span>
          </a>
        @endif
        <hr>
        <div class="d-flex my-auto">
          <h6 class="me-2"><strong>{{ __('Approval Status')}}:</strong></h6>
          <span class="badge mt-0 my-auto {{ $company->resolveApprovalStatus() }}">{{ Str::title($company->approval_status) }}</span>
        </div>
        <div class="d-flex my-auto">
          <h6 class="me-2"><strong>{{ __('Active Status')}}:</strong></h6>
          <span class="badge mt-0 my-auto {{ $company->resolveStatus() }}">{{ Str::title($company->status) }}</span>
        </div>
        <h6><strong>{{ __('KRA PIN')}}: </strong><span class="fw-light">{{ $company->kra_pin }}</span></h6>
        <h6><strong>{{ __('Branch Code')}}: </strong><span class="fw-light">{{ $company->branch_code }}</span></h6>
        <h6><strong>{{ __('Organization Type')}}: </strong><span class="fw-light">{{ $company->organization_type }}</span></h6>
        <h6><strong>{{ __('Address')}}: </strong><span class="fw-light">{{ Str::title($company->address) }}</span></h6>
        <h6><strong>{{ __('Pin/Zip/Postal Code')}}: </strong><span class="fw-light">{{ Str::title($company->postal_code) }}</span></h6>
        <h6><strong>{{ __('City')}}: </strong><span class="fw-light">{{ Str::title($company->city) }}</span></h6>
        @if ($company->business_segment)
          <h6><strong>{{ ('Industry')}}: </strong><span class="fw-light">{{ Str::title($company->business_segment) }}</span></h6>
        @endif
        <h6><strong>{{ __('Customer Type')}}: </strong><span class="fw-light">{{ Str::title($company->customer_type) }}</span></h6>
        <h6><strong>{{ __('Unique ID')}}: </strong><span class="fw-light">{{ $company->unique_identification_number }}</span></h6>
        <h6><strong>{{ __('Business ID')}}: </strong><span class="fw-light">{{ $company->business_identification_number }}</span></h6>
      </div>
      <div class="card-footer d-flex justify-content-end">
        @if ($company->approval_status === 'pending' || $company->approval_status === 'rejected')
          @if ($company->created_by != auth()->id())
            <form action="{{ route('companies.status.update', ['bank' => $bank, 'company' => $company]) }}" method="post">
              @csrf
              <input type="hidden" name="status" value="approved">
              <button class="btn btn-primary btn-sm mx-2" type="submit">{{ __('Approve')}}</button>
            </form>
          @endif
        @endif
        @if ($company->approval_status == 'pending' && $company->created_by != auth()->id())
          <button class="btn btn-danger btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#updateApprovalStatus">{{ __('Reject')}}</button>
          <div class="modal modal-top fade" id="updateApprovalStatus" tabindex="-1">
            <div class="modal-dialog">
              <form class="modal-content" method="POST" action="{{ route('companies.status.update', ['bank' => $bank, 'company' => $company]) }}">
                @csrf
                <div class="modal-header">
                  <h6 class="modal-title" id="modalTopTitle">{{ __('Update Company Status')}}</h6>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div class="form-group">
                    <label for="nameSlideTop" class="form-label">{{ __('Enter Rejection Reason')}}</label>
                    <textarea class="form-control" id="" name="rejection_reason"></textarea>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn bg-label-secondary" data-bs-dismiss="modal">{{ __('Close')}}</button>
                  <button type="submit" class="btn btn-primary">{{ __('Submit')}}</button>
                </div>
              </form>
            </div>
          </div>
        @endif
      </div>
    </div>
    @if ($company->approval_status == 'rejected')
      <div class="card">
        <div class="card-header">
          <h6>{{ __('Rejection Reason')}}</h6>
        </div>
        <div class="card-body">
          <p>{{ $company->rejection_reason }}</p>
        </div>
      </div>
      <br>
    @endif
    @if ($company->top_level_borrower_limit)
      <div class="card mt-4">
        <div class="card-body">
          <div class="d-flex">
            <h6>{{ __('Top Level Limit')}}:</h6>
            <h6 class="mx-1">{{ number_format($company->top_level_borrower_limit, 2) }}</h6>
          </div>
          <div class="d-flex">
            <h6>{{ __('Utilized Amount') }}</h6>
            <h6 class="mx-1">{{ number_format($company->utilized_amount, 2) }}</h6>
          </div>
          <div class="d-flex">
            <h6>{{ __('Pipeline Amount') }}</h6>
            <h6 class="mx-1">{{ number_format($company->pipeline_amount, 2) }}</h6>
          </div>
          <div class="d-flex">
            <h6>{{ __('Available Amount') }}</h6>
            <h6 class="mx-1">{{ number_format($company->top_level_borrower_limit - $company->utilized_amount - $company->pipeline_amount, 2) }}</h6>
          </div>
        </div>
      </div>
      <br>
    @endif
  </div>
  <div class="col-md-6 col-lg-8">
    @can('View Company Users')
      <div id="company-users">
        <company-users-component bank={{ request()->route('bank')->url }} company={{ $company->id }} anchor={{ collect($company->roles->pluck('name'))->contains('anchor') || collect($company->roles->pluck('name'))->contains('buyer') ? 'true' : 'false' }}></company-users-component>
      </div>
      <br>
    @endcan
    <div class="card mb-4">
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
    @can('View KYC Documents')
      <div class="card p-2">
        <div class="card-header px-2 py-1 mb-0">
          <div class="d-flex justify-content-between">
            <h4 class="">{{ __('Compliance Documents')}}</h4>
            @if ($company->pipeline)
              <div class="d-flex">
                <div>
                  <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#requestDocumentsModal">{{ __('Request More Documents')}}</button>
                </div>
                @can('Add KYC Documents')
                  <div>
                    <a href="{{ route('companies.documents-upload', ['bank' => request()->route('bank'), 'company' => $company]) }}" class="btn btn-danger btn-sm">{{ __('Upload Documents')}}</a>
                  </div>
                @endcan
              </div>
              <div class="modal modal-top fade" id="requestDocumentsModal" tabindex="-1">
                <div class="modal-dialog">
                  <form class="modal-content" method="POST" action="{{ route('companies.documents.request', ['bank' => $bank, 'company' => $company]) }}">
                    @csrf
                    <div class="modal-header">
                      <h6 class="modal-title" id="modalTopTitle">{{ __('Request More Compliance Documents')}}</h6>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="form-group">
                        <label for="nameSlideTop" class="form-label">{{ __('Enter Documents (each document separated by a comma)')}}</label>
                        <input class="form-control" id="" name="documents" />
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">{{ __('Close')}}</button>
                      <button type="submit" class="btn btn-primary btn-sm">{{ __('Submit')}}</button>
                    </div>
                  </form>
                </div>
              </div>
            @else
              <div class="d-flex gap-2">
                <div>
                  <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#requestDocumentsModal">{{ __('Request Documents')}}</button>
                </div>
                @can('Add KYC Documents')
                <div>
                  <a href="{{ route('companies.documents-upload', ['bank' => request()->route('bank'), 'company' => $company]) }}" class="btn btn-danger btn-sm">{{ __('Upload Documents')}}</a>
                </div>
                @endcan
              </div>
              <div class="modal modal-top fade" id="requestDocumentsModal" tabindex="-1">
                <div class="modal-dialog">
                  <form class="modal-content" method="POST" action="{{ route('companies.documents.request', ['bank' => $bank, 'company' => $company]) }}">
                    @csrf
                    <div class="modal-header">
                      <h6 class="modal-title" id="modalTopTitle">{{ __('Request For Compliance Documents')}}</h6>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      {{-- @foreach ($bank->requiredDocuments as $required_document)
                        <div class="form-group">
                          <input class="form-check-input" name="documents[{{ $required_document->name }}]" value="{{ $required_document->name }}" id="" type="checkbox" />
                          <label for="nameSlideTop" class="form-check-label">{{ $required_document->name }}</label>
                        </div>
                      @endforeach --}}
                      @foreach ($bank->requiredDocuments->groupBy('programType.name') as $key => $document)
                        <div class="d-flex flex-column flex-wrap mx-3 gap-2">
                          @if ($key == "")
                            <h6 class="my-auto">
                              {{ __('All Program Types') }}:
                            </h6>
                            <div class="d-flex flex-wrap gap-2 my-2">
                              @foreach ($document as $document_data)
                                <div class="d-flex gap-1">
                                  <input class="form-check-input" name="documents[{{ $document_data->name }}]" value="{{ $document_data->name }}" id="" type="checkbox" />
                                  <label for="nameSlideTop" class="form-check-label">{{ $document_data->name }}</label>
                                </div>
                              @endforeach
                            </div>
                          @else
                            @if ($key == 'Vendor Financing')
                              <div class="d-flex flex-column gap-2">
                                @foreach ($document->groupBy('programCode.name') as $key => $document_data)
                                  <div class="d-flex flex-wrap flex-column gap-2">
                                    <h6 class="my-auto">
                                      {{ $key }}:
                                    </h6>
                                    <div class="d-flex flex-wrap gap-2 my-2">
                                      @foreach ($document_data as $document_details)
                                        <div class="d-flex gap-1">
                                          <input class="form-check-input" name="documents[{{ $document_details->name }}]" value="{{ $document_details->name }}" id="" type="checkbox" />
                                          <label for="nameSlideTop" class="form-check-label">{{ $document_details->name }}</label>
                                        </div>
                                      @endforeach
                                    </div>
                                  </div>
                                @endforeach
                              </div>
                            @elseif($key == 'Dealer Financing')
                              <h6 class="my-auto">
                                {{ $key }}:
                              </h6>
                              <div class="d-flex flex-wrap gap-2 my-2">
                                @foreach ($document as $document_data)
                                  <div class="d-flex gap-1">
                                    <input class="form-check-input" name="documents[{{ $document_data->name }}]" value="{{ $document_data->name }}" id="" type="checkbox" />
                                    <label for="nameSlideTop" class="form-check-label">{{ $document_data->name }}</label>
                                  </div>
                                @endforeach
                              </div>
                            @endif
                          @endif
                        </div>
                      @endforeach
                      <br>
                      <div class="form-group">
                        <label for="send request to" class="form-label">{{ __('Send Request To')}}:</label>
                        <input type="email" name="send_to_email" class="form-control" placeholder="Enter Email" required />
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">{{ __('Close')}}</button>
                      <button type="submit" class="btn btn-primary btn-sm">{{ __('Submit')}}</button>
                    </div>
                  </form>
                </div>
              </div>
            @endif
          </div>
        </div>
        <div class="card-body px-2 py-1">
          @if ($company->pipeline)
            @forelse ($company->pipeline->uploadedDocuments as $uploaded_documents)
              @foreach ($uploaded_documents->companyDocuments as $key => $document)
                <div class="accordion-item card my-1 border-bottom border-secondary">
                  <div class="accordion-header p-1 d-flex justify-content-between">
                    <h2 class="">
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
                        <button class="btn btn-label-danger btn-sm mx-2" data-bs-toggle="modal" data-bs-target="#rejectDocumentModal-{{ $document->id }}">{{ __('Reject')}}</button>
                        <div class="modal modal-top fade" id="rejectDocumentModal-{{ $document->id }}" tabindex="-1">
                          <div class="modal-dialog">
                            <form class="modal-content" method="POST" action="{{ route('pipelines.pending.documents.status.update', ['bank' => $bank, 'pipeline' => $pipeline]) }}">
                              @csrf
                              <input type="hidden" name="status" value="rejected">
                              <input type="hidden" name="document_id" value="{{ $document->id }}">
                              <div class="modal-header">
                                <h6 class="modal-title" id="modalTopTitle">{{ __('Rejection Reason')}}</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body">
                                <div class="row">
                                  <label for="nameSlideTop" class="form-label">{{ __('Enter Rejection Reason')}}</label>
                                  <textarea class="form-control" id="" name="rejected_reason" rows="3"></textarea>
                                </div>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">{{ __('Close')}}</button>
                                <button type="submit" class="btn btn-primary btn-sm">{{ __('Submit')}}</button>
                              </div>
                            </form>
                          </div>
                        </div>
                        <form action="{{ route('pipelines.pending.documents.status.update', ['bank' => $bank, 'pipeline' => $pipeline]) }}" method="post">
                          @csrf
                          <input type="hidden" name="status" value="approved">
                          <input type="hidden" name="document_id" value="{{ $document->id }}">
                          <button type="submit" class="btn btn-label-primary btn-sm">{{ __('Approve')}}</button>
                        </form>
                      @endif
                    </div>
                  </div>
                  <div id="accordionStyle1-{{ $key }}" class="accordion-collapse collapse" data-bs-parent="#accordionStyle1">
                    <div class="accordion-body mx-3">
                      <div class="d-flex">
                        <a href="{{ config('app.backend_url') }}/storage/{{ $document->path }}" target="_blank" class="btn btn-sm btn-success mb-2">{{ __('View') }} {{ $document->original_name }}</a>
                      </div>
                      @if ($document->rejected_reason)
                        <h6>{{ __('Rejected Reason')}}</h6>
                        <p>{{ $document->rejected_reason }}</p>
                      @endif
                      <small class="text-muted pt-2">{{ __('Uploaded on') }} {{ $document->created_at->format('d M Y') }}</small>
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
            @forelse ($company->documents->groupBy('name') as $key => $document_details)
              <div class="d-flex justify-content-between">
                <h5 class="">
                  {{ $key }}
                </h5>
              </div>
              <div class="d-flex flex-wrap gap-4">
                @foreach ($document_details as $num => $document)
                  <div class="d-flex flex-wrap">
                    <h6 class="my-auto">{{ $num + 1 }}.</h6>
                    <i class="tf-icons ti ti-file ti-xl"></i>
                    <div class="d-flex gap-1">
                      <span class="my-auto">{{ __('Status') }}:</span>
                      <span class="badge {{ $document->resolveStatus() }} rounded-lg mx-1 my-auto">{{ Str::title($document->status) }}</span>
                    </div>
                    <div class="mx-1 my-auto">
                      <a target="_blank" href="{{ App\Models\CompanyDocument::find($document->id)->path }}" class="text-primary">
                        <i class="tf-icons ti ti-eye ti-xs" title="View Document"></i>
                      </a>
                      @can('Delete KYC Documents')
                        <a href="{{ route('companies.document.delete', ['bank' => $bank, 'company' => $company, 'company_document' => $document]) }}" class="text-danger">
                          <i class="tf-icons ti ti-trash ti-xs" title="Delete Document"></i>
                        </a>
                      @endcan
                    </div>
                    <div class="d-flex">
                      @if ($document->status === 'pending')
                        <button class="btn btn-danger mx-2" type="button" data-bs-toggle="modal" data-bs-target="#rejectDocumentModal-{{ $document->id }}">{{ __('Reject')}}</button>
                        <div class="modal modal-top fade" id="rejectDocumentModal-{{ $document->id }}" tabindex="-1">
                          <div class="modal-dialog">
                            <form class="modal-content" method="POST" action="{{ route('companies.documents.status.update', ['bank' => $bank, 'company' => $company]) }}">
                              @csrf
                              <input type="hidden" name="status" value="rejected">
                              <input type="hidden" name="document_id" value="{{ $document->id }}">
                              <div class="modal-header">
                                <h6 class="modal-title" id="modalTopTitle">{{ __('Rejection Reason')}}</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body">
                                <div class="row">
                                  <label for="nameSlideTop" class="form-label">{{ __('Enter Rejection Reason')}}</label>
                                  <textarea class="form-control" id="" name="rejected_reason" rows="3"></textarea>
                                </div>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">{{ __('Close')}}</button>
                                <button type="submit" class="btn btn-primary btn-sm">{{ __('Submit')}}</button>
                              </div>
                            </form>
                          </div>
                        </div>
                        <form action="{{ route('companies.documents.status.update', ['bank' => $bank, 'company' => $company]) }}" method="post">
                          @csrf
                          <input type="hidden" name="status" value="approved">
                          <input type="hidden" name="document_id" value="{{ $document->id }}">
                          <button type="submit" class="btn btn-primary">{{ __('Approve')}}</button>
                        </form>
                      @endif
                    </div>
                    @if ($document->rejected_reason)
                      <hr>
                      <h6>{{ __('Rejected Reason') }}</h6>
                      <p>{{ $document->rejected_reason }}</p>
                    @endif
                  </div>
                @endforeach
              </div>
              <hr>
            @empty
              <div class="accordion-item card">
                <div class="accordion-header p-1 d-flex justify-content-between show">
                  <span class="badge bg-label-danger">{{ __('Compliance Documents Not Uploaded Yet')}}</span>
                </div>
              </div>
            @endforelse
          @endif
        </div>
      </div>
    @endcan
    <div class="mt-4">
      <div class="card">
        <div class="d-flex justify-content-between">
          <h5 class="card-header">{{ __('Programs')}}</h5>
          {{-- <a class="card-header" href="#">View All</a> --}}
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>{{ __('Payment/OD A/C No')}}.</th>
                <th>{{ __('Approved Limit')}}</th>
                <th>{{ __('Utilized Limit')}}</th>
                <th>{{ __('Pipeline Requests')}}</th>
                <th>{{ __('Available Limit')}}</th>
              </tr>
            </thead>
            <tbody class="table-border-bottom-0 text-nowrap">
              @foreach ($program_vendor_configurations as $vendor)
                <tr>
                  <td>{{ $vendor->payment_account_number }}</td>
                  <td class="text-success">{{ number_format($vendor->sanctioned_limit) }}</td>
                  <td class="text-success">{{ number_format($vendor->utilized_amount) }}</td>
                  <td class="text-success">{{ number_format($vendor->pipeline_amount) }}</td>
                  <td class="text-success">{{ number_format($vendor->sanctioned_limit - ($vendor->utilized_amount + $vendor->pipeline_amount)) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <br>
    @if ($company->requestedDocuments()->exists())
      <div class="card mt-2">
        <div class="card-header p-3 pb-0">
          <h6>{{ __('Requested Documents')}}</h6>
        </div>
        <div class="card-body d-flex px-3 pb-0">
          @foreach ($company->requestedDocuments as $document)
              @if ($loop->last)
                <h6 class="mx-1">{{ $document->name.'('.$document->status.')' }}</h6>
              @elseif ($loop->first)
                <h6>{{ $document->name.'('.$document->status.'), ' }}</h6>
              @else
                <h6 class="mx-1">{{ $document->name.'('.$document->status.'), ' }}</h6>
              @endif
          @endforeach
        </div>
      </div>
    @endif
  </div>
</div>
<!-- Examples -->

@endsection
