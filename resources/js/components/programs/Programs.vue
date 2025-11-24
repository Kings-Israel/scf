<template>
  <div class="p-2 d-flex flex-column flex-md-row justify-content-between">
    <div class="d-flex flex-wrap gap-1">
      <div class="">
        <label for="Name" class="form-label">{{ $t('Program Name') }}</label>
        <input
          v-on:keyup.enter="filter"
          type="text"
          class="form-control form-search"
          id="defaultFormControlInput"
          v-model="program_name_search"
          :placeholder="$t('Program Name')"
          aria-describedby="defaultFormControlHelp"
        />
      </div>
      <div class="">
        <label for="Anchor" class="form-label">{{ $t('Anchor') }}</label>
        <input
          v-on:keyup.enter="filter"
          type="text"
          class="form-control form-search"
          id="defaultFormControlInput"
          v-model="anchor_search"
          :placeholder="$t('Anchor')"
          aria-describedby="defaultFormControlHelp"
        />
      </div>
      <div class="">
        <label for="Status" class="form-label">{{ $t('Status') }}</label>
        <select class="form-select form-search" v-model="status_search">
          <option value="">{{ $t('Status') }}</option>
          <option value="pending">{{ $t('Pending') }}</option>
          <option value="active">{{ $t('Active') }}</option>
          <option value="suspended">{{ $t('Suspended') }}</option>
        </select>
      </div>
      <div class="">
        <label for="Program Type" class="form-label">{{ $t('Program Type') }}</label>
        <select class="form-select form-search" v-model="type_search">
          <option value="">{{ $t('Program Type') }}</option>
          <option value="vendor_financing_receivable">{{ $t('Vendor Financing Receivable') }}</option>
          <option value="factoring_with_recourse">{{ $t('Factoring With Recourse') }}</option>
          <option value="factoring_without_recourse">{{ $t('Factoring Without Recourse') }}</option>
          <option value="dealer_financing">{{ $t('Dealer Financing') }}</option>
        </select>
      </div>
      <div class="">
        <label for="Bulk Actions" class="form-label">{{ $t('Bulk Actions') }}</label>
        <select class="form-select form-search" id="exampleFormControlSelect" v-model="bulk_action">
          <option value="">{{ $t('Bulk Actions') }}</option>
          <option value="approve">{{ $t('Approve') }}</option>
        </select>
      </div>
      <button
        class="d-none"
        ref="showApprovalModal"
        data-bs-toggle="modal"
        :data-bs-target="'#bulk-approval-modal'"
      ></button>
      <div class="modal fade" id="bulk-approval-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="modalCenterTitle">{{ $t('Approve Programs') }}</h5>
              <button
                type="button"
                class="btn-close"
                id="approve-programs-close"
                data-bs-dismiss="modal"
                aria-label="Close"
              ></button>
            </div>
            <form method="post" @submit.prevent="submitBulkAction('approve')">
              <div class="modal-body">
                <h5>{{ $t('Are you sure you want to approve the selected programs?') }}</h5>
              </div>
              <div class="modal-footer">
                <button class="btn btn-primary" type="submit">{{ $t('Submit') }}</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="table-search-btn">
        <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
      </div>
      <div class="table-clear-btn">
        <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
      </div>
    </div>
    <div class="d-flex justify-content-end gap-1 mt-2 mt-md-auto">
      <div class="">
        <select class="form-select" v-model="per_page" style="width: 5rem">
          <option value="10">10</option>
          <option value="20">20</option>
          <option value="50">50</option>
          <option value="100">50</option>
        </select>
      </div>
      <button type="button" class="btn btn-primary btn-md" @click="exportPrograms">
        <i class="ti ti-download ti-xs"></i> {{ $t('Excel') }}
      </button>
      <a v-if="can_add" :href="'programs/create'" class="btn btn-primary btn-md text-nowrap">
        {{ $t('Add Program') }}
      </a>
    </div>
  </div>
  <pagination
    class="px-2"
    v-if="programs.meta"
    :from="programs.meta.from"
    :to="programs.meta.to"
    :links="programs.meta.links"
    :next_page="programs.links.next"
    :prev_page="programs.links.prev"
    :total_items="programs.meta.total"
    :first_page_url="programs.links.first"
    :last_page_url="programs.links.last"
    @change-page="changePage"
  ></pagination>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr class="">
          <th>
            <div class="form-check">
              <input class="form-check-input border-primary" type="checkbox" id="select-all" @change="selectAll()" />
            </div>
          </th>
          <th>{{ $t('Program Name') }}</th>
          <th>{{ $t('Company Name') }}</th>
          <th>{{ $t('Product Type') }}</th>
          <th>{{ $t('Product Code') }}</th>
          <th>{{ $t('Status') }}</th>
          <th>{{ $t('Total Program Limit') }}</th>
          <th>{{ $t('Utilized Limit') }}</th>
          <th>{{ $t('Pipeline Amount') }}</th>
          <th>{{ $t('Actions') }}</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        <tr v-if="!programs.data">
          <td colspan="12" class="text-center">
            <span class="text-center">{{ $t('Loading Data') }}...</span>
          </td>
        </tr>
        <tr v-if="programs.data && programs.data.length <= 0">
          <td colspan="12" class="text-center">
            <span class="text-center">{{ $t('No Data Available') }}...</span>
          </td>
        </tr>
        <tr class="text-nowrap" v-for="program in programs.data" :key="program.id">
          <td>
            <div class="form-check">
              <input
                v-if="
                  program.can_approve &&
                  (program.status === 'pending' || program.status === 'rejected') &&
                  !program.proposed_update
                "
                class="form-check-input border-primary"
                type="checkbox"
                :id="'data-select-' + program.id"
                @change="updateSelected(program.id)"
              />
            </div>
          </td>
          <td class="text-primary">
            <div class="d-flex">
              <span
                v-if="
                  program.proposed_update ||
                  program.deleted_at ||
                  program.mapping_changes_count > 0 ||
                  program.status === 'pending'
                "
              >
                <a v-if="program.can_view" :href="'programs/' + program.id + '/details'">
                  {{ program.name }}
                </a>
                <span v-else>{{ program.name }}</span>
              </span>
              <span v-else>
                <a v-if="program.can_view" :href="'programs/' + program.id + '/details'">
                  {{ program.name }}
                </a>
                <span v-else>{{ program.name }}</span>
              </span>
              <i
                class="tf-icons ti ti-info-circle ti-xs text-warning"
                :title="$t('Program Limit is almost depleted')"
                v-if="program.utilized_percentage_ratio > 90"
              ></i>
              <i
                class="tf-icons ti ti-info-circle ti-xs text-danger"
                :title="$t('Pending Program Changes awating approval')"
                v-if="program.proposed_update"
              ></i>
              <i
                class="tf-icons ti ti-info-circle ti-xs text-danger"
                :title="$t('Mapping Creation/Updation awating approval')"
                v-if="program.mapping_changes_count > 0"
              ></i>
              <i
                class="tf-icons ti ti-info-circle ti-xs text-danger"
                :title="$t('Program Creation/Updation Awaiting Approval')"
                v-if="program.status == 'pending'"
              ></i>
              <i
                class="tf-icons ti ti-info-circle ti-xs text-danger"
                :title="'Program Deletion Awaiting Approval'"
                v-if="program.deleted_at"
              ></i>
            </div>
          </td>
          <td class="text-primary text-decoration-underline">
            <a v-if="program.anchor.can_view" :href="'companies/' + program.anchor.id + '/details'">{{
              program.anchor.name
            }}</a>
            <span v-else>{{ program.anchor.name }}</span>
          </td>
          <td class="">{{ program.program_type.name }}</td>
          <td>{{ program.program_code ? program.program_code.abbrev : 'DF' }}</td>
          <td>
            <span class="badge me-1 m_title" :class="resolveProgramStatus(program.account_status)">{{
              program.account_status
            }}</span>
          </td>
          <td class="text-success">
            {{ new Intl.NumberFormat().format(program.program_limit) }}
          </td>
          <td class="text-success">
            {{ new Intl.NumberFormat().format(program.utilized.toFixed(2)) }}
          </td>
          <td class="text-success">
            {{ new Intl.NumberFormat().format(program.pipeline.toFixed(2)) }}
          </td>
          <td class="">
            <div class="d-flex gap-1">
              <a
                :href="'programs/' + program.id + '/details'"
                v-if="program.can_view"
                class=""
                :title="$t('View Program Details')"
                ><i class="ti ti-eye ti-sm text-primary"></i
              ></a>
              <a
                :href="'programs/' + program.id + '/edit'"
                v-if="
                  !program.proposed_update && !program.deleted_at && program.status == 'approved' && program.can_edit
                "
                class=""
                :title="$t('Edit Program')"
                ><i class="ti ti-pencil ti-sm text-warning"></i
              ></a>
              <i
                class="ti ti-clock ti-sm text-danger"
                v-if="
                  program.account_status == 'suspended' &&
                  program.status == 'rejected' &&
                  program.proposed_update &&
                  program.can_view
                "
                :title="$t('Program Creation/Updation awaiting approval')"
                data-bs-toggle="modal"
                :data-bs-target="'#changes-details-' + program.id"
                style="cursor: pointer; font-weight: bolder"
                @click="addVendorConfigurations(program)"
              ></i>
              <i
                class="ti ti-clock ti-sm text-danger"
                v-if="
                  program.account_status == 'active' &&
                  (program.status == 'active' || program.proposed_update) &&
                  program.can_view
                "
                :title="$t('Program Creation/Updation awaiting approval')"
                data-bs-toggle="modal"
                :data-bs-target="'#changes-details-' + program.id"
                style="cursor: pointer; font-weight: bolder"
                @click="addVendorConfigurations(program)"
              ></i>
              <i
                class="ti ti-clock ti-sm text-primary"
                v-if="program.account_status == 'suspended' && !program.proposed_update && program.can_view"
                :title="$t('Program Creation/Updation awaiting approval')"
                data-bs-toggle="modal"
                :data-bs-target="'#new-program-' + program.id"
                style="cursor: pointer; font-weight: bolder"
                @click="addVendorConfigurations(program)"
              ></i>
              <div
                class="d-flex"
                v-if="
                  !program.proposed_update &&
                  !program.deleted_at &&
                  program.status == 'approved' &&
                  program.can_edit &&
                  program.program_type.name == 'Vendor Financing'
                "
              >
                <a
                  v-if="program.program_code.name == 'Vendor Financing Receivable'"
                  :href="'programs/' + program.id + '/vendors-manage'"
                  class=""
                  :title="$t('Manage Vendors')"
                  ><i class="ti ti-users ti-sm text-info"></i
                ></a>
                <a v-else :href="'programs/' + program.id + '/vendors-manage'" class="" :title="$t('Manage Buyers')"
                  ><i class="ti ti-users ti-sm text-info"></i
                ></a>
              </div>
              <div
                class="d-flex gap-1"
                v-if="
                  !program.proposed_update &&
                  !program.deleted_at &&
                  program.status == 'approved' &&
                  program.can_edit &&
                  program.program_type.name == 'Dealer Financing'
                "
              >
                <a :href="'programs/' + program.id + '/vendors-manage'" class="" :title="$t('Manage Dealers')"
                  ><i class="ti ti-users ti-sm text-info"></i
                ></a>
              </div>
              <i
                class="ti ti-thumb-up ti-sm text-success my-auto"
                v-if="
                  !program.proposed_update &&
                  !program.deleted_at &&
                  program.status == 'approved' &&
                  program.can_activate &&
                  program.account_status == 'active' &&
                  program.utilized == 0 &&
                  program.pipeline == 0
                "
                :title="$t('Click to Suspend Program')"
                style="cursor: pointer"
                @click="updateActiveStatus(program, 'suspended')"
              ></i>
              <i
                class="ti ti-thumb-down ti-sm text-danger my-auto"
                v-if="
                  !program.proposed_update &&
                  !program.deleted_at &&
                  program.status == 'approved' &&
                  program.can_activate &&
                  program.account_status == 'suspended'
                "
                :title="$t('Click to Activate Program')"
                style="cursor: pointer"
                @click="updateActiveStatus(program, 'active')"
              ></i>
              <!-- Approve Program -->
              <i
                class="ti ti-check ti-sm text-success my-auto"
                v-if="program.can_approve && !program.deleted_at && program.status == 'pending'"
                :title="$t('Click to Approve Program')"
                style="cursor: pointer; font-weight: bolder"
                data-bs-toggle="modal"
                :data-bs-target="'#approve-program-' + program.id"
              ></i>
              <div class="modal fade" :id="'approve-program-' + program.id" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Approve Program') }}</h5>
                      <button
                        type="button"
                        class="btn-close"
                        :id="'update-approve-status-close' + program.id"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                      ></button>
                    </div>
                    <div class="modal-body">
                      <div class="row mb-1">
                        <h5 class="text-wrap">{{ $t('Are you sure you want to approve the program?') }}</h5>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button
                        class="btn btn-primary"
                        type="button"
                        @click="updateApprovalStatus(program.id, 'approved')"
                      >
                        {{ $t('Confirm') }}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <!-- End Approve Program -->
              <!-- Reject Program -->
              <i
                class="ti ti-circle-x ti-sm text-danger my-auto"
                v-if="program.can_approve && !program.deleted_at && program.status == 'pending'"
                title="Click to Reject Program"
                style="cursor: pointer; font-weight: bolder"
                data-bs-toggle="modal"
                :data-bs-target="'#reject-program-' + program.id"
              ></i>
              <div class="modal fade" :id="'reject-program-' + program.id" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Reject Program') }}</h5>
                      <button
                        type="button"
                        class="btn-close"
                        :id="'update-reject-status-close' + program.id"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                      ></button>
                    </div>
                    <div class="modal-body">
                      <div class="row mb-1">
                        <h5 class="text-wrap">{{ $t('Are you sure you want to reject the program') }}?</h5>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button
                        class="btn btn-primary"
                        type="button"
                        @click="updateApprovalStatus(program.id, 'rejected')"
                      >
                        {{ $t('Confirm') }}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <!-- End Reject Program -->
              <!-- Delete Program -->
              <i
                class="ti ti-trash ti-sm text-danger my-auto"
                v-if="program.can_delete"
                title="Click to Delete Program"
                style="cursor: pointer; font-weight: bolder"
                data-bs-toggle="modal"
                :data-bs-target="'#delete-program-' + program.id"
              ></i>
              <div class="modal fade" :id="'delete-program-' + program.id" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Delete Program') }}</h5>
                      <button
                        type="button"
                        class="btn-close"
                        :id="'delete-program-close-' + program.id"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                      ></button>
                    </div>
                    <div class="modal-body">
                      <div class="row mb-1">
                        <h5 class="text-wrap">
                          {{ $t('Are you sure you want to delete the program? This action cannot be undone.') }}
                        </h5>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">
                        {{ $t('Cancel') }}
                      </button>
                      <button
                        v-if="program.deleted_at"
                        class="btn btn-primary"
                        type="button"
                        @click="cancelDeleteProgram(program.id)"
                      >
                        {{ $t('Cancel Deletion') }}
                      </button>
                      <button class="btn btn-danger" type="button" @click="deleteProgram(program.id)">
                        {{ $t('Delete') }}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <!-- End Delete Program -->
            </div>
            <!-- Proposed Program Update -->
            <div
              v-if="program.proposed_update"
              class="modal modal-top fade modal-xl"
              :id="'changes-details-' + program.id"
              tabindex="-1"
            >
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5>{{ $t('Program Changes Details') }}</h5>
                    <button
                      type="button"
                      class="btn-close"
                      data-bs-dismiss="modal"
                      :id="'changes-details-close-' + program.id"
                      aria-label="Close"
                    ></button>
                  </div>
                  <div class="modal-body">
                    <div v-if="program.details_changes">
                      <h4 class="mb-0">{{ $t('General Details') }}</h4>
                      <div class="row" v-for="(column, key) in Object.keys(program.details_changes)" :key="column">
                        <div class="col-12" v-if="!boolean_columns.includes(column)">
                          <span
                            ><strong class="change_title">{{ column.replaceAll('_', ' ') }}:</strong></span
                          >
                          <span class="mx-2">{{ Object.values(program.details_changes)[key] }}</span>
                        </div>
                        <div class="col-12" v-if="boolean_columns.includes(column)">
                          <span
                            ><strong class="change_title">{{ column.replaceAll('_', ' ') }}:</strong></span
                          >
                          <span class="mx-2" v-if="Object.values(program.details_changes)[key] == 0">{{
                            $t('From Yes to No')
                          }}</span>
                          <span class="mx-2" v-if="Object.values(program.details_changes)[key] == 1">{{
                            $t('From No to Yes')
                          }}</span>
                        </div>
                      </div>
                      <hr />
                    </div>
                    <div v-if="program.discount_details_changes">
                      <h4 class="mb-0">{{ $t('Discount Details') }}</h4>
                      <div
                        class="row"
                        v-for="(column, key) in Object.keys(program.discount_details_changes)"
                        :key="column"
                      >
                        <div class="col-12">
                          <span
                            ><strong class="change_title">{{ column.replaceAll('_', ' ') }}:</strong></span
                          >
                          <span class="mx-2">{{ Object.values(program.discount_details_changes)[key] }}</span>
                        </div>
                      </div>
                      <hr />
                    </div>
                    <div v-if="program.fees_details_changes">
                      <h4 class="mb-0">{{ $t('Fee Details') }}</h4>
                      <template v-for="change in program.fees_details_changes" :key="change">
                        <div class="row" v-for="(column, key) in Object.keys(change)" :key="column">
                          <div class="col-12" v-if="column != 'deleted_at'">
                            <span
                              ><strong class="change_title">{{ column.replaceAll('_', ' ') }}:</strong></span
                            >
                            <span class="mx-2">{{ Object.values(change)[key] }}</span>
                          </div>
                          <div class="col-12" v-else>
                            <span class="">{{ $t('Delete Fee') }}</span>
                          </div>
                        </div>
                      </template>
                      <hr />
                    </div>
                    <div v-if="program.dealer_discount_details_rates">
                      <h4 class="mb-0">{{ $t('Dealer Discount Rates') }}</h4>
                      <div class="row" v-for="(data, key) in program.dealer_discount_details_rates" :key="key">
                        <div class="col-12" v-for="(column, details) in data" :key="column">
                          <span
                            ><strong class="change_title">{{ details.replaceAll('_', ' ') }}:</strong></span
                          >
                          <span class="mx-2">{{ column }}</span>
                        </div>
                      </div>
                      <hr />
                    </div>
                    <div v-if="program.bank_details_changes">
                      <h4 class="mb-0">{{ $t('Bank Details') }}</h4>
                      <div class="row" v-for="(data, key) in program.bank_details_changes" :key="key">
                        <div class="col-6" v-for="(column, details) in data" :key="column">
                          <div class="col-6" v-if="details != 'deleted_at'">
                            <span v-if="details != 'program_id'"
                              ><strong class="change_title">{{ details.replaceAll('_', ' ') }}:</strong></span
                            >
                            <span class="mx-2" v-if="details != 'program_id'">{{ column }}</span>
                          </div>
                          <div class="col-6" v-else>
                            <span class=""
                              ><strong>{{ $t('Delete Bank Details') }}</strong></span
                            >
                          </div>
                        </div>
                      </div>
                      <hr />
                    </div>
                    <div
                      v-if="
                        (program.fees_details_changes ||
                          program.discount_details_changes ||
                          program.dealer_discount_details_rates) &&
                        program.vendor_configurations
                      "
                    >
                      <h4 class="mb-0">{{ $t('Select Mappings where changes will apply') }}:</h4>
                      <div
                        class="row"
                        v-for="(column, key) in Object.keys(program.vendor_configurations)"
                        :key="column"
                      >
                        <div class="col-12">
                          <div class="form-check">
                            <input
                              class="form-check-input border-primary"
                              v-model="program_ids"
                              type="checkbox"
                              :value="Object.values(program.vendor_configurations)[key].id"
                            />
                            <label :for="Object.values(program.vendor_configurations)[key].id" class="form-label"
                              >{{
                                Object.values(program.vendor_configurations)[key].buyer
                                  ? Object.values(program.vendor_configurations)[key].buyer.name
                                  : Object.values(program.vendor_configurations)[key].company.name
                              }}({{ Object.values(program.vendor_configurations)[key].payment_account_number }})</label
                            >
                          </div>
                        </div>
                      </div>
                      <hr />
                    </div>
                  </div>
                  <div class="modal-footer" v-if="program.can_approve_changes">
                    <button class="btn btn-sm btn-danger" @click="approveChange(program.id, 'reject')">
                      {{ $t('Reject') }}
                    </button>
                    <button class="btn btn-sm btn-primary" @click="approveChange(program.id, 'approve')">
                      {{ $t('Approve') }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <!-- End Proposed Program Update -->
            <div class="modal modal-top fade modal-xl" :id="'new-program-' + program.id" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5>{{ $t('New Program') }}</h5>
                    <button
                      type="button"
                      class="btn-close"
                      data-bs-dismiss="modal"
                      :id="'new-program-close-' + program.id"
                      aria-label="Close"
                    ></button>
                  </div>
                  <div class="modal-body">
                    <span>{{ $t('New Program Awaiting Approval') }}</span>
                  </div>
                  <div class="modal-footer">
                    <!-- Approve Program -->
                    <button
                      class="btn btn-sm btn-success mx-1 my-auto"
                      v-if="
                        program.can_approve &&
                        !program.deleted_at &&
                        (program.status == 'pending' || program.status == 'rejected')
                      "
                      :title="$t('Click to Approve Program')"
                      style="cursor: pointer"
                      data-bs-toggle="modal"
                      :data-bs-target="'#approve-program-' + program.id"
                    >
                      {{ $t('Approve') }}
                    </button>
                    <!-- Reject Program -->
                    <button
                      class="btn btn-sm btn-danger mx-1 my-auto"
                      v-if="
                        program.can_approve &&
                        !program.deleted_at &&
                        (program.status == 'pending' || program.status == 'rejected')
                      "
                      title="Click to Reject Program"
                      style="cursor: pointer"
                      data-bs-toggle="modal"
                      :data-bs-target="'#reject-program-' + program.id"
                    >
                      {{ $t('Reject') }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  <pagination
    class="px-2"
    v-if="programs.meta"
    :from="programs.meta.from"
    :to="programs.meta.to"
    :links="programs.meta.links"
    :next_page="programs.links.next"
    :prev_page="programs.links.prev"
    :total_items="programs.meta.total"
    :first_page_url="programs.links.first"
    :last_page_url="programs.links.last"
    @change-page="changePage"
  ></pagination>
</template>

<script>
import { useToast } from 'vue-toastification';
import { inject, onMounted, ref, watch } from 'vue';
import moment from 'moment';
import axios from 'axios';
import Pagination from '../partials/Pagination.vue';

export default {
  name: 'Programs',
  props: ['bank', 'can_add'],
  components: {
    Pagination
  },
  setup(props) {
    const base_url = inject('baseURL');
    const toast = useToast();
    const programs = ref([]);
    const can_add = props.can_add;

    const approvaPrograms = ref([]);

    const showRejectionModal = ref(null);
    const showApprovalModal = ref(null);

    // Search fields
    const program_name_search = ref('');
    const anchor_search = ref('');
    const status_search = ref('');
    const type_search = ref('');

    const selected_data = ref([]);

    const bulk_action = ref('');

    const program_ids = ref([]);

    const boolean_columns = ref([
      'request_auto_finance',
      'auto_debit_anchor_for_financed_invoices',
      'auto_debit_anchor_for_non_financed_invoices',
      'anchor_can_change_due_date',
      'anchor_can_change_payment_term',
      'mandatory_invoice_attachement'
    ]);

    // Pagination
    const per_page = ref(50);

    const getPrograms = async () => {
      await axios
        .get('programs/data', {
          params: {
            per_page: per_page.value,
            name: program_name_search.value,
            anchor: anchor_search.value,
            status: status_search.value,
            type: type_search.value
          }
        })
        .then(response => {
          programs.value = response.data.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    onMounted(() => {
      getPrograms();
    });

    const selectAll = () => {
      selected_data.value = [];
      if (!document.getElementById('select-all').checked) {
        programs.value.data.forEach(data => {
          if (data.can_activate && data.account_status == 'suspended') {
            document.getElementById('data-select-' + data.id).checked = false;
            let f;
            let index = selected_data.value.filter(function (id, index) {
              f = index;
              id == data.id;
            });
            if (!index) {
              return false;
            }
            selected_data.value.splice(f, 1);
          }
        });
      } else {
        programs.value.data.forEach(data => {
          if (data.can_activate && data.account_status == 'suspended') {
            document.getElementById('data-select-' + data.id).checked = true;
            selected_data.value.push(data.id);
          }
        });
      }
    };

    const updateSelected = id => {
      if (selected_data.value.includes(id)) {
        const index = selected_data.value.indexOf(id);
        selected_data.value.splice(index, 1);
      } else {
        selected_data.value.push(id);
      }
    };

    const submitBulkAction = action => {
      if (selected_data.value.length <= 0) {
        toast.error('Select programs');
        return;
      }

      const formData = new FormData();
      if (action == 'approve') {
        selected_data.value.forEach(request => {
          formData.append('programs[]', request);
        });
        formData.append('status', 'active');
      }

      axios
        .post(base_url + props.bank + '/programs/update/status', formData)
        .then(() => {
          toast.success('Programs updated');
          bulk_action.value = '';
          getPrograms();
          document.getElementById('approve-programs-close').click();
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    const deleteProgram = program => {
      axios
        .delete(base_url + props.bank + '/programs/' + program + '/delete')
        .then(res => {
          toast.success(res.data.message);
          document.getElementById('delete-program-close-' + program).click();
          getPrograms();
        })
        .catch(err => {
          toast.error(err.response.data.message);
        });
    };

    const cancelDeleteProgram = program => {
      axios
        .get(base_url + props.bank + '/programs/' + program + '/delete/cancel')
        .then(res => {
          toast.success(res.data.message);
          document.getElementById('delete-program-close-' + program).click();
          getPrograms();
        })
        .catch(err => {
          toast.error(err.response.data.message);
        });
    };

    const resolveProgramStatus = status => {
      switch (status) {
        case 'active':
          return 'bg-label-success';
          break;
        case 'pending':
          return 'bg-label-primary';
          break;
        case 'suspended':
          return 'bg-label-danger';
          break;
        default:
          break;
      }
    };

    const NumFormatter = data => {
      return parseFloat(data).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    };

    const updateActiveStatus = (program, status) => {
      // Make sure the programs expiry date is ahead of today's date
      if (status == 'active' && moment().isAfter(program.limit_expiry_date)) {
        toast.error('Update the limit expiry date to activate program');
        return;
      }
      axios
        .get(`programs/${program.id}/update/status/${status}`)
        .then(() => {
          getPrograms();
          toast.success('Program status updated');
        })
        .catch(err => {
          toast.error(err.response.data.message);
          console.log(err);
        });
    };

    const updateApprovalStatus = (program, status) => {
      axios
        .get(`programs/${program}/status/${status}/update`)
        .then(() => {
          getPrograms();
          toast.success('Program approval status updated');
          document.getElementById('update-approve-status-close' + program).click();
          document.getElementById('update-reject-status-close' + program).click();
        })
        .catch(err => {
          toast.error(err.response.data.message);
          console.log(err);
        });
    };

    watch(bulk_action, newVal => {
      if (selected_data.value.length > 0) {
        approvaPrograms.value = [];
        if (newVal == 'approve') {
          selected_data.value.forEach(selected => {
            approvaPrograms.value.push(programs.value.data.filter(request => request.id == selected)[0]);
          });
          showApprovalModal.value.click();
        }
      } else {
        toast.error('Select Programs');
      }
    });

    const filter = async () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      await axios
        .get('programs/data', {
          params: {
            per_page: per_page.value,
            name: program_name_search.value,
            anchor: anchor_search.value,
            status: status_search.value,
            type: type_search.value
          }
        })
        .then(response => {
          programs.value = response.data.data;
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = async () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      program_name_search.value = '';
      anchor_search.value = '';
      status_search.value = '';
      type_search.value = '';
      await axios
        .get('programs/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          programs.value = response.data.data;
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            per_page: per_page.value,
            name: program_name_search.value,
            anchor: anchor_search.value,
            status: status_search.value,
            type: type_search.value
          }
        })
        .then(response => {
          programs.value = response.data.data;
        });
    };

    const approveChange = (program, status) => {
      axios
        .post('programs/' + program + '/updates/' + status + '/approve', {
          program_id: program_ids.value
        })
        .then(() => {
          if (status == 'approve') {
            toast.success('Program changes have been applied');
          } else {
            toast.success('Program changes have been discarded');
          }
          document.getElementById('changes-details-close-' + program).click();
          getPrograms();
        });
    };

    const addVendorConfigurations = program => {
      program_ids.value = [];
      Object.values(program.vendor_configurations).forEach(vendor_configuration => {
        program_ids.value.push(vendor_configuration.id);
      });
    };

    const exportPrograms = () => {
      axios
        .get('programs/data/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            name: program_name_search.value,
            anchor: anchor_search.value,
            status: status_search.value,
            type: type_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Programs_${moment().format('Do_MMM_YYYY')}.csv`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    return {
      can_add,
      moment,
      programs,
      // Search fields
      program_name_search,
      anchor_search,
      status_search,
      type_search,

      // Pagination
      per_page,

      selected_data,

      selectAll,
      updateSelected,

      deleteProgram,

      submitBulkAction,

      filter,
      refresh,

      bulk_action,

      showRejectionModal,
      showApprovalModal,

      program_ids,

      boolean_columns,

      resolveProgramStatus,
      NumFormatter,
      updateActiveStatus,
      updateApprovalStatus,
      changePage,
      approveChange,
      addVendorConfigurations,
      cancelDeleteProgram,
      exportPrograms
    };
  }
};
</script>
<style scoped>
.change_title {
  text-transform: capitalize;
}
</style>
