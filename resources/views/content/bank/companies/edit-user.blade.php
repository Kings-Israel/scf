@extends('layouts/layoutMaster')

@section('title', 'Edit User')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/bs-stepper/bs-stepper.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/bootstrap-select/bootstrap-select.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/select2/select2.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css')}}" />
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/bs-stepper/bs-stepper.js')}}"></script>
<script src="{{asset('assets/vendor/libs/bootstrap-select/bootstrap-select.js')}}"></script>
<script src="{{asset('assets/vendor/libs/select2/select2.js')}}"></script>
<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js')}}"></script>
<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js')}}"></script>
<script src="{{asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js')}}"></script>
@endsection

@section('page-script')
<script>
  $(function () {
    const select2 = $('.select2'),
      selectPicker = $('.selectpicker');

    // Bootstrap select
    if (selectPicker.length) {
      selectPicker.selectpicker();
    }

    // select2
    if (select2.length) {
      select2.each(function () {
        var $this = $(this);
        $this.wrap('<div class="position-relative"></div>');
        $this.select2({
          placeholder: 'Select value',
          dropdownParent: $this.parent()
        });
      });
    }
  });

  $('#user_email').on('change', function() {
    let user = $(this).find(':selected').data('user');

    $('#phone-number').val(user.phone_number);
    $('#name').val(user.name)
  })
</script>
@endsection

@section('content')
<h4 class="fw-bold mb-2 d-flex justify-content-between">
  <span class="fw-light">{{ __('Edit User')}}</span>
</h4>

<div class="card">
  <div class="card-body">
    <form action="{{ route('companies.users.update', ['bank' => $bank, 'company' => $company, 'user' => $user]) }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="row g-3">
        <div class="col-sm-6">
          <label class="form-label" for="name">{{ __('Name')}}</label>
          <input type="text" id="name" name="name" class="form-control" value="{{ $user->name }}" required />
          <x-input-error :messages="$errors->get('name')" />
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="email">{{ __('Email')}}</label>
          <input type="email" id="email" name="email" class="form-control" value="{{ $user->email }}" required />
          <x-input-error :messages="$errors->get('email')" />
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="phone-number">{{ __('Phone Number')}}</label>
          <input type="tel" id="phone-number" class="form-control" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" maxlength="15" minlength="13" required />
          <x-input-error :messages="$errors->get('phone_number')" />
        </div>
        <div class="col-sm-6">
          <div class="d-flex justify-content-between">
            <label class="form-label" for="role">{{ __('Role')}}</label>
            @if ($user->roles->count() > 0)
              <span class="text-primary" data-bs-toggle="modal" data-bs-target="#current-roles-modal" style="cursor: pointer">{{ __('View Current Roles')}}</span>
              <div class="modal modal-top fade" id="current-roles-modal" tabindex="-1">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalTopTitle">{{ $user->name }}'s {{ __('Current Roles')}}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      @foreach ($user->roles as $role)
                        <span class="btn btn-xs btn-label-primary mx-1">{{ $role->name }}</span>
                      @endforeach
                    </div>
                  </div>
                </div>
              </div>
            @endif
          </div>
          <select name="role" id="" class="form-select">
            <option value="">{{ __('Select User\'s Role')}}</option>
            @foreach ($roles as $role)
              <option value="{{ $role->id }}">{{ $role->RoleName }}</option>
            @endforeach
          </select>
          <x-input-error :messages="$errors->get('role')" />
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="phone-number">{{ __('Receive Notifications on')}}</label>
          <select name="receive_notifications" id="" class="form-select">
            <option value="">{{ __('Select Channel')}}</option>
            @if($user->id == auth()->id())
              <option value="{{ $user->receive_notifications }}" selected>{{ $user->receive_notifications}}</option>
            @else
              @foreach ($notification_channels as $key => $channel)
                <option value="{{ $key }}" @if($user->receive_notifications == $key) selected @endif>{{ $channel }}</option>
              @endforeach
            @endif
          </select>
          <x-input-error :messages="$errors->get('receive_notifications')" />
        </div>
        @if (collect($company->roles->pluck('name'))->contains('anchor') || collect($company->roles->pluck('name'))->contains('buyer'))
          <div class="col-sm-6">
            <label class="form-label" for="role">{{ __('Vendor Financing Group')}}</label>
            @php($vendor_financing_group_id = $user->authorizationGroups->where('company_id', $company->id)->where('programType.name', 'Vendor Financing')->first() ? $user->authorizationGroups->where('company_id', $company->id)->where('programType.name', 'Vendor Financing')->first()->id : NULL)
            <select name="authorization_group_id" id="" class="form-select">
              <option value="">Select Group</option>
              @foreach ($authorization_groups as $group)
                <option value="{{ $group->id }}" @if ($group->id == $vendor_financing_group_id) selected @endif>{{ $group->name }}</option>
              @endforeach
            </select>
            <x-input-error :messages="$errors->get('authorization_group_id')" />
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="role">{{ __('Dealer Financing Group')}}</label>
            @php($dealer_financing_group_id = $user->authorizationGroups->where('company_id', $company->id)->where('programType.name', 'Dealer Financing')->first() ? $user->authorizationGroups->where('company_id', $company->id)->where('programType.name', 'Dealer Financing')->first()->id : NULL)
            <select name="dealer_financing_authorization_group_id" id="" class="form-select">
              <option value="">Select Group</option>
              @foreach ($dealer_financing_authorization_groups as $group)
                <option value="{{ $group->id }}" @if ($group->id == $dealer_financing_group_id) selected @endif>{{ $group->name }}</option>
              @endforeach
            </select>
            <x-input-error :messages="$errors->get('dealer_financing_authorization_group_id')" />
          </div>
        @endif
        <div class="col-sm-12 mt-3">
          <input type="checkbox" name="resend_link" id="" class="form-check-input">
          <label for="" class="form-label">{{ __('Resend Login Link')}}</label>
        </div>
      </div>
      <br>
      <div class="d-flex justify-content-between">
        <a href="{{ route('companies.show', ['bank' => $bank, 'company' => $company]) }}">
          <button class="btn btn-label-secondary" type="button">
            <span class="align-middle d-sm-inline-block d-none">{{ __('Cancel')}}</span>
          </button>
        </a>
        <button type="submit" class="btn btn-primary"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Submit')}}</span></button>
      </div>
    </form>
  </div>
</div>
@endsection
