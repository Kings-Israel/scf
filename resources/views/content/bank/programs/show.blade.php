@extends('layouts/layoutMaster')

@section('title', 'Program Details')

@section('vendor-style')
@endsection

@section('vendor-script')
@endsection

@section('page-script')
@endsection

@section('content')
    <div class="d-flex justify-content-between">
        <h5 class="fw-light text-underline">{{ $program->name }} {{ __('Details') }}</h5>
        <div class="">
          @if (!$program->deleted_at)
            @can('Program Changes Checker')
              @if ($program->proposedUpdate)
                  <button class="btn btn-label-danger btn-sm" type="button" data-bs-toggle="modal"
                      data-bs-target="#updateCompany">{{ __('View Pending Changes') }}</button>
                  <div class="modal modal-top fade modal-lg" id="updateCompany" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h6 class="modal-title" id="modalTopTitle">{{ __('Proposed Program Update') }}</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <form action="{{ route('programs.updates.approve', ['bank' => $bank, 'program' => $program, 'status' => 'approve']) }}" method="post">
                              @csrf
                              <input type="hidden" name="status" value="approve">
                              <div class="modal-body">
                                <div class="row">
                                  @if (array_key_exists('Program Details', $program->proposedUpdate->changes) &&
                                          count($program->proposedUpdate->changes['Program Details']) > 0)
                                      <div class="col-12">
                                          <h4 class="mb-0">{{ __('General Details')}}</h4>
                                          @php($boolean_columns = collect(['buyer_invoice_approval_required', 'anchor_can_change_due_date', 'request_auto_finance', 'auto_debit_anchor_for_financed_invoices', 'auto_debit_anchor_for_non_financed_invoices', 'anchor_can_change_payment_term', 'mandatory_invoice_attachment']))
                                          @foreach ($program->proposedUpdate->changes['Program Details'] as $key => $details_changes)
                                            @if (!$boolean_columns->contains($key) && $details_changes)
                                              <div>
                                                  <span><strong>{{ Str::title(Str::replace('_', ' ', $key)) }}:</strong></span>
                                                  <span>{{ $details_changes }}</span>
                                              </div>
                                            @endif
                                            @if ($boolean_columns->contains($key))
                                              <div>
                                                <span><strong>{{ Str::title(str_replace('_', ' ', $key)) }}:</strong></span>
                                                @if ($details_changes == 0)
                                                  <span>{{ __('From yes to no') }}</span>
                                                @else
                                                  <span>{{ __('From no to yes') }}</span>
                                                @endif
                                              </div>
                                            @endif
                                          @endforeach
                                      </div>
                                      <hr>
                                  @endif
                                  @if (array_key_exists('Program Discount Details', $program->proposedUpdate->changes) &&
                                          count($program->proposedUpdate->changes['Program Discount Details']) > 0)
                                      <div class="col-12">
                                          <h4 class="mb-0">{{ __('Discount Details')}}</h4>
                                          @foreach ($program->proposedUpdate->changes['Program Discount Details'] as $key => $discount_details)
                                            @if ($discount_details)
                                              <div>
                                                  <span><strong>{{ Str::title(Str::replace('_', ' ', $key)) }}:</strong></span>
                                                  <span>{{ $discount_details }}</span>
                                              </div>
                                            @endif
                                          @endforeach
                                      </div>
                                      <hr>
                                  @endif
                                  @if (array_key_exists('Program Dealer Discount Rates', $program->proposedUpdate->changes) &&
                                          count($program->proposedUpdate->changes['Program Dealer Discount Rates']) > 0)
                                      <div class="col-12">
                                          <h4 class="mb-0">{{ __('Discount Rates')}}</h4>
                                          <div class="row">
                                              @foreach ($program->proposedUpdate->changes['Program Dealer Discount Rates'] as $id => $dealer_discount_details)
                                                  <div class="col-4">
                                                      @foreach ($dealer_discount_details as $key => $rate)
                                                          @if ($key != 'program_id')
                                                              <div>
                                                                  <span><strong>{{ Str::title(Str::replace('_', ' ', $key)) }}:</strong></span>
                                                                  <span>{{ $rate }}</span>
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
                                  @if (array_key_exists('Program Fees', $program->proposedUpdate->changes) &&
                                          count($program->proposedUpdate->changes['Program Fees']) > 0)
                                      <div class="col-12">
                                          <h4 class="mb-0">{{ __('Fee Details')}}</h4>
                                          <div class="row">
                                            @foreach ($program->proposedUpdate->changes['Program Fees'] as $discount_details)
                                              <div class="col-4">
                                                @foreach ($discount_details as $key => $rate)
                                                  @if ($key != 'program_id' && $key == 'name')
                                                    <div>
                                                      <span><strong>{{ Str::title(Str::replace('_', ' ', $key)) }}:</strong></span>
                                                      <span>{{ $rate }}</span>
                                                    </div>
                                                  @endif
                                                  @if ($key != 'program_id' && $key != 'name' && $key == 'anchor_bearing_discount')
                                                      <div>
                                                        <span><strong>{{ __('Anchor Bearing') }}:</strong></span>
                                                        <span>{{ $rate }}</span>
                                                      </div>
                                                  @elseif ($key != 'program_id' && $key != 'name' && $key == 'vendor_bearing_discount')
                                                      <div>
                                                        <span><strong>{{ __('Vendor Bearing') }}:</strong></span>
                                                        <span>{{ $rate }}</span>
                                                      </div>
                                                  @elseif ($key != 'program_id' && $key != 'name' && $key == 'deleted_at')
                                                      <div>
                                                        <span><strong>{{ __('Delete Fee') }}</strong></span>
                                                        {{-- <span>{{ $rate }}</span> --}}
                                                      </div>
                                                  @elseif ($key != 'program_id' && $key != 'name')
                                                      <div>
                                                        <span><strong>{{ Str::title(Str::replace('_', ' ', $key)) }}:</strong></span>
                                                        <span>{{ $rate }}</span>
                                                      </div>
                                                  @endif
                                                @endforeach
                                              </div>
                                            @endforeach
                                          </div>
                                      </div>
                                      <hr>
                                  @endif
                                  @if (array_key_exists('Program Anchor Details', $program->proposedUpdate->changes) &&
                                          count($program->proposedUpdate->changes['Program Anchor Details']) > 0)
                                      <div class="col-12">
                                          <h4 class="mb-0">{{ __('Anchor Contact Details')}}</h4>
                                          @foreach ($program->proposedUpdate->changes['Program Anchor Details'] as $anchor_details)
                                              @foreach ($anchor_details as $key => $anchor)
                                                  <div>
                                                      <span><strong>{{ Str::title(Str::replace('_', ' ', $key)) }}:</strong></span>
                                                      <span>{{ $anchor }}</span>
                                                  </div>
                                              @endforeach
                                          @endforeach
                                      </div>
                                      <hr>
                                  @endif
                                  @if (array_key_exists('Program Bank User Details', $program->proposedUpdate->changes) &&
                                          count($program->proposedUpdate->changes['Program Bank User Details']) > 0)
                                      <div class="col-12">
                                          <h4 class="mb-0">{{ __('Bank Contact')}}</h4>
                                          @foreach ($program->proposedUpdate->changes['Program Bank User Details'] as $bank_user_details)
                                              @foreach ($bank_user_details as $key => $bank_user)
                                                  <div>
                                                      <span><strong>{{ Str::title(Str::replace('_', ' ', $key)) }}:</strong></span>
                                                      <span>{{ $bank_user }}</span>
                                                  </div>
                                              @endforeach
                                          @endforeach
                                      </div>
                                      <hr>
                                  @endif
                                  @if (array_key_exists('Program Bank Details', $program->proposedUpdate->changes) &&
                                          count($program->proposedUpdate->changes['Program Bank Details']) > 0)
                                    <div class="col-12">
                                      <h4 class="mb-0">{{ __('Bank Details')}}</h4>
                                      @foreach ($program->proposedUpdate->changes['Program Bank Details'] as $bank_details)
                                        <div class="row">
                                          @foreach ($bank_details as $key => $bank_detail)
                                            <div class="col-6">
                                              @if ($key !== 'program_id' && $key !== 'deleted_at')
                                                <div>
                                                  <span><strong>{{ Str::title(Str::replace('_', ' ', $key)) }}:</strong></span>
                                                  <span>{{ $bank_detail }}</span>
                                                </div>
                                              @endif
                                              @if ($key != 'program_id' && $key == 'deleted_at')
                                                <div>
                                                  <span><strong>{{ __('Delete Bank Details') }}</strong></span>
                                                </div>
                                              @endif
                                            </div>
                                          @endforeach
                                        </div>
                                      @endforeach
                                    </div>
                                    <hr>
                                  @endif
                                </div>
                                @if (((array_key_exists('Program Discount Details', $program->proposedUpdate->changes) &&
                                      count($program->proposedUpdate->changes['Program Discount Details']) > 0) ||
                                      (array_key_exists('Program Dealer Discount Rates', $program->proposedUpdate->changes) &&
                                      count($program->proposedUpdate->changes['Program Dealer Discount Rates']) > 0) ||
                                      (array_key_exists('Program Fees', $program->proposedUpdate->changes) &&
                                      count($program->proposedUpdate->changes['Program Fees']) > 0)) &&
                                      (array_key_exists('Program Vendor Configurations', $program->proposedUpdate->changes) &&
                                      count($program->proposedUpdate->changes['Program Vendor Configurations']) > 0))
                                  @if ($program->proposedUpdate->user_id != auth()->id() && auth()->user()->hasPermissionTo('Program Changes Checker'))
                                    <div>
                                      <h6>{{ __('Select Program Mappings where changes will apply') }}:</h6>
                                    </div>
                                    @foreach ($mappings as $mapping)
                                      @if (collect($program->proposedUpdate->changes['Program Vendor Configurations'])->contains($mapping->id))
                                        <div class="form-check">
                                          <input class="form-check-input border-primary" name="program_id[]" type="checkbox" checked value="{{ $mapping->id }}" />
                                          <label for="{{ $mapping->id }}" class="form-label">{{ $mapping->buyer ? $mapping->buyer->name : $mapping->company->name }}({{ $mapping->payment_account_number }})</label>
                                        </div>
                                      @endif
                                    @endforeach
                                  @endif
                                @endif
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-label-secondary btn-sm"
                                    data-bs-dismiss="modal">{{ __('Close')}}</button>
                                @if ($program->proposedUpdate->user_id != auth()->id() && auth()->user()->hasPermissionTo('Program Changes Checker'))
                                    <button class="btn btn-danger btn-sm" type="button" data-bs-target="#rejectUpdate" data-bs-toggle="modal" type="modal">{{ __('Reject') }}</button>
                                    <button class="btn btn-primary btn-sm" type="submit">{{ __('Approve')}}</button>
                                @endif
                              </div>
                            </form>
                        </div>
                    </div>
                  </div>
                  <div class="modal modal-top fade modal-lg" id="rejectUpdate" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h6 class="modal-title" id="modalTopTitle">{{ __('Reject Proposed Update') }}</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                              <span class="my-2">{{ __('Are you sure you want to reject the proposed changes') }}?</span>
                              <form method="POST" action="{{ route('programs.updates.approve', ['bank' => $bank, 'program' => $program, 'status' => 'reject']) }}">
                                @csrf
                                <input type="hidden" name="status" value="reject">
                                <button class="btn btn-danger btn-sm" type="submit">{{ __('Reject') }}</button>
                              </form>
                            </div>
                        </div>
                    </div>
                  </div>
              @endif
            @endcan
          @endif
          @if ($program->status === 'approved' && !$program->proposedUpdate)
            @can('Add/Edit Program & Mapping')
              @if (!$program->deleted_at)
                <a href="{{ route('programs.edit', ['bank' => request()->route('bank')->url, 'program' => $program]) }}"
                    class="btn btn-warning btn-sm">{{ __('Edit') }}</a>
                @if ($program->programType->name == 'Vendor Financing')
                  @if ($program->programCode->name == 'Vendor Financing Receivable')
                      <a href="{{ route('programs.vendors.manage', ['bank' => request()->route('bank')->url, 'program' => $program]) }}"
                      class="btn btn-primary btn-sm">{{ __('Manage Vendors')}}</a>
                  @else
                      <a href="{{ route('programs.vendors.manage', ['bank' => request()->route('bank')->url, 'program' => $program]) }}"
                      class="btn btn-primary btn-sm">{{ __('Manage Buyers')}}</a>
                  @endif
                @else
                  <a href="{{ route('programs.vendors.manage', ['bank' => request()->route('bank')->url, 'program' => $program]) }}"
                      class="btn btn-primary btn-sm">{{ __('Manage Dealers')}}</a>
                @endif
              @endif
              @if ($program->can_delete)
                <button class="btn btn-danger btn-sm" type="button" data-bs-toggle="modal"
                    data-bs-target="#deleteProgram">{{ __('Delete Program') }}</button>
                  <div class="modal modal-top fade modal-lg" id="deleteProgram" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h6 class="modal-title" id="modalTopTitle">{{ __('Delete Program') }}</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                              <span class="my-2">{{ __('Are you sure you want to delete the program') }}?</span>
                            </div>
                            <div class="modal-footer">
                              <button class="btn btn-secondary btn-sm" type="button" data-bs-dismiss="modal">
                                {{ __('Cancel') }}
                              </button>
                              @if ($program->deleted_at)
                                <a href="{{ route('program.delete.cancel', ['bank' => request()->route('bank')->url, 'program' => $program]) }}" class="btn btn-primary btn-sm" type="submit">{{ __('Cancel Deletion') }}</a>
                              @endif
                              <form action="{{ route('program.delete', ['bank' => request()->route('bank')->url, 'program' => $program]) }}" method="post">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm" type="submit">{{ __('Delete') }}</button>
                              </form>
                            </div>
                        </div>
                    </div>
                  </div>
              @endif
            @endcan
          @endif
          @if ($program->status === 'rejected' && $program->created_by === auth()->id() && !$program->proposedUpdate)
            @can('Add/Edit Program & Mapping')
              <a href="{{ route('programs.edit', ['bank' => request()->route('bank')->url, 'program' => $program]) }}"
                  class="btn btn-warning btn-sm">{{ __('Edit') }}</a>
            @endcan
          @endif
        </div>
    </div>
    <!-- Default -->
    <div class="card">
        <div class="card-body p-2">
            <div class="row">
                <div class="col-sm-4 text-align-center d-flex justify-content-between">
                    <h6 class="mr-2 fw-light">{{ __('Anchor')}}:</h6>
                    @can('View Companies')
                        <a href="{{ route('companies.show', ['bank' => $bank, 'company' => $program->anchor->id]) }}"
                            class="fw-bold text-decoration-underline text-right text-nowrap">{{ $program->anchor->name }}</a>
                    @else
                        <span
                            class="fw-bold text-decoration-underline text-right text-nowrap">{{ $program->anchor->name }}</span>
                    @endcan
                </div>
                <div class="col-sm-4 text-align-center d-flex justify-content-between">
                    <h6 class="mr-2 fw-light">{{ __('Program Type')}}:</h6>
                    <h6 class="px-2 text-right">{{ $program->programType->name }}</h6>
                </div>
                @if ($program->programCode)
                    <div class="col-sm-4 text-align-center d-flex justify-content-between">
                        <h6 class="mr-2 fw-light">{{ __('Program Code')}}:</h6>
                        <h6 class="px-2 text-right text-nowrap">
                            {{ $program->programCode->name }}({{ $program->programCode->abbrev }})</h6>
                    </div>
                @endif
                <div class="col-sm-4 text-align-center d-flex justify-content-between">
                    <h6 class="mr-2 fw-light">{{ __('Code')}}:</h6>
                    <h6 class="px-2 text-right">{{ $program->code }}</h6>
                </div>
                <div class="col-sm-4 text-align-center d-flex justify-content-between">
                    <h6 class="mr-2 fw-light">{{ __('Eligibility')}}:</h6>
                    <h6 class="px-2 text-right text-success">{{ $program->eligibility }}%</h6>
                </div>
                <div class="col-sm-4 text-align-center d-flex justify-content-between d-none">
                    <h6 class="mr-2 fw-light">{{ __('Invoice Margin')}}:</h6>
                    <h6 class="px-2 text-right">{{ $program->invoice_margin }}</h6>
                </div>
                <div class="col-sm-4 text-align-center d-flex justify-content-between">
                    <h6 class="mr-2 fw-light">{{ __('Maximum Limit Per Account')}}:</h6>
                    <h6 class="px-2 text-right text-success">{{ number_format($program->max_limit_per_account) }}</h6>
                </div>
                @if ($program->collection_account)
                    <div class="col-sm-4 text-align-center d-flex justify-content-between">
                        <h6 class="mr-2 fw-light">{{ __('Collection Account')}}:</h6>
                        <h6 class="px-2 text-right">{{ $program->collection_account }}</h6>
                    </div>
                @endif
                @if ($program->factoring_payment_account)
                    <div class="col-sm-4 text-align-center d-flex justify-content-between">
                        <h6 class="mr-2 fw-light">{{ __('Factoring Payment Account')}}:</h6>
                        <h6 class="px-2 text-right">{{ $program->factoring_payment_account }}</h6>
                    </div>
                @endif
                <div class="col-sm-4 text-align-center d-flex justify-content-between">
                    <h6 class="mr-2 fw-light">{{ __('Request Auto Finance')}}:</h6>
                    <h6 class="px-2 text-right">{{ $program->request_auto_finance ? 'Yes' : 'No' }}</h6>
                </div>
                @if ($program->stale_invoice_period)
                    <div class="col-sm-4 text-align-center d-flex justify-content-between">
                        <h6 class="mr-2 fw-light">{{ __('Stale Invoice Period')}}:</h6>
                        <h6 class="px-2 text-right">{{ $program->stale_invoice_period }}</h6>
                    </div>
                @endif
                @if ($program->programType->name == 'Vendor Financing')
                    <div class="col-sm-4 text-align-center d-flex justify-content-between">
                        <h6 class="mr-2 fw-light">{{ __('Minimum Financing Days')}}:</h6>
                        <h6 class="px-2 text-right">{{ $program->min_financing_days }}</h6>
                    </div>
                @endif
                @if ($program->programType->name == 'Vendor Financing')
                    <div class="col-sm-4 text-align-center d-flex justify-content-between">
                        <h6 class="mr-2 fw-light">{{ __('Maximum Financing Days')}}:</h6>
                        <h6 class="px-2 text-right">{{ $program->max_financing_days }}</h6>
                    </div>
                @endif
                @if ($program->discountDetails->count() > 0 && $program->discountDetails->first()->discount_type)
                    <div class="col-sm-4 text-align-center d-flex justify-content-between">
                        <h6 class="mr-2 fw-light">{{ __('Discount Type')}}:</h6>
                        <h6 class="px-2 text-right">{{ $program->discountDetails->first()->discount_type }}</h6>
                    </div>
                @else
                  <div class="col-sm-4 text-align-center d-flex justify-content-between">
                      <h6 class="mr-2 fw-light">{{ __('Discount Type')}}:</h6>
                      <h6 class="px-2 text-right">{{ __('Rear Ended')}}</h6>
                  </div>
                @endif
                @if ($program->discountDetails->count() > 0 && $program->discountDetails->first()->fee_type)
                    <div class="col-sm-4 text-align-center d-flex justify-content-between">
                        <h6 class="mr-2 fw-light">{{ __('Fee Type')}}:</h6>
                        <h6 class="px-2 text-right">{{ $program->discountDetails->first()->fee_type }}</h6>
                    </div>
                @else
                  <div class="col-sm-4 text-align-center d-flex justify-content-between">
                      <h6 class="mr-2 fw-light">{{ __('Fee Type')}}:</h6>
                      <h6 class="px-2 text-right">{{ __('Rear Ended')}}</h6>
                  </div>
                @endif
                @if ($program->discountDetails->count() > 0 && $program->discountDetails->first()->penal_discount_on_principle)
                  <div class="col-sm-4 text-align-center d-flex justify-content-between">
                      <h6 class="mr-2 fw-light">{{ __('Penal Discount on Principle')}}:</h6>
                      <h6 class="px-2 text-right">{{ $program->discountDetails->first()->penal_discount_on_principle }}%</h6>
                  </div>
                @endif
                @if ($program->discountDetails->count() > 0 && $program->discountDetails->first()->grace_period)
                  <div class="col-sm-4 text-align-center d-flex justify-content-between">
                      <h6 class="mr-2 fw-light">{{ __('Grace Period')}}:</h6>
                      <h6 class="px-2 text-right">{{ $program->discountDetails->first()->grace_period }} {{ __('Days') }}</h6>
                  </div>
                @endif
                @if ($program->discountDetails->count() > 0 && $program->discountDetails->first()->grace_period_discount)
                  <div class="col-sm-4 text-align-center d-flex justify-content-between">
                      <h6 class="mr-2 fw-light">{{ __('Grace Period Discount')}}:</h6>
                      <h6 class="px-2 text-right">{{ $program->discountDetails->first()->grace_period_discount }}%</h6>
                  </div>
                @endif
                @if ($program->segment)
                    <div class="col-sm-4 text-align-center d-flex justify-content-between">
                        <h6 class="mr-2 fw-light">{{ __('Segment')}}:</h6>
                        <h6 class="px-2 text-right">{{ $program->segment }}</h6>
                    </div>
                @endif
                @if ($program->discountDetails->first()?->tax_on_discount)
                    <div class="col-sm-4 text-align-center d-flex justify-content-between">
                        <h6 class="mr-2 fw-light">{{ __('Tax on Discount')}}:</h6>
                        <h6 class="px-2 text-right">{{ $program->discountDetails?->first()?->tax_on_discount }}%</h6>
                    </div>
                @endif
                <div class="col-sm-4 text-align-center d-flex justify-content-between">
                    <h6 class="mr-2 fw-light">{{ __('Auto Debit Anchor for Financed Invoices')}}:</h6>
                    <h6 class="px-2 text-right">{{ $program->auto_debit_anchor_financed_invoices ? 'Yes' : 'No' }}</h6>
                </div>
                <div class="col-sm-4 text-align-center d-flex justify-content-between">
                    <h6 class="mr-2 fw-light">{{ __('Auto Debit Anchor For Non-financed Invoices')}}:</h6>
                    <h6 class="px-2 text-right">{{ $program->auto_debit_anchor_non_financed_invoices ? 'Yes' : 'No' }}
                    </h6>
                </div>
                <div class="col-sm-4 text-align-center d-flex justify-content-between">
                    <h6 class="mr-2 fw-light">{{ __('Maximum Days for due date extension:')}}</h6>
                    <h6 class="px-2 text-right">{{ $program->max_days_due_date_extension }}</h6>
                </div>
                <div class="col-sm-4 text-align-center d-flex justify-content-between">
                    <h6 class="mr-2 fw-light">{{ __('Days Limit for Due date change')}}:</h6>
                    <h6 class="px-2 text-right">{{ $program->days_limit_for_due_date_change }}</h6>
                </div>
                <div class="col-sm-4 text-align-center d-flex justify-content-between">
                    <h6 class="mr-2 fw-light">{{ __('Default Payment Terms')}}:</h6>
                    <h6 class="px-2 text-right">{{ $program->default_payment_terms }}</h6>
                </div>
                <div class="col-sm-4 text-align-center d-flex justify-content-between">
                    <h6 class="mr-2 fw-light">{{ __('Anchor can change Payment Term')}}:</h6>
                    <h6 class="px-2 text-right">{{ $program->anchor_can_change_payment_term ? 'Yes' : 'No' }}</h6>
                </div>
                @if ($program->repayment_appropriation)
                    <div class="col-sm-4 text-align-center d-flex justify-content-between">
                        <h6 class="mr-2 fw-light">{{ __('Repayment Appropriation')}}:</h6>
                        <h6 class="px-2 text-right">{{ $program->repayment_appropriation }}</h6>
                    </div>
                @endif
                <div class="col-sm-4 text-align-center d-flex justify-content-between">
                    <h6 class="mr-2 fw-light">{{ __('Mandatory Invoice Attachment')}}:</h6>
                    <h6 class="px-2 text-right">{{ $program->mandatory_invoice_attachment ? 'Yes' : 'No' }}</h6>
                </div>
                <div class="col-sm-4 text-align-center d-flex justify-content-between">
                  <h6 class="mr-2 fw-light">{{ __('Maturity Handling on Holidays')}}:</h6>
                  <h6 class="px-2 text-right">{{ $program->discountDetails->first()?->maturity_handling_on_holidays }}</h6>
              </div>
                @if ($program->partner)
                    <div class="col-sm-4 text-align-center d-flex justify-content-between">
                        <h6 class="mr-2 fw-light">{{ __('Partner')}}:</h6>
                        <h6 class="px-2 text-right">{{ $program->partner }}</h6>
                    </div>
                @endif
                @if ($program->recourse)
                  <div class="col-sm-4 text-align-center d-flex justify-content-between">
                      <h6 class="mr-2 fw-light">{{ __('Recourse')}}:</h6>
                      <h6 class="px-2 text-right">{{ $program->recourse }}</h6>
                  </div>
                @endif
                @if ($program->due_date_calculated_from)
                  <div class="col-sm-4 text-align-center d-flex justify-content-between">
                      <h6 class="mr-2 fw-light">{{ __('Due Date Calculated From')}}:</h6>
                      <h6 class="px-2 text-right text-nowrap">{{ $program->due_date_calculated_from }}</h6>
                  </div>
                @endif
                @if ($program->noa)
                    <div class="col-sm-4 text-align-center d-flex justify-content-between">
                        <h6 class="mr-2 fw-light">{{ __('NOA')}}:</h6>
                        <h6 class="px-2 text-right">{{ $program->noa }}</h6>
                    </div>
                @endif
                <div class="col-sm-4 text-align-center d-flex justify-content-between">
                  <h6 class="mr-2 fw-light">{{ __('Limit Expiry Date')}}:</h6>
                  <h6 class="px-2 text-right">{{ Carbon\Carbon::parse($program->limit_expiry_date)->format('d M Y') }}</h6>
                </div>
                <div class="col-sm-4 text-align-center d-flex justify-content-between">
                    <h6 class="mr-2 fw-light">{{ __('Account Status')}}:</h6>
                    <h6 class="px-2 text-right">{{ Str::title($program->account_status) }}</h6>
                </div>
            </div>
        </div>
    </div>
    <div class="card mt-2 p-2">
      <div class="row">
        <div class="col-sm-3 text-align-center d-flex">
          <h6 class="mr-2 fw-light my-auto">{{ __('Program Limit')}}:</h6>
          <h6 class="px-2 text-right ml-4 my-auto text-success">{{ number_format($program->program_limit, 2) }}</h6>
        </div>
        <div class="col-sm-3 text-align-center d-flex">
          <h6 class="mr-2 fw-light my-auto">{{ __('Utilized Amount')}}:</h6>
          <h6 class="px-2 text-right ml-4 my-auto text-success">{{ number_format($program->utilized_amount, 2) }}</h6>
        </div>
        <div class="col-sm-3 text-align-center d-flex">
          <h6 class="mr-2 fw-light my-auto">{{ __('Pipeline Amount')}}:</h6>
          <h6 class="px-2 text-right ml-4 my-auto text-success">{{ number_format($program->pipeline_amount, 2) }}</h6>
        </div>
        <div class="col-sm-3 text-align-center d-flex">
          <h6 class="mr-2 fw-light my-auto">{{ __('Available Limit')}}:</h6>
          <h6 class="px-2 text-right ml-4 my-auto text-success">{{ number_format($program->program_limit - ($program->utilized_amount + $program->pipeline_amount), 2) }}</h6>
        </div>
      </div>
    </div>
    @if ($program->programType->name == 'Vendor Financing')
        <div class="card mt-2">
            <div class="card-header p-2">
                <h6>{{ __('Discounts')}}</h6>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('Benchmark Rate')}}</th>
                                <th>{{ __('Business Strategy Spread')}}</th>
                                <th>{{ __('Credit Spread')}}</th>
                                <th>{{ __('Total Spread')}}</th>
                                <th>{{ __('Total ROI')}}</th>
                                <th>{{ __('Anchor Discount Bearing')}}</th>
                                <th>{{ __('Vendor Discount Bearing')}}</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0 text-nowrap">
                            @foreach ($program->discountDetails as $discount_details)
                                <tr>
                                    <td>{{ $discount_details->benchmark_rate }}%</td>
                                    <td class="text-success">{{ $discount_details->business_strategy_spread }}%</td>
                                    <td class="text-success">{{ $discount_details->credit_spread }}%</td>
                                    <td class="text-success">{{ $discount_details->total_spread }}%</td>
                                    <td class="text-success">{{ $discount_details->total_roi }}%</td>
                                    <td class="text-success">{{ $discount_details->anchor_discount_bearing }}%</td>
                                    <td class="text-success">{{ $discount_details->vendor_discount_bearing }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="card mt-2">
            <div class="card-header p-2">
                <h6>{{ __('Discounts')}}</h6>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('From Day')}}</th>
                                <th>{{ __('To Day')}}</th>
                                <th>{{ __('Benchmark Rate')}}</th>
                                <th>{{ __('Business Strategy Spread')}}</th>
                                <th>{{ __('Credit Spread')}}</th>
                                <th>{{ __('Total Spread')}}</th>
                                <th>{{ __('Total ROI')}}</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0 text-nowrap">
                            @foreach ($program->dealerDiscountRates as $discount_details)
                                <tr>
                                    <td>{{ $discount_details->from_day }}</td>
                                    <td>{{ $discount_details->to_day }}</td>
                                    <td class="text-success">{{ $program->discountDetails->first()->benchmark_rate }}%</td>
                                    <td class="text-success">{{ $discount_details->business_strategy_spread }}%</td>
                                    <td class="text-success">{{ $discount_details->credit_spread }}%</td>
                                    <td class="text-success">{{ $discount_details->total_spread }}%</td>
                                    <td class="text-success">{{ $discount_details->total_roi }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
    @if ($program->programType->name == 'Vendor Financing')
        <div class="card mt-2">
            <div class="card-header p-2">
                <h6>{{ __('Fees')}}</h6>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('Name')}}</th>
                                <th>{{ __('Type')}}</th>
                                <th>{{ __('Value')}}</th>
                                <th>{{ __('Anchor Bearing')}}</th>
                                <th>{{ __('Vendor Bearing')}}</th>
                                <th>{{ __('Charge Type') }}</th>
                                <th>{{ __('Credited To') }}</th>
                                <th>{{ __('Taxes')}}</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0 text-nowrap">
                            @foreach ($program->fees as $fee)
                                <tr>
                                    <td>{{ $fee->fee_name }}</td>
                                    <td>{{ Str::title($fee->type) }}</td>
                                    <td class="text-success">{{ $fee->value }} @if ($fee->per_amount)
                                            (per {{ $fee->per_amount }})
                                        @endif
                                    </td>
                                    <td class="text-success">{{ $fee->anchor_bearing_discount }}</td>
                                    <td class="text-success">{{ $fee->vendor_bearing_discount }}</td>
                                    <td>
                                      @if ($fee->charge_type === 'daily')
                                        <span title="Daily">{{ __('Per Day') }}</span>
                                      @else
                                        <span>
                                          {{ Str::title($fee->charge_type) }}
                                        </span>
                                      @endif
                                    </td>
                                    <td>{{ $fee->account_number }}</td>
                                    <td class="text-success">{{ $fee->taxes }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="card mt-2">
            <div class="card-header p-2">
                <h6>{{ __('Fees')}}</h6>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('Name')}}</th>
                                <th>{{ __('Type')}}</th>
                                <th>{{ __('Value')}}</th>
                                <th>{{ __('Dealer Bearing')}}</th>
                                <th>{{ __('Charge Type')}}</th>
                                <th>{{ __('Credited To') }}</th>
                                <th>{{ __('Taxes')}}</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0 text-nowrap">
                            @foreach ($program->fees as $fee)
                                <tr>
                                    <td>{{ $fee->fee_name }}</td>
                                    <td>{{ Str::title($fee->type) }}</td>
                                    <td class="text-success">{{ $fee->value }}</td>
                                    <td class="text-success">{{ $fee->dealer_bearing }}</td>
                                    <td>
                                      @if ($fee->charge_type === 'daily')
                                        <span title="Daily">{{ __('PD') }}</span>
                                      @else
                                        <span>
                                          {{ Str::title($fee->charge_type) }}
                                        </span>
                                      @endif
                                    </td>
                                    <td>{{ $fee->account_number }}</td>
                                    <td class="text-success">{{ $fee->taxes ? $fee->taxes . '%' : '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
    <hr>
    <div class="card mt-2">
      <div class="card-header p-2">
          <h6>{{ __('Email & Mobile Details')}}</h6>
      </div>
      <div class="card-body p-2">
          <div class="table-responsive">
              <table class="table">
                  <thead>
                      <tr>
                          <th>{{ __('Bank User Name')}}</th>
                          <th>{{ __('Bank User Email')}}</th>
                          <th>{{ __('Bank User Mobile')}}</th>
                      </tr>
                  </thead>
                  <tbody class="table-border-bottom-0 text-nowrap">
                      @foreach ($program->bankUserDetails as $bank_user_details)
                          <tr>
                              <td>{{ $bank_user_details->name }}</td>
                              <td>{{ $bank_user_details->email }}</td>
                              <td>{{ $bank_user_details->phone_number }}</td>
                          </tr>
                      @endforeach
                  </tbody>
              </table>
          </div>
      </div>
  </div>
  <hr>
    <div class="card mt-2">
      <div class="card-header p-2">
          <h6>{{ __('Bank Details')}}</h6>
      </div>
      <div class="card-body p-2">
          <div class="table-responsive">
              <table class="table">
                  <thead>
                      <tr>
                          <th>{{ __('Account Name')}}</th>
                          <th>{{ __('Account Number')}}</th>
                          <th>{{ __('Bank Name')}}</th>
                          <th>{{ __('Branch')}}</th>
                          <th>{{ __('Swift Code')}}</th>
                          <th>{{ __('Account Type')}}</th>
                      </tr>
                  </thead>
                  <tbody class="table-border-bottom-0 text-nowrap">
                      @foreach ($program->bankDetails as $bank_details)
                          <tr>
                              <td>{{ $bank_details->name_as_per_bank }}</td>
                              <td>{{ $bank_details->account_number }}</td>
                              <td>{{ $bank_details->bank_name }}</td>
                              <td>{{ $bank_details->branch }}</td>
                              <td>{{ $bank_details->swift_code }}</td>
                              <td>{{ $bank_details->account_type }}</td>
                          </tr>
                      @endforeach
                  </tbody>
              </table>
          </div>
      </div>
  </div>
@endsection
