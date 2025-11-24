@extends('layouts/layoutMaster')

@section('title', 'Documents Upload - ' . $company->name)

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/animate-css/animate.css')}}">
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/masonry/masonry.js')}}"></script>
@endsection

@section('page-script')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="fw-bold mb-2">
    <span class="fw-light">{{ __('Upload Documents for ' . $company->name)}}</span>
    <small class="text-danger">({{ __('Upload Documents for Applicable Products') }})</small>
  </h4>
  <div>
    <a href="{{ route('companies.show', ['bank' => request()->route('bank'), 'company' => $company]) }}" class="btn btn-danger">{{ __('Cancel')}}</a>
  </div>
</div>
<div class="card">
  <x-input-error :messages="$errors->get('files')" />
  <form action="{{ route('companies.documents.upload', ['bank' => $bank, 'company' => $company]) }}" method="post" enctype="multipart/form-data">
    @csrf
    <div class="d-flex flex-column mt-1 mb-2">
      @foreach ($documents as $key => $document)
        <div class="d-flex flex-column flex-wrap mx-3 gap-2">
          @if ($key == "")
            <h6 class="my-auto">
              {{ __('All Program Types') }}:
            </h6>
            <div class="d-flex flex-wrap gap-2 my-2">
              @foreach ($document as $document_data)
                <div class="">
                  <div class="d-flex justify-content-between">
                    <label for="{{ $document_data->name }}">{{ $document_data->name }}</label>
                  </div>
                  <input class="form-control" type="file" accept=".pdf,.jpg,.jpeg" name="files[{{ $document_data->name }}]" />
                </div>
              @endforeach
            </div>
          @else
            @if ($key === 'Vendor Financing')
              <div class="d-flex flex-column gap-2">
                @foreach ($document->groupBy('programCode.name') as $key => $document_data)
                  <hr>
                  <div class="d-flex flex-column flex-wrap gap-2">
                    <h6 class="my-auto">
                      {{ $key }}:
                    </h6>
                    <div class="d-flex flex-wrap gap-2 my-2">
                      @foreach ($document_data as $document_details)
                      <div class="">
                        <div class="d-flex justify-content-between">
                          <label for="{{ $document_details->name }}">{{ $document_details->name }}</label>
                        </div>
                        <input class="form-control" type="file" accept=".pdf,.jpg,.jpeg" name="files[{{ $document_details->name }}]" />
                      </div>
                      @endforeach
                    </div>
                  </div>
                @endforeach
              </div>
            @elseif($key === 'Dealer Financing')
              <hr>
              <h6 class="my-auto">
                {{ $key }}:
              </h6>
              <div class="d-flex flex-wrap my-2 gap-2">
                @foreach ($document as $document_data)
                <div class="">
                  <div class="d-flex justify-content-between">
                    <label for="{{ $document_data->name }}">{{ $document_data->name }}</label>
                  </div>
                  <input class="form-control" type="file" accept=".pdf,.jpg,.jpeg" name="files[{{ $document_data->name }}]" />
                </div>
                @endforeach
              </div>
            @endif
          @endif
        </div>
      @endforeach
    </div>
    <div class="d-flex my-2 mx-3">
      <button class="btn btn-primary" type="submit">
        {{ __('Submit') }}
      </button>
    </div>
  </form>
</div>
@can('View KYC Documents')
  <div class="card mt-2">
    <div class="card-body">
      @forelse ($company->documents->groupBy('name') as $key => $document_details)
        <div class="d-flex flex-column">
          <h5>
            {{ $key }}
          </h5>
          <div class="d-flex gap-4 flex-wrap">
            @foreach ($document_details as $num => $document)
              <div class="d-flex gap-1">
                <h6 class="my-auto">{{ $num + 1 }}.</h6>
                <i class="tf-icons ti ti-file ti-xl"></i>
                <a target="_blank" href="{{ App\Models\CompanyDocument::find($document->id)->path }}" class="text-primary my-auto">
                  <i class="tf-icons ti ti-eye ti-xs" title="View Document"></i>
                </a>
                <a href="{{ route('companies.document.delete', ['bank' => $bank, 'company' => $company, 'company_document' => $document]) }}" class="text-danger my-auto">
                  <i class="tf-icons ti ti-trash ti-xs" title="Delete Document"></i>
                </a>
              </div>
            @endforeach
          </div>
        </div>
        <hr>
      @empty
        <span class="badge bg-label-danger">{{ __('Compliance Documents Not Uploaded Yet')}}</span>
      @endforelse
    </div>
  </div>
@endcan
@endsection
