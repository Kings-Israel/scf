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

  $('#location-type').on('change', function() {
    let selected_value = $(this).val()
    let locationOptions = document.getElementById('location')

    while (locationOptions.options.length) {
      locationOptions.remove(0)
    }

    locationOptions.options.add(new Option('Location', ''))

    if (selected_value == 'Branch') {
      let branches = {!! json_encode($branches) !!}

      if (branches) {
        var i
        for (let i = 0; i < branches.length; i++) {
          var branch = new Option(branches[i].name, branches[i].name)
          locationOptions.options.add(branch)
        }
      }
    }
  })
</script>
@endsection

@section('content')
<h4 class="fw-bold mb-2 d-flex justify-content-between">
  <span class="fw-light">{{ __('Add Bank User')}}</span>
</h4>

<div class="card">
  <div class="card-body">
    <form action="{{ route('configurations.users.store', ['bank' => $bank]) }}" method="post">
      @csrf
      <div class="row g-3">
        <div class="col-sm-6">
          <label class="form-label" for="name">{{ __('Name') }}</label>
          <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" />
          <x-input-error :messages="$errors->get('name')" />
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="email">{{ __('Email') }}</label>
          <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" />
          <x-input-error :messages="$errors->get('email')" />
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="phone-number">{{ __('Phone Number') }}</label>
          <div class="row">
            <div class="col-5">
              <select name="country_code" id="country-code" class="form-control" required>
                <option value="">{{ __('Select Country Code') }}</option>
                @foreach ($countries as $country)
                  <option value="{{ $country->dial_code }}" @if(old('country_code') === $country->dial_code) selected @endif>{{ $country->name }}({{ $country->dial_code }})</option>
                @endforeach
              </select>
            </div>
            <div class="col-7">
              <input type="tel" id="phone-number" class="form-control mx-1" name="phone_number" placeholder="Enter Phone Number" maxlength="10" value="{{ old('phone_number') }}" />
            </div>
          </div>
          <x-input-error :messages="$errors->get('phone_number')" />
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="role">{{ __('Role') }}</label>
          <select name="role" id="" class="form-select">
            <option value="">{{ __('Select User\'s Role') }}</option>
            @foreach ($roles as $role)
              <option value="{{ $role->id }}" @if(old('role') === $role->id) selected @endif>{{ $role->RoleName }}</option>
            @endforeach
          </select>
          <x-input-error :messages="$errors->get('role')" />
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="phone-number">{{ __('Receive Notifications on')}}</label>
          <select name="receive_notifications" id="" class="form-select">
            <option value="">{{ __('Select Channel')}}</option>
            @foreach ($notification_channels as $key => $channel)
              <option value="{{ $key }}" @if(old('receive_notifications') === $key) selected @endif>{{ $channel }}</option>
            @endforeach
          </select>
          <x-input-error :messages="$errors->get('receive_notifications')" />
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="phone-number">{{ __('Reporting Manager')}}</label>
          <select name="reporting_manager" id="" class="form-select">
            <option value="">{{ __('Reporting Manager')}}</option>
            @foreach ($reporting_managers as $manager)
              <option value="{{ $manager->email }}" @if(old('reporting_manager') === $manager->email) selected @endif>{{ $manager->name }}</option>
            @endforeach
          </select>
          <x-input-error :messages="$errors->get('reporting_manager')" />
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="phone-number">{{ __('Record Visibility')}}</label>
          <select name="record_visibility" id="" class="form-select">
            <option value="">{{ __('Record Visibility')}}</option>
            @foreach ($visibilities as $visibility)
              <option value="{{ $visibility }}" @if(old('record_visibility') === $visibility) selected @endif>{{ $visibility }}</option>
            @endforeach
          </select>
          <x-input-error :messages="$errors->get('record_visibility')" />
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="phone-number">{{ __('Location Type')}}</label>
          <select name="location_type" id="location-type" class="form-select">
            <option value="">{{ __('Location Type')}}</option>
            @foreach ($location_types as $type)
              <option value="{{ $type }}" @if(old('location_type') === $type) selected @endif>{{ $type }}</option>
            @endforeach
          </select>
          <x-input-error :messages="$errors->get('location_type')" />
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="phone-number">{{ __('Location')}}</label>
          <select name="location" id="location" class="form-select">
            <option value="">{{ __('Location')}}</option>
          </select>
          <x-input-error :messages="$errors->get('location')" />
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="phone-number">{{ __('Applicable Product')}}</label>
          <select name="applicable_product[]" id="" class="form-select select2" multiple>
            <option value="">{{ __('Applicable Product')}}</option>
            @foreach ($products as $key => $product)
              <option value="{{ $key }}" @if(old('applicable_product') && in_array($key, old('applicable_product'))) selected @endif>{{ $product }}</option>
            @endforeach
          </select>
          <x-input-error :messages="$errors->get('applicable_product')" />
        </div>
      </div>
      <button type="submit" class="btn btn-primary mt-2">{{ __('Submit') }}</button>
    </form>
  </div>
</div>
@endsection
