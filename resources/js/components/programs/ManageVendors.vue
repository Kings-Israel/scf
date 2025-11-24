<template>
  <div class="card p-2" id="companies">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="form-group">
          <label for="Name" class="form-label">{{ $t('Company Name') }}</label>
          <input
            type="text"
            class="form-control form-search"
            v-model="name_search"
            id="defaultFormControlInput"
            placeholder="Company Name"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
        <div class="form-group">
          <label for="Bulk Actions" class="form-label">{{ $t('Bulk Action') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect" v-model="bulk_action">
            <option value="">{{ $t('Bulk Actions') }}</option>
            <option value="active">{{ $t('Approve') }}</option>
            <option value="inactive">{{ $t('Reject') }}</option>
          </select>
        </div>
        <div class="table-search-btn">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="table-clear-btn">
          <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
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
                <h6 class="modal-title" id="modalCenterTitle">{{ $t('Approve Mappings') }}</h6>
                <button
                  type="button"
                  class="btn-close"
                  id="close-activate-modal"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                ></button>
              </div>
              <form method="post" @submit.prevent="submitBulkApproval('approve')">
                <div class="modal-body">
                  <h4>{{ $t('Are you sure you want to approve these companies in the program') }}?</h4>
                </div>
                <div class="modal-footer">
                  <button class="btn btn-primary" type="submit">{{ $t('Confirm') }}</button>
                </div>
              </form>
            </div>
          </div>
        </div>
        <button
          class="d-none"
          ref="showRejectionModal"
          data-bs-toggle="modal"
          :data-bs-target="'#bulk-rejection-modal'"
        ></button>
        <div class="modal fade" id="bulk-rejection-modal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-md" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h6 class="modal-title" id="modalCenterTitle">{{ $t('Reject Mappings') }}</h6>
                <button
                  type="button"
                  class="btn-close"
                  id="close-deactivate-modal"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                ></button>
              </div>
              <form method="post" @submit.prevent="submitBulkApproval('reject')">
                <div class="modal-body">
                  <h4>{{ $t('Are you sure you want to reject these mappings in the program') }}?</h4>
                  <textarea
                    name="rejection_reason"
                    v-model="rejection_reason"
                    class="form-control"
                    placeholder="Enter Rejection Reason"
                    rows="5"
                    id=""
                  ></textarea>
                </div>
                <div class="modal-footer">
                  <button class="btn btn-primary" type="submit">{{ $t('Confirm') }}</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      <div class="d-flex justify-content-end mt-2 mt-md-auto gap-1">
        <div class="">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page" style="height: fit-content">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <button type="button" class="btn btn-primary btn-md" @click="exportData">
          <i class="ti ti-download ti-xs"></i> {{ $t('Excel') }}
        </button>
      </div>
    </div>
    <pagination
      :from="companies.from"
      :to="companies.to"
      :links="companies.links"
      :next_page="companies.next_page_url"
      :prev_page="companies.prev_page_url"
      :total_items="companies.total"
      :first_page_url="companies.first_page_url"
      :last_page_url="companies.last_page_url"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th class="px-1 py-auto">
              <div class="form-check">
                <input class="form-check-input border-primary" type="checkbox" id="select-all" @change="selectAll()" />
              </div>
            </th>
            <th>{{ vendors_type == 'buyers' ? $t('Anchor') : $t('Vendor') }}</th>
            <th>{{ $t('Payment/OD Account') }}</th>
            <th>{{ $t('Sanctioned Limit') }}</th>
            <th>{{ $t('Utilized Amount') }}</th>
            <th>{{ $t('Pipeline Requests') }}</th>
            <th>{{ $t('Available Limit') }}</th>
            <th>{{ $t('Status') }}</th>
            <th v-if="manage">{{ $t('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr v-if="!companies.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="companies.data && companies.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr class="text-nowrap" v-for="company in companies.data" :key="company.id">
            <td class="px-1">
              <div class="form-check">
                <input
                  v-if="
                    company.vendor_configuration.can_approve &&
                    !company.vendor_configuration.is_approved &&
                    !company.vendor_configuration.deleted_at
                  "
                  class="form-check-input border-primary"
                  type="checkbox"
                  :id="'company-' + company.id"
                  @change="updateSelected(company.id)"
                />
              </div>
            </td>
            <td class="text-primary text-decoration-underline">
              <span>
                <a v-if="company.can_view" :href="'../../companies/' + company.id + '/details'">
                  {{ company.name }}
                </a>
                <span v-else>{{ company.name }}</span>
                <i
                  class="tf-icons ti ti-info-circle ti-xs text-danger"
                  title="Pending Company Changes awating approval"
                  v-if="company.proposed_update_count > 0"
                ></i>
                <i
                  class="tf-icons ti ti-info-circle ti-xs text-danger"
                  :title="'Program Limit is almost depleted. ' + company.utilized_percentage_ratio + '% utilized'"
                  v-if="company.utilized_percentage_ratio > 90"
                ></i>
                <i
                  class="tf-icons ti ti-info-circle ti-xs text-danger"
                  :title="'Program Mapping Creation/Updation Awaiting Approval'"
                  v-if="company.changes"
                ></i>
                <i
                  class="tf-icons ti ti-info-circle ti-xs text-danger"
                  :title="'Program Mapping Deletion Awaiting Approval'"
                  v-if="company.vendor_configuration.deleted_at"
                ></i>
                <i
                  class="tf-icons ti ti-info-circle ti-xs text-danger"
                  :title="'Rejection Reason : ' + company.vendor_configuration.rejection_reason"
                  v-if="company.vendor_configuration.rejection_reason"
                ></i>
              </span>
            </td>
            <td
              class="text-primary"
              data-bs-toggle="modal"
              :data-bs-target="'#vendor-mapping-details-' + company.id"
              style="cursor: pointer"
            >
              <span>
                {{ company.vendor_configuration.payment_account_number }}
              </span>
            </td>
            <td class="">
              <span>{{ new Intl.NumberFormat().format(company.vendor_configuration.sanctioned_limit) }}</span>
            </td>
            <td class="">
              <span>
                {{ new Intl.NumberFormat().format(company.vendor_configuration.utilized_amount.toFixed(2)) }}
              </span>
            </td>
            <td class="">
              <span>
                {{ new Intl.NumberFormat().format(company.vendor_configuration.pipeline_amount.toFixed(2)) }}
              </span>
            </td>
            <td class="">
              <span>
                {{
                  new Intl.NumberFormat().format(
                    (
                      company.vendor_configuration.sanctioned_limit -
                      company.vendor_configuration.utilized_amount -
                      company.vendor_configuration.pipeline_amount
                    ).toFixed(2)
                  )
                }}
              </span>
            </td>
            <td>
              <span :class="'m_title badge ' + resolveStatus(company.vendor_configuration.status)">{{
                company.vendor_configuration.status
              }}</span>
            </td>
            <td class="" v-if="manage">
              <div class="d-flex gap-1">
                <a
                  v-if="
                    !company.changes &&
                    company.vendor_configuration.is_approved &&
                    !company.vendor_configuration.deleted_at &&
                    company.can_edit &&
                    company.vendor_configuration.status == 'active' &&
                    company.vendor_configuration.utilized_amount == 0 &&
                    company.vendor_configuration.pipeline_amount == 0
                  "
                  href="#"
                  data-bs-toggle="modal"
                  :data-bs-target="'#update-active-status-' + company.id"
                >
                  <i class="ti ti-thumb-up ti-sm text-success" title="Click to Deactivate Company Mapping"></i>
                </a>
                <a
                  v-if="
                    !company.changes &&
                    company.vendor_configuration.is_approved &&
                    !company.vendor_configuration.deleted_at &&
                    company.can_edit &&
                    company.vendor_configuration.status == 'inactive'
                  "
                  href="#"
                  data-bs-toggle="modal"
                  :data-bs-target="'#update-active-status-' + company.id"
                >
                  <i class="ti ti-thumb-down ti-sm text-danger" title="Click to Activate Company Mapping"></i>
                </a>
                <a
                  v-if="
                    !company.vendor_configuration.is_approved &&
                    !company.vendor_configuration.deleted_at &&
                    !company.vendor_configuration.is_rejected
                  "
                  href="#"
                  data-bs-toggle="modal"
                  :data-bs-target="'#pending-approval-mapping-' + company.id"
                >
                  <i class="ti ti-clock ti-sm text-primary" title="Pending Approval"></i>
                </a>
                <a
                  v-if="
                    company.vendor_configuration.can_approve &&
                    !company.vendor_configuration.is_approved &&
                    !company.vendor_configuration.deleted_at &&
                    !company.vendor_configuration.is_rejected
                  "
                  href="#"
                  data-bs-toggle="modal"
                  :data-bs-target="'#approve-mapping-' + company.id"
                >
                  <i class="ti ti-check ti-sm text-success" title="Click to Approve Mapping"></i>
                </a>
                <a
                  v-if="
                    !company.changes &&
                    company.vendor_configuration.is_approved &&
                    !company.vendor_configuration.deleted_at &&
                    company.can_edit
                  "
                  href="#"
                  data-bs-toggle="modal"
                  :data-bs-target="'#block-' + company.id"
                >
                  <i
                    :class="
                      company.vendor_configuration.is_blocked
                        ? 'ti ti-hand-off ti-sm text-danger'
                        : 'ti ti-hand-stop ti-sm text-success'
                    "
                    :title="
                      company.vendor_configuration.is_blocked
                        ? 'Click to UNBLOCK company from creating invoice and making requests'
                        : 'Click to BLOCK company from creating invoice and making requests'
                    "
                  >
                  </i>
                </a>
                <div
                  v-if="
                    !company.vendor_configuration.is_approved &&
                    !company.vendor_configuration.deleted_at &&
                    !company.vendor_configuration.is_rejected
                  "
                  class="modal fade"
                  :id="'pending-approval-mapping-' + company.id"
                  tabindex="-1"
                  aria-hidden="true"
                >
                  <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h6 class="modal-title" id="modalCenterTitle">{{ $t('Pending Approval') }}</h6>
                        <button
                          type="button"
                          class="btn-close"
                          :id="'update-active-status-close' + company.id"
                          data-bs-dismiss="modal"
                          aria-label="Close"
                        ></button>
                      </div>
                      <div class="modal-body">
                        <table class="table">
                          <thead>
                            <tr>
                              <th>{{ $t('Approval Type') }}</th>
                              <th>{{ $t('Proposed By') }}</th>
                              <th>{{ $t('Date Created') }}</th>
                              <th>{{ $t('Proposed Value(s)') }}</th>
                              <th>{{ $t('Status') }}</th>
                              <th>{{ $t('Actions') }}</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr>
                              <td>{{ $t('Create') }}</td>
                              <td>{{ company.vendor_configuration.user.name }}</td>
                              <td>{{ moment(company.vendor_configuration.created_at).format(date_format) }}</td>
                              <th>-</th>
                              <th>{{ $t('Pending Approval') }}</th>
                              <th>
                                <a
                                  v-if="
                                    company.vendor_configuration.can_approve &&
                                    !company.vendor_configuration.deleted_at &&
                                    !company.vendor_configuration.is_approved &&
                                    !company.vendor_configuration.is_rejected
                                  "
                                  href="#"
                                  data-bs-toggle="modal"
                                  :data-bs-target="'#approve-mapping-' + company.id"
                                >
                                  <i class="ti ti-check ti-sm text-success" title="Click to Approve Mapping"></i>
                                </a>
                                <a
                                  v-if="
                                    company.vendor_configuration.can_approve &&
                                    !company.vendor_configuration.deleted_at &&
                                    !company.vendor_configuration.is_approved &&
                                    !company.vendor_configuration.is_rejected
                                  "
                                  href="#"
                                  data-bs-toggle="modal"
                                  :data-bs-target="'#reject-mapping-' + company.id"
                                >
                                  <i class="ti ti-circle-x ti-sm text-danger" title="Click to Reject Mapping"></i>
                                </a>
                              </th>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="modal fade" :id="'approve-mapping-' + company.id" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h6 class="modal-title" id="modalCenterTitle">{{ $t('Approve Mapping') }}</h6>
                        <button
                          type="button"
                          class="btn-close"
                          :id="'approve-mapping-status-close-' + company.id"
                          data-bs-dismiss="modal"
                          aria-label="Close"
                        ></button>
                      </div>
                      <div class="modal-body">
                        <div class="row mb-1">
                          <h6 class="text-wrap">{{ $t('Are you sure you want to approve the mapping?') }}</h6>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button
                          class="btn btn-primary"
                          type="button"
                          @click="updateApprovalStatus(company.id, 'approve')"
                        >
                          {{ $t('Confirm') }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="modal fade" :id="'update-active-status-' + company.id" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h6 class="modal-title" id="modalCenterTitle">{{ $t('Update Mapping Active Status') }}</h6>
                        <button
                          type="button"
                          class="btn-close"
                          :id="'update-company-mapping-status-close-' + company.id"
                          data-bs-dismiss="modal"
                          aria-label="Close"
                        ></button>
                      </div>
                      <div class="modal-body">
                        <div class="row mb-1">
                          <h6 class="text-wrap">
                            {{ $t('Are you sure you want to activate/deactivate the mapping?') }}
                          </h6>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button class="btn btn-primary" type="button" @click="updateActiveStatus(company.id)">
                          {{ $t('Confirm') }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="modal fade" :id="'block-' + company.id" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h6 class="modal-title" id="modalCenterTitle" v-if="!company.vendor_configuration.is_blocked">
                          {{ $t('Block Company') }}
                        </h6>
                        <h6 class="modal-title" id="modalCenterTitle" v-else>{{ $t('Unblock Company') }}</h6>
                        <button
                          type="button"
                          class="btn-close"
                          :id="'update-block-' + company.id"
                          data-bs-dismiss="modal"
                          aria-label="Close"
                        ></button>
                      </div>
                      <div class="modal-body">
                        <div class="row mb-1">
                          <h6 class="text-wrap" v-if="!company.vendor_configuration.is_blocked">
                            {{
                              $t(
                                'Are you sure you want to BLOCK the company from creating invoices and requesting financing?'
                              )
                            }}
                          </h6>
                          <h6 class="text-wrap" v-else>
                            {{
                              $t(
                                'Are you sure you want to UNBLOCK the company from creating invoices and requesting financing?'
                              )
                            }}
                          </h6>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button class="btn btn-primary" type="button" @click="updateBlockStatus(company.id)">
                          {{ $t('Confirm') }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                <a
                  v-if="
                    company.vendor_configuration.can_approve &&
                    !company.vendor_configuration.deleted_at &&
                    !company.vendor_configuration.is_approved &&
                    !company.vendor_configuration.is_rejected
                  "
                  href="#"
                  data-bs-toggle="modal"
                  :data-bs-target="'#reject-mapping-' + company.id"
                >
                  <i class="ti ti-circle-x ti-sm text-danger" title="Click to Reject Mapping"></i>
                </a>
                <div class="modal fade" :id="'reject-mapping-' + company.id" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h6 class="modal-title" id="modalCenterTitle">{{ $t('Reject Mapping') }}</h6>
                        <button
                          type="button"
                          class="btn-close"
                          :id="'reject-mapping-status-close-' + company.id"
                          data-bs-dismiss="modal"
                          aria-label="Close"
                        ></button>
                      </div>
                      <div class="modal-body">
                        <div class="mb-1">
                          <h6 class="text-wrap">{{ $t('Are you sure you want to reject the mapping?') }}</h6>
                        </div>
                        <textarea
                          name="rejection_reason"
                          class="form-control"
                          rows="5"
                          placeholder="Enter Rejection Reason"
                          required
                          v-model="rejection_reason"
                          id=""
                        ></textarea>
                      </div>
                      <div class="modal-footer">
                        <button
                          class="btn btn-primary"
                          type="button"
                          @click="updateApprovalStatus(company.id, 'reject')"
                        >
                          {{ $t('Confirm') }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                <span data-bs-toggle="modal" :data-bs-target="'#vendor-mapping-details-' + company.id">
                  <i class="ti ti-eye ti-sm text-primary" style="cursor: pointer" title="View Mapping Details"></i>
                </span>
                <a
                  v-if="
                    !company.vendor_configuration.rejected &&
                    company.vendor_configuration.is_approved &&
                    !company.vendor_configuration.deleted_at &&
                    !company.changes &&
                    can_edit_mapping
                  "
                  :href="vendors_type + '/' + company.id + '/map/edit'"
                >
                  <i class="ti ti-pencil ti-sm text-warning" title="Edit Mapping"></i>
                </a>
                <a
                  v-if="!company.vendor_configuration.rejected && company.vendor_configuration.is_rejected"
                  :href="vendors_type + '/' + company.id + '/map/edit'"
                >
                  <i class="ti ti-pencil ti-sm text-warning" title="Edit Mapping"></i>
                </a>
                <span
                  v-if="company.changes"
                  data-bs-toggle="modal"
                  :data-bs-target="'#vendor-mapping-changes-details-' + company.id"
                >
                  <i
                    class="ti ti-clock ti-sm text-warning"
                    style="cursor: pointer"
                    title="View Pending Mapping Changes Details"
                  ></i>
                </span>
                <!-- Changes Modal -->
                <div
                  v-if="company.changes"
                  class="modal modal-top fade modal-xl"
                  :id="'vendor-mapping-changes-details-' + company.id"
                  tabindex="-1"
                >
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h6>{{ $t('Mapping Changes Details') }}</h6>
                        <button
                          type="button"
                          class="btn-close"
                          data-bs-dismiss="modal"
                          :id="'vendor-mapping-changes-details-close-' + company.id"
                          aria-label="Close"
                        ></button>
                      </div>
                      <div class="modal-body">
                        <div class="row">
                          <div class="col-6">
                            <h6>{{ $t('General Configuration') }}</h6>
                            <div
                              class="d-flex flex-column"
                              v-for="(setting, column) in company.changes.changes.vendor_configuration"
                              :key="setting"
                            >
                              <!-- Check if setting has been changed -->
                              <span class="m_title" v-if="column == 'gst_number'">KRA PIN: {{ setting }}</span>
                              <span class="m_title" v-if="column == 'request_auto_finance'"
                                >{{ $t('Request auto finance') }}: {{ setting == 0 ? 'No' : 'Yes' }}</span
                              >
                              <span class="m_title" v-if="column == 'auto_approve_finance'"
                                >{{ $t('Auto approve finance') }}: {{ setting == 0 ? 'No' : 'Yes' }}</span
                              >
                              <span class="m_title" v-if="column == 'is_blocked'"
                                >{{ $t('Block Company in Program') }}: {{ setting == 0 ? 'No' : 'Yes' }}</span
                              >
                              <span
                                class="m_title"
                                v-if="
                                  setting != '' &&
                                  setting != null &&
                                  column != 'gst_number' &&
                                  column != 'request_auto_finance' &&
                                  column != 'is_blocked' &&
                                  column != 'auto_approve_finance'
                                "
                                >{{ column.replaceAll('_', ' ') }}: {{ setting }}</span
                              >
                            </div>
                          </div>
                          <div class="col-6">
                            <h6>{{ $t('Discount Details') }}</h6>
                            <div
                              class="d-flex flex-column"
                              v-for="(setting, column) in company.changes.changes.vendor_discount_details"
                              :key="setting"
                            >
                              <span
                                class="m_title"
                                v-if="typeof setting == 'string' && setting != '' && setting != null"
                                >{{ column.replaceAll('_', ' ') }}: {{ setting }}</span
                              >
                              <template v-if="typeof setting == 'object'">
                                <span v-for="(setting_change, index) in setting" :key="setting_change" class="m_title">
                                  <span
                                    v-if="
                                      index == 'from_day' ||
                                      index == 'to_day' ||
                                      index == 'business_strategy_spread' ||
                                      index == 'credit_spread' ||
                                      index == 'total_roi'
                                    "
                                  >
                                    {{ index.replaceAll('_', ' ') }}: {{ setting_change }}
                                  </span>
                                </span>
                              </template>
                            </div>
                          </div>
                          <div class="col-6">
                            <h6>{{ $t('Fees Details') }}</h6>
                            <div
                              class="d-flex flex-column"
                              v-for="change in company.changes.changes.vendor_fee_details"
                              :key="change"
                            >
                              <div class="m_title" v-for="(setting, column) in change" :key="setting">
                                <span v-if="setting != '' && setting != null"
                                  >{{ column.replaceAll('_', ' ') }}: {{ setting }}</span
                                >
                              </div>
                            </div>
                          </div>
                          <div class="col-6">
                            <h6>{{ $t('Bank Details') }}</h6>
                            <div
                              class="d-flex flex-column"
                              v-for="change in company.changes.changes.vendor_bank_details"
                              :key="change"
                            >
                              <span class="m_title" v-for="(setting, column) in change" :key="setting"
                                >{{ column.replaceAll('_', ' ') }}: {{ setting }}</span
                              >
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="modal-footer" v-if="company.changes.can_approve">
                        <button
                          class="btn btn-sm btn-danger"
                          @click="approveConfigChange(program_id, company.id, 'reject')"
                        >
                          {{ $t('Reject') }}
                        </button>
                        <button
                          class="btn btn-sm btn-primary"
                          @click="approveConfigChange(program_id, company.id, 'approve')"
                        >
                          {{ $t('Approve') }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Details Modal -->
                <div class="modal modal-top fade modal-xl" :id="'vendor-mapping-details-' + company.id" tabindex="-1">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h6 class="modal-title" id="modalTopTitle">{{ company.name }} {{ $t('Mapping details') }}</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <div class="row">
                          <div class="col-sm-4 text-align-center d-flex justify-content-between">
                            <h6 class="mr-2 fw-light">{{ $t('Sanctioned Limit') }}:</h6>
                            <h6 class="px-2 text-right">
                              {{ new Intl.NumberFormat().format(company.vendor_configuration.sanctioned_limit) }}
                            </h6>
                          </div>
                          <div class="col-sm-4 text-align-center d-flex justify-content-between">
                            <h6 class="mr-2 fw-light">{{ $t('Drawing Power') }}:</h6>
                            <h6 class="px-2 text-right">
                              {{ new Intl.NumberFormat().format(company.vendor_configuration.drawing_power) }}
                            </h6>
                          </div>
                          <div class="col-sm-4 text-align-center d-flex justify-content-between">
                            <h6 class="mr-2 fw-light">{{ $t('Eligibility') }}:</h6>
                            <h6 class="px-2 text-right">{{ company.vendor_configuration.eligibility }}%</h6>
                          </div>
                          <div class="col-sm-4 text-align-center d-flex justify-content-between">
                            <h6 class="mr-2 fw-light">{{ $t('Payment Account') }}:</h6>
                            <h6 class="px-2 text-right">
                              {{ company.vendor_configuration.payment_account_number }}
                            </h6>
                          </div>
                          <div class="col-sm-4 text-align-center d-flex justify-content-between">
                            <h6 class="mr-2 fw-light">{{ $t('Limit Approval Date') }}:</h6>
                            <h6 class="px-2 text-right">
                              {{ moment(company.vendor_configuration.limit_approved_date).format(date_format) }}
                            </h6>
                          </div>
                          <div class="col-sm-4 text-align-center d-flex justify-content-between">
                            <h6 class="mr-2 fw-light">{{ $t('Limit Expiry Date') }}:</h6>
                            <h6 class="px-2 text-right">
                              {{ moment(company.vendor_configuration.limit_expiry_date).format(date_format) }}
                            </h6>
                          </div>
                          <div class="col-sm-4 text-align-center d-flex justify-content-between">
                            <h6 class="mr-2 fw-light">{{ $t('Limit Review Date') }}:</h6>
                            <h6 class="px-2 text-right">
                              {{ moment(company.vendor_configuration.limit_review_date).format(date_format) }}
                            </h6>
                          </div>
                          <div class="col-sm-4 text-align-center d-flex justify-content-between">
                            <h6 class="mr-2 fw-light">{{ $t('Request Auto Finance') }}:</h6>
                            <h6 class="px-2 text-right">
                              {{ company.vendor_configuration.request_auto_finance ? 'Yes' : 'No' }}
                            </h6>
                          </div>
                          <div class="col-sm-4 text-align-center d-flex justify-content-between">
                            <h6 class="mr-2 fw-light">{{ $t('Auto Approve Request') }}:</h6>
                            <h6 class="px-2 text-right">
                              {{ company.vendor_configuration.auto_approve_finance ? 'Yes' : 'No' }}
                            </h6>
                          </div>
                          <div class="col-sm-4 text-align-center d-flex justify-content-between">
                            <h6 class="mr-2 fw-light">{{ $t('Auto Request Finance') }}:</h6>
                            <h6 class="px-2 text-right">
                              {{ company.vendor_configuration.auto_request_finance ? 'Yes' : 'No' }}
                            </h6>
                          </div>
                          <div
                            v-if="company.vendor_discount_details && company.vendor_discount_details.length > 0"
                            class="col-sm-4 text-align-center d-flex justify-content-between"
                          >
                            <h6 class="mr-2 fw-light">{{ $t('Penal Discount on Principle') }}:</h6>
                            <h6 class="px-2 text-right">
                              {{ company.vendor_discount_details[0].penal_discount_on_principle }}%
                            </h6>
                          </div>
                        </div>
                        <hr />
                        <div class="">
                          <h6>{{ $t('Discounts') }}</h6>
                        </div>
                        <div class="">
                          <div class="table-responsive">
                            <table class="table">
                              <thead>
                                <tr>
                                  <th v-if="vendors_type == 'dealers'">{{ $t('To Day') }}</th>
                                  <th v-if="vendors_type == 'dealers'">{{ $t('From Day') }}</th>
                                  <th>{{ $t('Benchmark Rate') }}</th>
                                  <th>{{ $t('Business Strategy Spread') }}</th>
                                  <th>{{ $t('Credit Spread') }}</th>
                                  <th>{{ $t('Total Spread') }}</th>
                                  <th>{{ $t('Total ROI') }}</th>
                                  <th v-if="vendors_type == 'vendors'">
                                    {{ $t('Anchor Discount Bearing') }}
                                  </th>
                                  <th v-if="vendors_type == 'buyers'">
                                    {{ $t('Buyer Discount Bearing') }}
                                  </th>
                                  <th v-if="vendors_type == 'vendors' || vendors_type == 'buyers'">
                                    {{ $t('Vendor Discount Bearing') }}
                                  </th>
                                </tr>
                              </thead>
                              <tbody class="table-border-bottom-0 text-nowrap">
                                <tr
                                  v-for="discount_detail in company.vendor_discount_details"
                                  :key="discount_detail.id"
                                >
                                  <td v-if="vendors_type == 'dealers'">{{ discount_detail.from_day }}</td>
                                  <td v-if="vendors_type == 'dealers'">{{ discount_detail.to_day }}</td>
                                  <td>
                                    {{
                                      discount_detail.benchmark_rate ? discount_detail.benchmark_rate.toFixed(2) : 0
                                    }}%
                                  </td>
                                  <td>
                                    {{
                                      discount_detail.business_strategy_spread
                                        ? discount_detail.business_strategy_spread.toFixed(2)
                                        : 0
                                    }}%
                                  </td>
                                  <td>
                                    {{ discount_detail.credit_spread ? discount_detail.credit_spread.toFixed(2) : 0 }}%
                                  </td>
                                  <td>
                                    {{ discount_detail.total_spread ? discount_detail.total_spread.toFixed(2) : 0 }}%
                                  </td>
                                  <td>{{ discount_detail.total_roi ? discount_detail.total_roi.toFixed(2) : 0 }}%</td>
                                  <td v-if="vendors_type == 'vendors' || vendors_type == 'buyers'">
                                    {{
                                      discount_detail.anchor_discount_bearing
                                        ? discount_detail.anchor_discount_bearing.toFixed(2)
                                        : 0
                                    }}%
                                  </td>
                                  <td v-if="vendors_type == 'vendors' || vendors_type == 'buyers'">
                                    {{
                                      discount_detail.vendor_discount_bearing
                                        ? discount_detail.vendor_discount_bearing.toFixed(2)
                                        : 0
                                    }}%
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                        </div>
                        <hr />
                        <div class="py-1r">
                          <h6>{{ $t('Fees') }}</h6>
                        </div>
                        <div class="">
                          <div class="table-responsive">
                            <table class="table">
                              <thead>
                                <tr>
                                  <th>{{ $t('Name') }}</th>
                                  <th>{{ $t('Type') }}</th>
                                  <th>{{ $t('Value') }}</th>
                                  <th v-if="vendors_type == 'vendors' || vendors_type == 'buyers'">
                                    {{ $t('Vendor Bearing') }}(%)
                                  </th>
                                  <th v-if="vendors_type == 'vendors'">{{ $t('Anchor Bearing') }}(%)</th>
                                  <th v-if="vendors_type == 'buyers'">{{ $t('Buyer Bearing') }}(%)</th>
                                  <th v-if="vendors_type == 'dealers'">{{ $t('Dealer Bearing') }}(%)</th>
                                  <th>{{ $t('Charge Type') }}</th>
                                  <th>{{ $t('Credit To') }}</th>
                                  <th>{{ $t('Taxes') }}</th>
                                </tr>
                              </thead>
                              <tbody class="table-border-bottom-0 text-nowrap">
                                <tr v-for="fee in company.vendor_fee_details" :key="fee.id">
                                  <td>{{ fee.fee_name }}</td>
                                  <td class="m_title">{{ fee.type }}</td>
                                  <td>{{ fee.value }}</td>
                                  <td v-if="vendors_type == 'vendors' || vendors_type == 'buyers'">
                                    {{ fee.vendor_bearing_discount.toFixed(2) }}
                                  </td>
                                  <td v-if="vendors_type == 'vendors' || vendors_type == 'buyers'">
                                    {{ fee.anchor_bearing_discount.toFixed(2) }}
                                  </td>
                                  <td v-if="vendors_type == 'dealers'">{{ fee.dealer_bearing.toFixed(2) }}</td>
                                  <td>
                                    <span v-if="fee.charge_type === 'daily'" title="Daily">{{ $t('Per Day') }}</span>
                                    <span v-else>{{ fee.charge_type }}</span>
                                  </td>
                                  <td>
                                    <span>{{ fee.account_number }}</span>
                                  </td>
                                  <td>
                                    <span v-if="fee.taxes">{{ fee.taxes }}%</span>
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                        </div>
                        <hr />
                        <div class="py-1r">
                          <h6>{{ $t('Bank Accounts Details') }}</h6>
                        </div>
                        <div class="">
                          <div class="table-responsive">
                            <table class="table">
                              <thead>
                                <tr>
                                  <th>{{ $t('Name') }}</th>
                                  <th>{{ $t('Bank') }}</th>
                                  <th>{{ $t('Swift Code') }}</th>
                                  <th>{{ $t('Branch') }}</th>
                                  <th>{{ $t('Account Number') }}</th>
                                  <th>{{ $t('Account Type') }}</th>
                                </tr>
                              </thead>
                              <tbody class="table-border-bottom-0 text-nowrap">
                                <tr v-for="bank_details in company.vendor_bank_details" :key="bank_details.id">
                                  <td>{{ bank_details.name_as_per_bank }}</td>
                                  <td>{{ bank_details.bank_name }}</td>
                                  <td>{{ bank_details.swift_code }}</td>
                                  <td>{{ bank_details.branch }}</td>
                                  <td>{{ bank_details.account_number }}</td>
                                  <td>{{ bank_details.account_type }}</td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Delete Mapping -->
                <a
                  v-if="company.vendor_configuration.can_delete"
                  href="#"
                  data-bs-toggle="modal"
                  :data-bs-target="'#delete-' + company.id"
                >
                  <i class="ti ti-trash ti-sm text-danger" title="Delete Company Mapping"></i>
                </a>
                <!-- Delete Modal -->
                <div class="modal fade" :id="'delete-' + company.id" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h6 class="modal-title" id="modalCenterTitle">{{ $t('Delete Mapping') }}</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <div class="row mb-1">
                          <h6 class="text-wrap">
                            {{
                              $t(
                                'Are you sure you want to delete the mapping with OD: ' +
                                  company.vendor_configuration.payment_account_number
                              )
                            }}?
                          </h6>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button
                          class="btn btn-secondary"
                          type="button"
                          data-bs-dismiss="modal"
                          :id="'delete-mapping-' + company.id"
                        >
                          {{ $t('Cancel') }}
                        </button>
                        <button
                          v-if="company.vendor_configuration.deleted_at"
                          class="btn btn-primary"
                          type="button"
                          @click="cancelDeleteMapping(company.id)"
                        >
                          {{ $t('Cancel Deletion') }}
                        </button>
                        <button class="btn btn-danger" type="button" @click="deleteMapping(company.id, 'approve')">
                          {{ $t('Delete') }}
                        </button>
                      </div>
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
      :from="companies.from"
      :to="companies.to"
      :links="companies.links"
      :next_page="companies.next_page_url"
      :prev_page="companies.prev_page_url"
      :total_items="companies.total"
      :first_page_url="companies.first_page_url"
      :last_page_url="companies.last_page_url"
      @change-page="changePage"
    ></pagination>
  </div>
</template>

<script>
import moment from 'moment';
import { useToast } from 'vue-toastification';
import { computed, onMounted, ref, watch, inject } from 'vue';
import { RouterLink } from 'vue-router';
import Pagination from '../partials/Pagination.vue';
import axios from 'axios';
export default {
  name: 'ManageVendors',
  props: ['bank', 'program', 'type', 'can_edit_mapping', 'manage', 'date_format'],
  components: {
    RouterLink,
    Pagination
  },
  setup(props) {
    const date_format = props.date_format;
    const can_edit_mapping = props.can_edit_mapping;
    const manage = props.manage;
    const program_id = props.program;
    const vendors_type = props.type;
    const base_url = inject('baseURL');
    const toast = useToast();
    const companies = ref([]);
    const rejection_reason = ref('');
    const bank = props.bank;
    const selected_companies = ref([]);
    const bulk_action = ref('');
    const showRejectionModal = ref(null);
    const showApprovalModal = ref(null);

    // Search fields
    const name_search = ref('');

    // Pagination
    const per_page = ref(50);

    const getCompanies = async () => {
      await axios
        .get(base_url + props.bank + '/programs/' + props.program + '/vendors-manage/data', {
          params: {
            per_page: per_page.value,
            name: name_search.value
          }
        })
        .then(response => {
          companies.value = response.data;
        });
    };

    const updateSelected = id => {
      if (selected_companies.value.includes(id)) {
        const index = selected_companies.value.indexOf(id);
        selected_companies.value.splice(index, 1);
      } else {
        selected_companies.value.push(id);
      }
    };

    const selectAll = () => {
      if (!document.getElementById('select-all').checked) {
        companies.value.data.forEach(company => {
          if (document.getElementById('company-' + company.id)) {
            document.getElementById('company-' + company.id).checked = false;
            let f;
            let index = selected_companies.value.filter(function (id, index) {
              f = index;
              id == company.id;
            });
            if (!index) {
              return false;
            }
            selected_companies.value.splice(f, 1);
          }
        });
      } else {
        companies.value.data.forEach(company => {
          if (document.getElementById('company-' + company.id)) {
            document.getElementById('company-' + company.id).checked = true;
            selected_companies.value.push(company.id);
          }
        });
      }
    };

    const resolveStatus = status => {
      let style = '';
      switch (status) {
        case 'active':
          style = 'bg-label-primary';
          break;
        case 'inactive':
          style = 'bg-label-danger';
          break;
        default:
          style = 'bg-label-primary';
          break;
      }
      return style;
    };

    watch([per_page], ([new_per_page]) => {
      axios
        .get(base_url + props.bank + '/programs/' + props.program + '/vendors-manage/data', {
          params: {
            per_page: new_per_page,
            name: name_search.value
          }
        })
        .then(response => {
          companies.value = response.data;
        });
    });

    const filter = () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      axios
        .get(base_url + props.bank + '/programs/' + props.program + '/vendors-manage/data', {
          params: {
            per_page: per_page.value,
            name: name_search.value
          }
        })
        .then(response => {
          companies.value = response.data;
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = () => {
      name_search.value = '';
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      axios
        .get(base_url + props.bank + '/programs/' + props.program + '/vendors-manage/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          companies.value = response.data;
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    watch(bulk_action, newVal => {
      if (selected_companies.value.length > 0) {
        if (newVal == 'inactive') {
          showRejectionModal.value.click();
        }
        if (newVal == 'active') {
          showApprovalModal.value.click();
        }
      } else {
        toast.error('Select Companies');
      }
    });

    const submitBulkApproval = action => {
      if (selected_companies.value.length <= 0) {
        toast.error('Select companies');
        return;
      }

      const formData = new FormData();
      selected_companies.value.forEach(request => {
        formData.append('companies[]', request);
      });
      formData.append('status', action);
      formData.append('rejection_reason', rejection_reason.value);

      axios
        .post(base_url + props.bank + '/programs/' + props.program + '/mapping/approval/update', formData)
        .then(() => {
          getCompanies();
          bulk_action.value = '';
          rejection_reason.value = '';
          document.getElementById('select-all').checked = false;
          if (action == 'approve') {
            document.getElementById('close-activate-modal').click();
          } else {
            document.getElementById('close-deactiveate-modal').click();
          }
          selected_companies.value.forEach(selected => {
            document.getElementById('company-' + selected).checked = false;
          });
          toast.success('Mapping status updated');
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    const submitBulkAction = action => {
      if (selected_companies.value.length <= 0) {
        toast.error('Select companies');
        return;
      }

      const formData = new FormData();
      if (action == 'inactive') {
        selected_companies.value.forEach(request => {
          formData.append('companies[]', request);
        });
        formData.append('status', 'inactive');
      }
      if (action == 'active') {
        selected_companies.value.forEach(request => {
          formData.append('companies[]', request);
        });
        formData.append('status', 'active');
      }

      axios
        .post(base_url + props.bank + '/programs/' + props.program + '/mapping/update', formData)
        .then(() => {
          getCompanies();
          bulk_action.value = '';
          document.getElementById('select-all').checked = false;
          if (action == 'inactive') {
            document.getElementById('close-deactivate-modal').click();
          } else {
            document.getElementById('close-activate-modal').click();
          }
          selected_companies.value.forEach(selected => {
            document.getElementById('company-' + selected).checked = false;
          });
          toast.success('Mapping status updated');
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    const updateActiveStatus = company => {
      axios
        .get(base_url + props.bank + '/programs/' + props.program + '/' + company + '/mapping/update')
        .then(() => {
          getCompanies();
          toast.success('Mapping status updated');
          document.getElementById('update-company-mapping-status-close-' + company).click();
        })
        .catch(err => {
          toast.error(err.response.data.message);
          console.log(err);
        });
    };

    const updateBlockStatus = company => {
      axios
        .get(base_url + props.bank + '/programs/' + props.program + '/' + company + '/mapping/block/status/update')
        .then(res => {
          getCompanies();
          toast.success(res.data.message);
          document.getElementById('update-block-' + company).click();
        })
        .catch(err => {
          toast.error(err.response.data.message);
          console.log(err);
        });
    };

    const updateApprovalStatus = (company, status) => {
      axios
        .post(base_url + props.bank + '/programs/' + props.program + '/' + company + '/mapping/approval/update', {
          status: status,
          rejection_reason: rejection_reason.value
        })
        .then(() => {
          getCompanies();
          rejection_reason.value = '';
          toast.success('Mapping status updated successfully');
          if (status == 'approve') {
            document.getElementById('approve-mapping-status-close-' + company).click();
          } else {
            document.getElementById('reject-mapping-status-close-' + company).click();
          }
        })
        .catch(err => {
          console.log(err);
        });
    };

    const approveConfigChange = (program, company, status) => {
      axios
        .get('mapping/company/' + company + '/changes/approve/' + status)
        .then(response => {
          getCompanies();
          toast.success(response.data.message);
          document.getElementById('vendor-mapping-changes-details-close-' + company).click();
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    const deleteMapping = company => {
      axios
        .delete('mapping/' + company + '/delete')
        .then(response => {
          document.getElementById('delete-mapping-' + company).click();
          toast.success(response.data.message);
          getCompanies();
        })
        .catch(err => {
          toast.error(err.response.data.message);
        });
    };

    const cancelDeleteMapping = company => {
      axios
        .get('mapping/' + company + '/delete/cancel')
        .then(res => {
          toast.success(res.data.message);
          document.getElementById('delete-mapping-' + company).click();
          getCompanies();
        })
        .catch(err => {
          toast.error(err.response.data.message);
        });
    };

    const hasAnchorRole = roles => {
      let company_roles = [];
      roles.forEach(role => {
        company_roles.push(role.name);
      });

      if (company_roles.length > 0 && company_roles.includes('anchor')) {
        return true;
      }
      return false;
    };

    onMounted(() => {
      getCompanies();
    });

    const exportData = async () => {
      await axios
        .get(base_url + props.bank + '/programs/' + props.program + '/vendors-manage/data/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            name: name_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Mappings_${moment().format('Do_MMM_YYYY')}.csv`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    const changePage = async page => {
      await axios
        .get(page + '&per_page=' + per_page.value, {
          params: {
            name: name_search.value
          }
        })
        .then(response => {
          companies.value = response.data;
        });
    };

    return {
      base_url,
      bank,
      companies,
      moment,
      rejection_reason,

      can_edit_mapping,
      manage,
      program_id,
      vendors_type,

      // Search fields
      name_search,

      // Pagination
      per_page,

      filter,
      refresh,

      bulk_action,

      showRejectionModal,
      showApprovalModal,

      deleteMapping,

      selectAll,
      updateSelected,
      resolveStatus,
      updateActiveStatus,
      submitBulkAction,
      updateApprovalStatus,
      changePage,
      hasAnchorRole,
      approveConfigChange,
      cancelDeleteMapping,
      submitBulkApproval,
      date_format,
      updateBlockStatus,
      exportData
    };
  }
};
</script>

<style scoped>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
