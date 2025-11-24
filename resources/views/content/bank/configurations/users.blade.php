@extends('layouts/layoutMaster')

@section('title', 'Users & Roles')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}">
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css')}}">
@endsection

@section('page-style')
<style>
  .table-responsive .dropdown,
  .table-responsive .btn-group,
  .table-responsive .btn-group-vertical {
      position: static;
  }
  .m_title::first-letter {
    text-transform: uppercase;
  }
</style>
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/moment/moment.js')}}"></script>
@endsection

@section('page-script')
<script>
  let user_permissions = {!! json_encode($user_permissions) !!}

  function selectGroupPermissions(id) {
    var checked = $('#group-'+id).is(':checked');
    if (checked) {
      $("div [data-target-role-id='" + id +"']").prop('checked', true);
    } else {
      $("div [data-target-role-id='" + id +"']").prop('checked', false);
    }
  }

  $('#select-role').on('change', function() {
    let permissions = $(this).find(':selected').data('permissions')
    let html = '<div class="mt-2">'
    if ($(this).val() == 'Bank') {
      permissions.forEach(group => {
        let counter = 0
        group.access_groups.forEach(permission => {
          if (user_permissions.includes(permission.name)) {
            counter += 1
          }
        })
        if (counter > 0) {
          html += '<div class="">'
          html += '<input type="checkbox" class="form-check-input border-primary" name="" id="group-'+group.id+'" value="' + group.id + '" onchange="selectGroupPermissions('+group.id+')" />'
          html += '<label class="form-label fw-bold px-2" for="' + group.id + '">' + group.name + '</label>'
          html += '<div class="row">'
          group.access_groups.forEach(permission => {
            if (user_permissions.includes(permission.name)) {
              html += '<div class="col-3">'
              html += '<input type="checkbox" class="form-check-input border-primary" data-target-role-id="'+permission.target_role_id+'" name="permission_ids[]" value="' + permission.id + '" />'
              html += '<label class="form-label fw-light px-2" for="' + permission.id + '">' + permission.name + '</label>'
              html += '</div>'
            }
          });
          html += '</div>'
          html += '</div>'
          html += '<hr />'
        }
      });
    } else {
      permissions.forEach(group => {
        html += '<div class="">'
        html += '<input type="checkbox" class="form-check-input border-primary" name="" id="group-'+group.id+'" value="' + group.id + '" onchange="selectGroupPermissions('+group.id+')" />'
        html += '<label class="form-label fw-bold px-2" for="' + group.id + '">' + group.name + '</label>'
        html += '<div class="row">'
        group.access_groups.forEach(permission => {
          html += '<div class="col-3">'
          html += '<input type="checkbox" class="form-check-input border-primary" data-target-role-id="'+permission.target_role_id+'" name="permission_ids[]" value="' + permission.id + '" />'
          html += '<label class="form-label fw-light px-2" for="' + permission.id + '">' + permission.name + '</label>'
          html += '</div>'
        });
        html += '</div>'
        html += '</div>'
        html += '<hr />'
      });
    }
    html += '</div>'

    $('#permissions-section').html(html)
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
<h4 class="fw-bold mb-2">
  <span class="fw-light">{{ __('User Management') }}</span>
</h4>

@canany(['View Users', 'Add/Edit Users'])
  <div class="card">
    <div class="p-1 d-flex justify-content-between">
      <h5 class="fw-bold py-1 my-2">
        <span class="fw-light px-2">{{ __('Bank Users Management') }}</span>
      </h5>
      @can('Add/Edit Users')
        <a href="{{ route('configurations.users.add', ['bank' => $bank]) }}">
          <button class="btn btn-primary btn-sm m-2">{{ __('Add User') }}</button>
        </a>
      @endcan
      <div class="modal fade" id="confirm-update-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
          <form action="{{ route('configurations.users.store', ['bank' => $bank]) }}" method="post">
            @csrf
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="modalCenterTitle">{{ __('Add User') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="row g-3 p-3">
                <div class="col-sm-6">
                  <label class="form-label" for="name">{{ __('Name') }}</label>
                  <input type="text" id="name" name="name" class="form-control" />
                  <x-input-error :messages="$errors->get('name')" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="email">{{ __('Email') }}</label>
                  <input type="email" id="email" name="email" class="form-control" />
                  <x-input-error :messages="$errors->get('email')" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="phone-number">{{ __('Phone Number') }}</label>
                  <div class="row">
                    <div class="col-5">
                      <select name="country_code" id="country-code" class="form-control" required>
                        <option value="">{{ __('Select Country Code') }}</option>
                        @foreach ($countries as $country)
                          <option value="{{ $country->dial_code }}">{{ $country->name }}({{ $country->dial_code }})</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-7">
                      <input type="tel" id="phone-number" class="form-control mx-1" name="phone_number" placeholder="Enter Phone Number" maxlength="10" />
                    </div>
                  </div>
                  <x-input-error :messages="$errors->get('phone_number')" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="role">{{ __('Role') }}</label>
                  <select name="role" id="" class="form-select">
                    <option value="">{{ __('Select User\'s Role') }}</option>
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
                    @foreach ($notification_channels as $key => $channel)
                      <option value="{{ $key }}">{{ $channel }}</option>
                    @endforeach
                  </select>
                  <x-input-error :messages="$errors->get('receive_notifications')" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="phone-number">{{ __('Reporting Manager')}}</label>
                  <select name="reporting_manager" id="" class="form-select">
                    <option value="">{{ __('Reporting Manager')}}</option>
                    @foreach ($reporting_managers as $manager)
                      <option value="{{ $manager->email }}">{{ $manager->name }}</option>
                    @endforeach
                  </select>
                  <x-input-error :messages="$errors->get('reporting_manager')" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="phone-number">{{ __('Record Visibility')}}</label>
                  <select name="record_visibility" id="" class="form-select">
                    <option value="">{{ __('Record Visibility')}}</option>
                    @foreach ($visibilities as $visibility)
                      <option value="{{ $visibility }}">{{ $visibility }}</option>
                    @endforeach
                  </select>
                  <x-input-error :messages="$errors->get('record_visibility')" />
                </div>
                <div class="col-sm-6">
                  <label class="form-label" for="phone-number">{{ __('Location Type')}}</label>
                  <select name="location_type" id="location-type" class="form-select">
                    <option value="">{{ __('Location Type')}}</option>
                    @foreach ($location_types as $type)
                      <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                  </select>
                  <x-input-error :messages="$errors->get('record_visibility')" />
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
                      <option value="{{ $key }}">{{ $product }}</option>
                    @endforeach
                  </select>
                  <x-input-error :messages="$errors->get('applicable_product')" />
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
    @can('View Users')
      <div id="bank-users" class="p-1">
        <bank-users bank={!! request()->route('bank')->url !!}></bank-users>
      </div>
    @endcan
  </div>
  <hr>
@endcanany
@can('Manage Roles')
  <div class="card">
    <div class="p-1 px-3">
      <h5 class="fw-bold py-1 my-2">
        <span>{{ __('Add New Role')}}</span>
      </h5>
      <form action="{{ route('role.store', ['bank' => $bank]) }}" method="post">
        @csrf
        <div class="row">
          <div class="col-sm-12 col-md-6">
            <div class="form-group">
              <label for="Role Name" class="form-label">{{ __('Role Name')}}</label>
              <input type="text" name="role_name" id="" class="form-control" value="{{ old('role_name') }}">
              <x-input-error :messages="$errors->get('role_name')" />
            </div>
          </div>
          <div class="col-sm-12 col-md-6">
            <div class="form-group">
              <label for="Role Name" class="form-label">{{ __('Role Description')}}</label>
              <input type="text" name="role_description" id="" class="form-control" value="{{ old('role_description') }}">
              <x-input-error :messages="$errors->get('role_description')" />
            </div>
          </div>
          <div class="col-sm-12 col-md-6">
            <div class="form-group">
              <label for="Role Type" class="form-label">{{ __('Role Type')}}</label>
              <select name="role_type" class="form-control" id="select-role">
                <option value="">{{ __('Select Role Type')}}</option>
                @foreach ($role_types as $role_type)
                  <option value="{{ $role_type->name }}" data-permissions="{{ $role_type->Groups }}">{{ $role_type->name }}</option>
                @endforeach
              </select>
              <x-input-error :messages="$errors->get('role_type')" />
            </div>
          </div>
        </div>
        <div class="form-group" id="permissions-section"></div>
        <div class="d-flex my-2">
          <button class="btn btn-primary" type="submit">{{ __('Submit')}}</button>
        </div>
      </form>
    </div>
  </div>
  <hr>
  <div class="card">
    <div class="p-1 px-3">
      <h5 class="fw-bold py-1 my-2">
        <span>{{ __('Roles Management')}}</span>
      </h5>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>{{ __('Name')}}</th>
              <th>{{ __('Type')}}</th>
              <th>{{ __('Description')}}</th>
              <th>{{ __('Permissions')}}</th>
              <th>{{ __('Status')}}</th>
              <th>{{ __('Actions')}}</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($bank_roles as $role)
              <tr>
                <td>
                  {{ $role->RoleName }}
                  @if ($role->change)
                    <i class="tf-icons ti ti-info-circle ti-xs text-danger" title="Role Updation Awaiting Approval"></i>
                  @endif
                </td>
                <td>{{ $role->RoleTypeName }}</td>
                <td>{{ $role->RoleDescription }}</td>
                <td>
                  <a href="#" data-bs-toggle="modal" data-bs-target="#view-role-{{ $role->id }}">
                    {{ $role->roleIDs->count() }}
                  </a>
                </td>
                <td>
                  <span class="m_title badge bg-label-{{ App\Helpers\Helpers::statusCss($role->status) }}">
                    {{ $role->status }}
                  </span>
                </td>
                <td>
                  <div class="d-flex">
                    <div class="mx-1">
                      <button class="btn btn-label-secondary btn-xs" data-bs-toggle="modal" data-bs-target="#view-role-{{ $role->id }}" type="button">{{ __('View') }}</button>
                    </div>
                    <div class="modal fade" id="view-role-{{ $role->id }}" tabindex="-1" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5>{{ $role->RoleName }} {{ __('Details') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <form action="{{ route('role.update', ['bank' => $bank, 'role' => $role]) }}" method="POST">
                            @csrf
                            <div class="modal-body">
                              <div class="form-group">
                                <label for="Role Name" class="form-label">{{ __('Role Name')}}</label>
                                <input type="text" name="role_name" id="" class="form-control" value="{{ $role->RoleName }}" readonly>
                              </div>
                              <div class="form-group">
                                <label for="Role Name" class="form-label">{{ __('Role Description')}}</label>
                                <input type="text" name="role_description" id="" class="form-control" value="{{ $role->RoleDescription }}" readonly>
                              </div>
                              <div class="form-group">
                                <label for="Role Name" class="form-label">{{ __('Role Type')}}</label>
                                <input type="text" name="role_type" id="" class="form-control" value="{{ $role->RoleTypeName }}" readonly>
                              </div>
                              <br>
                              @if ($role_types->where('name', $role->RoleTypeName)->first()?->Groups)
                                @foreach ($role_types->where('name', $role->RoleTypeName)->first()?->Groups as $groups)
                                  <span class="fw-bold">
                                    {{ $groups->name }}
                                  </span>
                                  <div class="row">
                                    @foreach ($groups->toArray()['access_groups'] as $access_group)
                                      <div class="col-4">
                                        <input type="checkbox" name="permission_ids[]" id="" class="form-check-input border-primary" disabled value="{{ $access_group['id'] }}" @if (in_array($access_group['id'], $role->roleIDs->pluck('access_right_group_id')->toArray())) checked @endif>
                                        <label for="{{ $access_group['name'] }}" class="form-label fw-light">{{ $access_group['name'] }}</label>
                                      </div>
                                    @endforeach
                                  </div>
                                  <hr>
                                @endforeach
                              @endif
                            </div>
                          </form>
                          <div class="modal-footer">
                            @if ($role->bank_id != NULL && $role->status == 'pending' && auth()->id() != $role->user_id)
                              <div>
                                <button class="btn btn-success btn-xs" data-bs-toggle="modal" data-bs-target="#approve-role-{{ $role->id }}" type="button">{{ __('Approve') }}</button>
                              </div>
                              <div class="mx-1">
                                <button class="btn btn-danger btn-xs" data-bs-toggle="modal" data-bs-target="#reject-role-{{ $role->id }}" type="button">{{ __('Reject') }}</button>
                              </div>
                            @endif
                          </div>
                        </div>
                      </div>
                    </div>
                    @if ($role->bank_id != NULL && $role->status == 'approved' && !$role->change)
                      <div>
                        <button class="btn btn-warning btn-xs" data-bs-toggle="modal" data-bs-target="#edit-role-{{ $role->id }}" type="button">{{ __('Edit') }}</button>
                      </div>
                      <div class="modal fade" id="edit-role-{{ $role->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5>{{ __('Edit') }} {{ $role->RoleName }}</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="{{ route('role.update', ['bank' => $bank, 'role' => $role]) }}" method="POST">
                              @csrf
                              <div class="modal-body">
                                <div class="form-group">
                                  <label for="Role Name" class="form-label">{{ __('Role Name')}}</label>
                                  <input type="text" name="role_name" id="" class="form-control" value="{{ $role->RoleName }}">
                                </div>
                                <div class="form-group">
                                  <label for="Role Name" class="form-label">{{ __('Role Description')}}</label>
                                  <input type="text" name="role_description" id="" class="form-control" value="{{ $role->RoleDescription }}">
                                </div>
                                <div class="form-group">
                                  <label for="Role Name" class="form-label">{{ __('Role Type')}}</label>
                                  <input type="text" name="role_type" id="" class="form-control" value="{{ $role->RoleTypeName }}" readonly>
                                </div>
                                <br>
                                @foreach ($role_types->where('name', $role->RoleTypeName)->first()->Groups as $groups)
                                  <span class="fw-bold">
                                    {{ $groups->name }}
                                  </span>
                                  <div class="row">
                                    @foreach ($groups->toArray()['access_groups'] as $access_group)
                                      <div class="col-4">
                                        <input type="checkbox" name="permission_ids[]" id="" class="form-check-input border-primary" value="{{ $access_group['id'] }}" @if (in_array($access_group['id'], $role->roleIDs->pluck('access_right_group_id')->toArray())) checked @endif>
                                        <label for="{{ $access_group['name'] }}" class="form-label fw-light">{{ $access_group['name'] }}</label>
                                      </div>
                                    @endforeach
                                  </div>
                                  <hr>
                                @endforeach
                              </div>
                              <div class="modal-footer">
                                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal" aria-label="Close">{{ __('Close')}}</button>
                                <button type="submit" class="btn btn-label-primary">{{ __('Update')}}</button>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>
                    @endif
                    @if ($role->change && $role->change->created_by != auth()->id())
                      <div>
                        <button class="btn btn-danger btn-xs" data-bs-toggle="modal" data-bs-target="#edit-role-{{ $role->id }}" type="button">{{ __('View Changes') }}</button>
                      </div>
                      <div class="modal fade" id="edit-role-{{ $role->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5>{{ __('Changes to') }} {{ $role->RoleName }}</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                              <div class="row">
                                @if (array_key_exists('RoleName', $role->change->changes))
                                  <div class="col-6">
                                    <div class="form-group">
                                      <label for="Role Name" class="form-label">{{ __('Role Name')}}</label>
                                      <input type="text" name="role_name" id="" class="form-control" value="{{ $role->change->changes['RoleName'] }}" readonly>
                                    </div>
                                  </div>
                                @endif
                                @if (array_key_exists('RoleDescription', $role->change->changes))
                                  <div class="col-6">
                                    <div class="form-group">
                                      <label for="Role Name" class="form-label">{{ __('Role Description')}}</label>
                                      <input type="text" name="role_description" id="" class="form-control" value="{{ $role->change->changes['RoleDescription'] }}" readonly>
                                    </div>
                                  </div>
                                @endif
                                @if (array_key_exists('RoleTypeName', $role->change->changes))
                                  <div class="col-6">
                                    <div class="form-group">
                                      <label for="Role Name" class="form-label">{{ __('Role Type')}}</label>
                                      <input type="text" name="role_type" id="" class="form-control" value="{{ $role->change->changes['RoleTypeName'] }}" readonly>
                                    </div>
                                  </div>
                                @endif
                              </div>
                              <br>
                              @if (array_key_exists('additional_permissions', $role->change->changes) && count($role->change->changes['additional_permissions']) > 0)
                                <div class="d-flex flex-column">
                                  <h4>{{ __('Permission to be Added') }}</h4>
                                  <div class="row">
                                    @foreach ($role_types->where('name', $role->RoleTypeName)->first()->Groups as $groups)
                                      @foreach ($groups->toArray()['access_groups'] as $access_group)
                                        @if (in_array($access_group['id'], $role->change->changes['additional_permissions']))
                                          <div class="col-3">{{ $access_group['name'] }},</div>
                                        @endif
                                      @endforeach
                                    @endforeach
                                  </div>
                                </div>
                              @endif
                              <br>
                              @if (array_key_exists('removed_permissions', $role->change->changes) && count($role->change->changes['removed_permissions']) > 0)
                                <div class="d-flex flex-column">
                                  <h4>{{ __('Permissions to be Removed') }}</h4>
                                  <div class="row">
                                    @foreach ($role_types->where('name', $role->RoleTypeName)->first()->Groups as $groups)
                                      @foreach ($groups->toArray()['access_groups'] as $access_group)
                                        @if (in_array($access_group['id'], $role->change->changes['removed_permissions']))
                                          <span class="col-3">{{ $access_group['name'] }},</span>
                                        @endif
                                      @endforeach
                                    @endforeach
                                  </div>
                                </div>
                              @endif
                            </div>
                            <div class="modal-footer">
                              <button class="btn btn-secondary" type="button" data-bs-dismiss="modal" aria-label="Close">{{ __('Close')}}</button>
                              <a href="{{ route('role.changes.approve', ['bank' => $bank, 'role' => $role, 'status' => 'reject']) }}" class="btn btn-label-danger">{{ __('Reject')}}</a>
                              <a href="{{ route('role.changes.approve', ['bank' => $bank, 'role' => $role, 'status' => 'approve']) }}" class="btn btn-label-primary">{{ __('Approve')}}</a>
                            </div>
                          </div>
                        </div>
                      </div>
                    @endif
                    @if ($role->bank_id != NULL && $role->status == 'pending' && auth()->id() != $role->user_id)
                      <div>
                        <button class="btn btn-success btn-xs" data-bs-toggle="modal" data-bs-target="#approve-role-{{ $role->id }}" type="button">{{ __('Approve') }}</button>
                      </div>
                      <div class="modal fade" id="approve-role-{{ $role->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5>{{ __('Approve') }} {{ $role->RoleName }}</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="{{ route('role.status.update', ['bank' => $bank, 'role' => $role]) }}" method="POST">
                              @csrf
                              <input type="hidden" name="status" value="approved">
                              <div class="modal-body">
                                <h5 class="text-wrap">{{ __('Are you sure you want to approve the role') }} {{ $role->RoleName }}?</h5>
                              </div>
                              <div class="modal-footer">
                                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal" aria-label="Close">{{ __('Close')}}</button>
                                <button type="submit" class="btn btn-label-primary">{{ __('Confirm')}}</button>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>
                    @endif
                    @if ($role->bank_id != NULL && $role->status == 'pending' && auth()->id() != $role->user_id)
                      <div class="mx-1">
                        <button class="btn btn-danger btn-xs" data-bs-toggle="modal" data-bs-target="#reject-role-{{ $role->id }}" type="button">{{ __('Reject') }}</button>
                      </div>
                      <div class="modal fade" id="reject-role-{{ $role->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5>{{ __('Reject') }} {{ $role->RoleName }}</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="{{ route('role.status.update', ['bank' => $bank, 'role' => $role]) }}" method="POST">
                              @csrf
                              <input type="hidden" name="status" value="rejected">
                              <div class="modal-body">
                                <h5 class="text-wrap">{{ __('Are you sure you want to reject the role') }} {{ $role->RoleName }}?</h5>
                              </div>
                              <div class="modal-footer">
                                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal" aria-label="Close">{{ __('Close')}}</button>
                                <button type="submit" class="btn btn-label-primary">{{ __('Confirm')}}</button>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>
                    @endif
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endcan
@endsection
