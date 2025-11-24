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
  <span class="fw-light">{{ __('Edit User')}}</span>
</h4>

<div class="card">
  <div class="card-body">
    <form action="{{ route('configurations.users.update', ['bank' => $bank, 'user' => $user]) }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="row g-3">
        <div class="col-sm-6">
          <label class="form-label" for="name">{{ __('Name')}}</label>
          <input type="text" id="name" name="name" class="form-control" @if($user->id == auth()->id()) readonly @endif value="{{ $user->name }}" />
          <x-input-error :messages="$errors->get('name')" />
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="email">{{ __('Email')}}</label>
          <input type="email" id="email" name="email" class="form-control" @if($user->id == auth()->id()) readonly @endif value="{{ $user->email }}" />
          <x-input-error :messages="$errors->get('email')" />
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="phone-number">{{ __('Phone Number')}}</label>
          <input type="tel" id="phone-number" class="form-control" name="phone_number" @if($user->id == auth()->id()) readonly @endif value="{{ $user->phone_number }}" maxlength="13" />
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
          @php
            $user_role_id = ''
          @endphp
          <select name="role" id="" class="form-select">
            <option value="">{{ __('Select User\'s Role')}}</option>
            @if($user->id == auth()->id())
              @foreach ($roles as $role)
                @if ($role->RoleName == $user->roles[0]->name)
                  @php
                    $user_role_id = $role->id
                  @endphp
                @endif
              @endforeach
              <option value="{{ $user_role_id }}" selected>{{ $user->roles[0]->name}}</option>
            @else
              @foreach ($roles as $role)
                <option value="{{ $role->id }}" @if($user->roles[0]->name == $role->RoleName) selected @endif>{{ $role->RoleName }}</option>
              @endforeach
            @endif
          </select>
          <x-input-error :messages="$errors->get('role')" />
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="phone-number">{{ __('Receive Notifications on')}}</label>
          <select name="receive_notifications" id="" class="form-select">
            <option value="">{{ __('Select Channel')}}</option>
            @foreach ($notification_channels as $key => $channel)
              <option value="{{ $key }}" @if($user->receive_notifications == $key) selected @endif>{{ $channel }}</option>
            @endforeach
          </select>
          <x-input-error :messages="$errors->get('receive_notifications')" />
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="phone-number">{{ __('Reporting Manager')}}</label>
            @if($user->id == auth()->id())
              <input type="text" id="reporting_manager" name="reporting_manager" class="form-control" readonly value="{{ $user->reporting_manager }}" />
            @else
              <select name="reporting_manager" id="" class="form-select">
                <option value="">{{ __('Reporting Manager')}}</option>
                @foreach ($reporting_managers as $manager)
                  <option value="{{ $manager->email }}" @if($user->reporting_manager == $manager->email) selected @endif>{{ $manager->name }}</option>
                @endforeach
              </select>
              <x-input-error :messages="$errors->get('reporting_manager')" />
            @endif
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="phone-number">{{ __('Record Visibility')}}</label>
          <select name="record_visibility" id="" class="form-select">
            <option value="">{{ __('Record Visibility')}}</option>
            @foreach ($visibilities as $visibility)
              <option value="{{ $visibility }}" @if($user->record_visibility == $visibility) selected @endif>{{ $visibility }}</option>
            @endforeach
          </select>
          <x-input-error :messages="$errors->get('record_visibility')" />
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="phone-number">{{ __('Location Type')}}</label>
          <select name="location_type" id="location-type" class="form-select">
            <option value="">{{ __('Location Type')}}</option>
            @foreach ($location_types as $type)
              <option value="{{ $type }}" @if($user->location_type == $type) selected @endif>{{ $type }}</option>
            @endforeach
          </select>
          <x-input-error :messages="$errors->get('record_visibility')" />
        </div>
        <div class="col-sm-6">
          <label class="form-label" for="phone-number">{{ __('Location')}}</label>
          <select name="location" id="location" class="form-select">
            <option value="">{{ __('Location')}}</option>
            @if($user->id == auth()->id())
              <option value="{{ $user->location }}" selected>{{ $user->location}}</option>
            @else

            @endif
          </select>
          <x-input-error :messages="$errors->get('location')" />
        </div>
        <div class="col-sm-6">
          <div class="d-flex justify-content-between">
            <label class="form-label" for="phone-number">{{ __('Applicable Product')}}</label>
            <div>
              @if ($user->applicable_product)
                @if(gettype($user->applicable_product) == 'string')
                  {{ str_replace('_', ' ', str_replace('"', '', str_replace('[', '', str_replace(']', '', $user->applicable_product)))) }}
                @else
                  @foreach ($user->applicable_product as $product)
                    <span>{{ str_replace('_', ' ', Str::title($product)) }}</span>
                  @endforeach
                @endif
              @endif
            </div>
          </div>
          <select name="applicable_product[]" id="" class="form-select select2" multiple>
            <option value="">{{ __('Applicable Product')}}</option>
            {{-- @if($user->id == auth()->id())
              <option value="{{ $user->applicable_products }}" selected>{{ $user->applicable_products}}</option>
            @else
            @endif --}}
            @foreach ($products as $key => $product)
              <option value="{{ $key }}">{{ $product }}</option>
            @endforeach
          </select>
          <x-input-error :messages="$errors->get('applicable_product')" />
        </div>
      </div>
      <br>
      <div class="d-flex justify-content-between">
        <a href="{{ route('configurations.users', ['bank' => $bank]) }}" class="btn btn-label-secondary">
          {{ __('Cancel')}}
        </a>
        <button type="submit" class="btn btn-primary"> <span class="align-middle d-sm-inline-block d-none me-sm-1">{{ __('Submit')}}</span></button>
      </div>
    </form>
  </div>
</div>
@endsection
