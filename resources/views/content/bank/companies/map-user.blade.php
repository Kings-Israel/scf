@extends('layouts/layoutMaster')

@section('title', 'Add User')

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
  <span class="fw-light">{{ __('Add User')}}</span>
</h4>

<div class="card">
  <div class="card-body">
    <form action="{{ route('companies.users.map.store', ['bank' => $bank, 'company' => $company]) }}" method="POST" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="mode" value="{{ $mode }}">
      @if ($mode == 'create')
        <div class="row g-3">
          <div class="col-sm-6">
            <label class="form-label" for="name">{{ __('Name')}}</label>
            <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" />
            <x-input-error :messages="$errors->get('name')" />
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="email">{{ __('Email')}}</label>
            <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" />
            <x-input-error :messages="$errors->get('email')" />
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="phone-number">{{ __('Phone Number')}}</label>
            <div class="row">
              <div class="col-4">
                <select name="country_code" id="" class="select2" required>
                  <option value="">{{ __('Select Country Code')}}</option>
                  @foreach ($countries as $country)
                    <option value="{{ $country->dial_code }}" @if(old('country_code') == $country->dial_code) selected @endif>{{ $country->name }}({{ $country->dial_code }})</option>
                  @endforeach
                </select>
              </div>
              <div class="col-8">
                <input type="tel" id="phone-number" class="form-control mx-1" name="phone_number" placeholder="Enter Phone Number" maxlength="12" value="{{ old('phone_number') }}" required />
              </div>
            </div>
            <x-input-error :messages="$errors->get('phone_number')" />
            <x-input-error :messages="$errors->get('country_code')" />
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="role">{{ __('Role')}}</label>
            <select name="role" id="" class="form-select">
              <option value="">{{ __('Select User\'s Role')}}</option>
              @foreach ($roles as $role)
                <option value="{{ $role->id }}" @if(old('role') == $role->id) selected @endif>{{ $role->RoleName }}</option>
              @endforeach
            </select>
            <x-input-error :messages="$errors->get('role')" />
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="phone-number">{{ __('Receive Notifications on')}}</label>
            <select name="receive_notifications" id="" class="form-select">
              <option value="">{{ __('Select Channel')}}</option>
              @foreach ($notification_channels as $key => $channel)
                <option value="{{ $key }}" @if(old('receive_notifications') == $key) selected @endif>{{ $channel }}</option>
              @endforeach
            </select>
            <x-input-error :messages="$errors->get('receive_notifications')" />
          </div>
        </div>
      @else
        <div class="row g-3">
          <div class="col-sm-6">
            <label class="form-label" for="email">{{ __('Email')}}</label>
            <select name="email" id="user_email" class="select2">
              <option value="">{{ __('Search User By Email')}}</option>
              @foreach ($users as $user)
                <option value="{{ $user->email }}" data-user="{{ $user }}">{{ $user->email }}</option>
              @endforeach
            </select>
            {{-- <input type="email" id="email" name="email" class="form-control" /> --}}
            <x-input-error :messages="$errors->get('email')" />
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="name">{{ __('Name')}}</label>
            <input type="text" readonly id="name" name="name" class="form-control" />
            <x-input-error :messages="$errors->get('name')" />
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="phone-number">{{ __('Phone Number')}}</label>
            <input type="tel" id="phone-number" readonly class="form-control" name="phone_number" readonly />
            <x-input-error :messages="$errors->get('phone_number')" />
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="role">{{ __('Role')}}</label>
            <select name="role" id="" class="form-select">
              <option value="">{{ __('Select User\'s Role')}}</option>
              @foreach ($roles as $role)
                <option value="{{ $role->id }}">{{ $role->RoleName }}</option>
              @endforeach
            </select>
            <x-input-error :messages="$errors->get('role')" />
          </div>
          {{-- <div class="col-sm-6">
            <label class="form-label" for="phone-number">{{ __('Receive Notifications on')}}</label>
            <select name="receive_notifications" id="" class="form-select">
              <option value="">{{ __('Select Channel')}}</option>
              @foreach ($notification_channels as $key => $channel)
                <option value="{{ $key }}">{{ $channel }}</option>
              @endforeach
            </select>
            <x-input-error :messages="$errors->get('receive_notifications')" />
          </div> --}}
        </div>
      @endif
      @if (collect($company->roles->pluck('name'))->contains('anchor') || collect($company->roles->pluck('name'))->contains('buyer'))
        <div class="row g-3 mt-1">
          <div class="col-sm-6">
            <label class="form-label" for="role">{{ __('Vendor Financing Group')}}</label>
            <select name="authorization_group_id" id="" class="form-select">
              <option value="">{{ __('Select Group')}}</option>
              @foreach ($authorization_groups as $group)
                <option value="{{ $group->id }}" @if(old('authorization_group_id') == $group->id) selected @endif>{{ $group->name }}</option>
              @endforeach
            </select>
            <x-input-error :messages="$errors->get('authorization_group_id')" />
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="role">{{ __('Dealer Financing Group')}}</label>
            <select name="dealer_financing_authorization_group_id" id="" class="form-select">
              <option value="">{{ __('Select Group')}}</option>
              @foreach ($dealer_financing_authorization_groups as $group)
                <option value="{{ $group->id }}" @if(old('dealer_financing_authorization_group_id') == $group->id) selected @endif>{{ $group->name }}</option>
              @endforeach
            </select>
            <x-input-error :messages="$errors->get('dealer_financing_authorization_group_id')" />
          </div>
        </div>
      @endif
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
