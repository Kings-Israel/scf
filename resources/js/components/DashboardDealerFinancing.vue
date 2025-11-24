<template>
  <div class="card p-1">
    <div class="d-flex justify-content-between">
      <h5 class="card-header px-1">{{ $t('Latest Dealer Financing Requests') }}</h5>
      <a class="card-header text-primary text-decoration-underline" :href="bank_url + '/requests/dealer-financing'">{{
        $t('View All')
      }}</a>
    </div>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th class="px-1">{{ $t('Payment Reference No') }}.</th>
            <th class="">{{ $t('Invoice No') }}.</th>
            <th class="px-1">{{ $t('Dealer') }}</th>
            <th class="px-1">{{ $t('Anchor') }}</th>
            <th class="px-1">{{ $t('Request Date') }}</th>
            <th class="px-1">{{ $t('Due Date') }}</th>
            <th class="px-1">{{ $t('PI Amount') }}</th>
            <th class="px-1">{{ $t('Payment Amount') }}</th>
            <th class="px-1">{{ $t('Status') }}</th>
            <th class="px-1">{{ $t('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr v-if="!requests.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="requests.data && requests.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr v-for="payment_request in requests.data" :key="payment_request.id">
            <td
              @click="showInvoice(payment_request.invoice_id, 'show-payment-request-btn-' + payment_request.invoice_id)"
              class="text-primary text-decoration-underline px-1"
              style="cursor: pointer"
            >
              {{ payment_request.reference_number }}
            </td>
            <td
              class="text-primary text-decoration-underline"
              style="cursor: pointer"
              @click="showInvoice(payment_request.invoice_id, 'show-details-btn-' + payment_request.invoice_id)"
            >
              {{ payment_request.invoice_number }}
            </td>
            <td class="text-primary text-decoration-underline px-2">
              <a
                v-if="payment_request.can_view_company"
                href="#"
                @click="
                  showCompany(payment_request.company_id, 'show-vendor-details-btn-' + payment_request.company_id)
                "
              >
                {{ payment_request.company_name }}
              </a>
              <span v-else>{{ payment_request.company_name }}</span>
            </td>
            <td class="text-primary text-decoration-underline px-2">
              <a
                v-if="payment_request.can_view_company"
                href="#"
                @click="showCompany(payment_request.anchor_id, 'show-anchor-details-btn-' + payment_request.anchor_id)"
              >
                {{ payment_request.anchor_name }}
              </a>
              <span v-else>{{ payment_request.anchor_name }}</span>
            </td>
            <td class="px-1">
              {{ moment(payment_request.created_at).format(date_format) }}
            </td>
            <td class="px-1">
              {{ moment(payment_request.due_date).format(date_format) }}
            </td>
            <td class="text-success px-1">
              {{ payment_request.currency }}
              {{ new Intl.NumberFormat().format(payment_request.drawdown_amount) }}
            </td>
            <td class="text-success px-1">
              {{ payment_request.currency }}
              {{ new Intl.NumberFormat().format(payment_request.amount.toFixed(2)) }}
            </td>
            <td class="px-1">
              <span class="badge me-1 m_title" :class="resolvePaymentRequestStatus(payment_request.approval_stage)">{{
                payment_request.approval_stage
              }}</span>
            </td>
            <td class="px-1">
              <div class="d-flex">
                <span
                  v-if="
                    !payment_request.program_suspended &&
                    !payment_request.mapping_suspended &&
                    payment_request.user_can_approve &&
                    !payment_request.user_has_approved
                  "
                  ><i
                    class="ti ti-circle-check ti-sm text-primary"
                    style="cursor: pointer"
                    title="Approve"
                    data-bs-toggle="modal"
                    :data-bs-target="'#approve-payment-request-' + payment_request.id"
                  ></i
                ></span>
                <span
                  v-if="
                    !payment_request.program_suspended &&
                    !payment_request.mapping_suspended &&
                    payment_request.user_can_approve &&
                    !payment_request.user_has_approved
                  "
                  ><i
                    class="ti ti-circle-x ti-sm mx-1 text-danger"
                    title="Reject"
                    data-bs-toggle="modal"
                    :data-bs-target="'#update-payment-request-' + payment_request.id"
                    style="cursor: pointer"
                  ></i
                ></span>
                <span
                  v-if="
                    (payment_request.program_suspended || payment_request.mapping_suspended) &&
                    payment_request.status != 'rejected'
                  "
                >
                  <i class="ti ti-circle-x ti-sm mx-auto text-danger" :title="$t('Program/Mapping is suspended')"></i>
                </span>
                <span v-if="payment_request.status == 'rejected'"
                  ><i
                    class="ti ti-circle-x ti-sm mx-1 text-danger"
                    :title="payment_request.rejected_reason"
                    style="cursor: pointer"
                  ></i
                ></span>
              </div>
              <button
                class="d-none"
                :id="'show-payment-request-btn-' + payment_request.invoice_id"
                data-bs-toggle="modal"
                :data-bs-target="'#payment-request-show-' + payment_request.invoice_id"
              ></button>
              <button
                class="d-none"
                :id="'show-details-btn-' + payment_request.invoice_id"
                data-bs-toggle="modal"
                :data-bs-target="'#invoice-' + payment_request.invoice_id"
              ></button>
              <button
                class="d-none"
                :id="'show-pi-btn-' + payment_request.invoice_id"
                data-bs-toggle="modal"
                :data-bs-target="'#pi-' + payment_request.invoice_id"
              ></button>
              <button
                class="d-none"
                :id="'show-anchor-details-btn-' + payment_request.anchor_id"
                data-bs-toggle="modal"
                :data-bs-target="'#company-details'"
              ></button>
              <button
                class="d-none"
                :id="'show-vendor-details-btn-' + payment_request.company_id"
                data-bs-toggle="modal"
                :data-bs-target="'#company-details'"
              ></button>
              <button
                class="d-none"
                id="loading-modal-btn"
                data-bs-toggle="modal"
                data-bs-target="#loading-modal"
              ></button>
              <div class="modal fade" id="loading-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                  <div class="modal-content">
                    <div class="modal-body">
                      <div class="d-flex justify-content-center">
                        <span>{{ $t('Loading') }}...</span>
                        <img src="../../../public/assets/img/tube-spinner.svg" style="width: 1.3rem" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <invoice-details v-if="invoice_details" :invoice-details="invoice_details" :key="invoice_details_key" />
              <payment-instruction
                v-if="invoice_details"
                :invoice-details="invoice_details"
                :key="invoice_details_key"
              />
              <div
                class="modal fade"
                :id="'payment-request-show-' + payment_request.invoice_id"
                tabindex="-1"
                aria-hidden="true"
              >
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Payment Request') }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      <div class="d-flex gap-1">
                        <span v-if="payment_request.user_can_approve && !payment_request.user_has_approved"
                          ><button
                            class="btn btn-label-primary"
                            data-bs-toggle="modal"
                            :data-bs-target="'#approve-payment-request-' + payment_request.id"
                          >
                            {{ $t('Approve') }}
                          </button></span
                        >
                        <span class="" v-if="payment_request.user_can_approve && !payment_request.user_has_approved"
                          ><button
                            class="btn btn-label-danger"
                            data-bs-toggle="modal"
                            :data-bs-target="'#update-payment-request-' + payment_request.id"
                            style="cursor: pointer"
                          >
                            {{ $t('Reject') }}
                          </button></span
                        >
                        <div>
                          <button
                            class="btn btn-primary"
                            type="button"
                            data-bs-toggle="modal"
                            :data-bs-target="'#add-attachment-' + payment_request.invoice_id"
                          >
                            {{ $t('Attachment') }}
                          </button>
                        </div>
                        <div>
                          <a :href="'../payment-request/' + payment_request.id + '/download'" class="btn btn-primary"
                            ><i class="ti ti-printer"></i> {{ $t('Print') }}</a
                          >
                        </div>
                      </div>
                    </div>
                    <div class="modal-body">
                      <div class="row mb-1">
                        <div class="col-6 my-1">{{ $t('Dealer') }}:</div>
                        <div class="col-6">
                          <a
                            :href="'../companies/' + payment_request.company_id + '/details'"
                            class="fw-bold my-auto"
                            >{{ payment_request.company_name }}</a
                          >
                        </div>
                        <div class="col-6 my-1">{{ $t('OD Account No') }}:</div>
                        <div class="col-6">
                          {{ payment_request.payment_account_number }}
                        </div>
                        <div class="col-6 my-1">{{ $t('Anchor') }}:</div>
                        <div class="col-6">
                          <a :href="'../companies/' + payment_request.anchor_id + '/details'" class="fw-bold my-auto">{{
                            payment_request.anchor_name
                          }}</a>
                        </div>
                        <div class="col-6 my-1">{{ $t('Program Name') }}:</div>
                        <div class="col-6">{{ payment_request.program_name }}</div>
                        <div class="col-6 my-1">{{ $t('Invoice / Unique Reference Number') }}:</div>
                        <div class="col-6">{{ payment_request.invoice_number }}</div>
                        <div class="col-6 my-1">{{ $t('PI No') }}.:</div>
                        <div
                          class="col-6 text-primary"
                          style="cursor: pointer"
                          @click="showInvoice(payment_request.invoice_id, 'show-pi-btn-' + payment_request.invoice_id)"
                        >
                          {{ payment_request.pi_number }}
                        </div>
                        <div class="col-6 my-1">{{ $t('Invoice Amount') }}</div>
                        <div class="col-6 text-success">
                          {{ new Intl.NumberFormat().format(payment_request.invoice_total_amount) }}
                        </div>
                        <div class="col-6 my-1">{{ $t('Eligibility') }} (%):</div>
                        <div class="col-6">{{ payment_request.eligibility }}</div>
                        <div class="col-6 my-1">{{ $t('Eligible Drawdown Amount') }}</div>
                        <div class="col-6 text-success">
                          {{ new Intl.NumberFormat().format(payment_request.eligible_for_finance) }}
                        </div>
                        <div class="col-6 my-1">{{ $t('Requested Drawdown Amount') }}:</div>
                        <div class="col-6 text-success">
                          {{ new Intl.NumberFormat().format(payment_request.drawdown_amount) }}
                        </div>
                        <div class="col-6 my-1">{{ $t('Credit to Account No') }}.:</div>
                        <div class="col-6">{{ payment_request.payment_accounts[0].account }}</div>
                        <div class="col-6 my-1">{{ $t('Request Date') }}:</div>
                        <div class="col-6">{{ moment(payment_request.created_at).format(date_format) }}</div>
                        <div class="col-6 my-1">{{ $t('Requested Disbursement Date') }}:</div>
                        <div class="col-6">
                          {{
                            moment(payment_request.payment_request_date).isBefore(moment())
                              ? moment().format(date_format)
                              : moment(payment_request.payment_request_date).format(date_format)
                          }}
                        </div>
                        <div class="col-6 my-1">{{ $t('Discount Rate') }} (%) ({{ 'including Base Rate' }}):</div>
                        <div class="col-6">{{ payment_request.discount_rate }}</div>
                        <div
                          class="col-12"
                          v-if="payment_request.payment_accounts && payment_request.payment_accounts.length > 0"
                        >
                          <div v-for="payment_account in payment_request.payment_accounts" :key="payment_account.id">
                            <div class="row" v-if="payment_account.can_show">
                              <div class="col-6 my-1 d-flex">
                                <div class="">
                                  {{
                                    payment_account.title
                                      ? payment_account.title
                                      : payment_account.type.replaceAll('_', ' ')
                                  }}
                                </div>
                                <div class="" v-if="payment_account.description">
                                  ({{ payment_account.description }})
                                </div>
                              </div>
                              <div class="col-6 text-success">
                                {{ new Intl.NumberFormat().format(Number(payment_account.amount).toFixed(2)) }}
                              </div>
                            </div>
                            <!-- <div class="row" v-if="payment_request.discount_charge_type == 'Front Ended'">
                              <div class="col-6 my-1 d-flex" v-if="payment_account.type != 'Vendor Account'">
                                <div class="">
                                  {{
                                    payment_account.title
                                      ? payment_account.title
                                      : payment_account.type.replaceAll('_', ' ')
                                  }}
                                </div>
                                <div class="" v-if="payment_account.description">
                                  ({{ payment_account.description }})
                                </div>
                              </div>
                              <div class="col-6 text-success" v-if="payment_account.type != 'Vendor Account'">
                                {{ new Intl.NumberFormat().format(Number(payment_account.amount).toFixed(2)) }}
                              </div>
                            </div>
                            <div class="row" v-else>
                              <div
                                class="col-6 my-1 d-flex"
                                v-if="
                                  payment_account.type != 'Vendor Account' &&
                                  !payment_request.discount_transactions.includes(payment_account.type)
                                "
                              >
                                <div class="">
                                  {{
                                    payment_account.title
                                      ? payment_account.title
                                      : payment_account.type.replaceAll('_', ' ')
                                  }}
                                </div>
                                <div class="" v-if="payment_account.description">
                                  ({{ payment_account.description }})
                                </div>
                              </div>
                              <div
                                class="col-6 text-success"
                                v-if="
                                  payment_account.type != 'Vendor Account' &&
                                  !payment_request.discount_transactions.includes(payment_account.type)
                                "
                              >
                                {{ new Intl.NumberFormat().format(Number(payment_account.amount).toFixed(2)) }}
                              </div>
                            </div> -->
                          </div>
                        </div>
                        <div class="col-6 my-1">{{ $t('Net Disbursal Total') }}</div>
                        <div class="col-6 text-success">
                          {{ new Intl.NumberFormat().format(payment_request.amount.toFixed(2)) }}
                        </div>
                        <div class="col-6">{{ $t('Status') }}</div>
                        <div class="col-6">
                          <span
                            class="badge me-1 m_title"
                            :class="resolvePaymentRequestStatus(payment_request.status)"
                            >{{ payment_request.status }}</span
                          >
                        </div>
                        <div class="col-6" v-if="payment_request.approvals.length > 0">{{ $t('Approved By') }}</div>
                        <div class="col-6 my-1" v-if="payment_request.approvals.length > 0">
                          {{ payment_request.approvals[payment_request.approvals.length - 1].user.name }}
                        </div>
                        <div class="col-6" v-if="payment_request.rejected_by">{{ $t('Rejected By') }}</div>
                        <div class="col-6 my-1" v-if="payment_request.rejected_by">
                          {{ payment_request.rejected_by.name }}
                        </div>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">
                        {{ $t('Close') }}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <div
                class="modal fade"
                :id="'approve-payment-request-' + payment_request.id"
                tabindex="-1"
                aria-hidden="true"
              >
                <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Approve Dealer Financing Request') }}</h5>
                      <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        :id="'approve-payment-request-close-btn-' + payment_request.id"
                        aria-label="Close"
                      ></button>
                    </div>
                    <form @submit.prevent="updateStatus(payment_request, 'approved')" method="post">
                      <div class="modal-body">
                        <h5>
                          {{
                            $t(
                              'Are you sure you want to approve the Dealer Financing request with the reference number, '
                            )
                          }}
                          {{ payment_request.reference_number }}?
                        </h5>
                      </div>
                      <div class="modal-footer">
                        <button class="btn btn-primary" type="submit" :disabled="is_updating">
                          {{ !is_updating ? $t('Approve') : $t('Processing...') }}
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
              <div
                class="modal fade"
                :id="'update-payment-request-' + payment_request.id"
                tabindex="-1"
                aria-hidden="true"
              >
                <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">
                        {{ $t('Reject Dealer Financing Request with the reference number, ') }}
                        {{ payment_request.reference_number }}
                      </h5>
                      <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        :id="'reject-payment-request-close-btn-' + payment_request.id"
                        aria-label="Close"
                      ></button>
                    </div>
                    <form @submit.prevent="updateStatus(payment_request, 'rejected')" method="post">
                      <div class="modal-body">
                        <div class="row mb-1">
                          <div class="form-group">
                            <label for="">{{ $t('Rejection Reason') }}</label>
                            <select
                              class="form-control"
                              :id="'rejection-reason-' + payment_request.id"
                              v-model="updateRequestRejectionReason"
                              @change="updateSelectedRejectionReason(payment_request.id)"
                            >
                              <option>{{ $t('Select Option') }}</option>
                              <option
                                v-for="rejection_reason in rejection_reasons"
                                :key="rejection_reason.id"
                                :value="rejection_reason.reason"
                              >
                                {{ rejection_reason.reason }}
                              </option>
                              <option value="other">{{ $t('Other') }}</option>
                            </select>
                            <textarea
                              type="text"
                              class="form-control d-none mt-2"
                              placeholder="Enter Custom Reason"
                              :id="'custom-rejection-reason-' + payment_request.id"
                              v-model="customRequestRejectionReason"
                            ></textarea>
                          </div>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button class="btn btn-primary" type="submit" :disabled="is_updating">
                          {{ !is_updating ? $t('Submit') : $t('Processing...') }}
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
              <div v-if="company_details" class="modal fade" :id="'company-details'" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Company Details') }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="row mb-1">
                        <div class="col-5 d-flex">
                          <div class="">{{ $t('Name') }}:</div>
                          <div class="">
                            {{ company_details.name }}
                          </div>
                        </div>
                        <div class="col-5 d-flex">
                          <div class="">{{ $t('KRA PIN') }}:</div>
                          <div class="">
                            {{ company_details.kra_pin }}
                          </div>
                        </div>
                        <div class="col-5 d-flex">
                          <div class="">{{ $t('Unique Identification No.') }}:</div>
                          <div class="">
                            {{ company_details.unique_identification_number }}
                          </div>
                        </div>
                        <div class="col-5 d-flex">
                          <div class="">{{ $t('Business Identification No.') }}:</div>
                          <div class="">
                            {{ company_details.business_identification_number }}
                          </div>
                        </div>
                        <div class="col-5 d-flex">
                          <div class="">{{ $t('Type') }}:</div>
                          <div class="">
                            {{ company_details.organization_type }}
                          </div>
                        </div>
                        <div class="col-5 d-flex">
                          <div class="">{{ $t('Branch Code') }}:</div>
                          <div class="">
                            {{ company_details.branch_code }}
                          </div>
                        </div>
                        <div class="col-5 d-flex">
                          <div class="">{{ $t('City') }}:</div>
                          <div class="">
                            {{ company_details.city }}
                          </div>
                        </div>
                        <div class="col-5 d-flex">
                          <div class="">{{ $t('Postal Code') }}:</div>
                          <div class="">
                            {{ company_details.postal_code }}
                          </div>
                        </div>
                        <div class="col-5 d-flex">
                          <div class="">{{ $t('Address') }}:</div>
                          <div class="">
                            {{ company_details.address }}
                          </div>
                        </div>
                        <div class="col-5 d-flex">
                          <div class="">{{ $t('Customer Type') }}:</div>
                          <div class="">
                            {{ company_details.customer_type }}
                          </div>
                        </div>
                      </div>
                      <br />
                      <h5>{{ $t('Relationship Managers') }}</h5>
                      <div
                        class="row my-1"
                        v-for="relationship_manager in company_details.relationship_managers"
                        :key="relationship_manager.id"
                      >
                        <div class="col-4">
                          <div class="d-flex">
                            <div class="">{{ $t('Name') }}:</div>
                            <div class="">
                              {{ relationship_manager.name }}
                            </div>
                          </div>
                        </div>
                        <div class="col-4">
                          <div class="d-flex">
                            <div class="">{{ $t('Email') }}:</div>
                            <div class="">
                              {{ relationship_manager.email }}
                            </div>
                          </div>
                        </div>
                        <div class="col-4">
                          <div class="d-flex">
                            <div class="">{{ $t('Phone') }}:</div>
                            <div class="">
                              {{ relationship_manager.phone_number }}
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">
                        {{ $t(' Close') }}
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
  </div>
</template>
<script>
import { computed, inject, onMounted, ref, watch, nextTick } from 'vue';
import axios from 'axios';
// Notification
import { useToast } from 'vue-toastification';
import moment from 'moment';
import Pagination from './partials/Pagination.vue';
import InvoiceDetails from '../InvoiceDetails.vue';
import PaymentInstruction from '../PaymentInstruction.vue';

export default {
  name: 'DealerFinancing',
  components: {
    Pagination,
    InvoiceDetails,
    PaymentInstruction
  },
  props: ['bank', 'date_format'],
  setup(props) {
    const date_format = props.date_format;
    const toast = useToast();
    const base_url = inject('baseURL');
    const bank_url = props.bank;
    const requests = ref([]);
    const updateRequestStatus = ref('');
    const updateRequestRejectionReason = ref('');
    const selected_requests = ref([]);
    const bulk_action = ref('');
    const showBulkRejectionReasonModal = ref(false);
    const showRejectionModal = ref(null);
    const showApprovalModal = ref(null);
    const approveRequestsTotalAmount = ref(0);
    const approveRequests = ref([]);

    // Search fields
    const payment_reference_number_search = ref('');
    const invoice_number_search = ref('');
    const status_search = ref('');
    const vendor_search = ref('');
    const anchor_search = ref('');
    const sort_by = ref('');
    const request_date_search = ref('');

    const customRequestRejectionReason = ref('');
    const rejection_reasons = ref([]);

    // Pagination
    const per_page = ref(5);

    const invoice_details = ref(null);

    const company_details = ref(null);

    const is_updating = ref(false);

    const invoice_details_key = ref(0);

    const getRequests = async () => {
      await axios
        .get(
          base_url +
            props.bank +
            '/requests/dealer-financing-requests/data?per_page=' +
            per_page.value +
            '&type=dashboard'
        )
        .then(response => {
          requests.value = response.data.payment_requests;
          rejection_reasons.value = response.data.rejection_reasons;
        })
        .catch(err => {
          console.log(err);
        });
    };

    const updateSelected = id => {
      if (selected_requests.value.includes(id)) {
        const index = selected_requests.value.indexOf(id);
        selected_requests.value.splice(index, 1);
      } else {
        selected_requests.value.push(id);
      }
    };

    const selectAll = () => {
      selected_requests.value = [];
      if (!document.getElementById('select-all').checked) {
        requests.value.data.forEach(request => {
          if (
            request.user_can_approve &&
            !request.user_has_approved &&
            document.getElementById('payment-request-' + request.id).checked == true
          ) {
            document.getElementById('payment-request-' + request.id).checked = false;
            let f;
            let index = selected_requests.value.filter(function (id, index) {
              f = index;
              id == request.id;
            });
            if (!index) {
              return false;
            }
            selected_requests.value.splice(f, 1);
          }
        });
      } else {
        requests.value.data.forEach(request => {
          if (request.user_can_approve && !request.user_has_approved) {
            document.getElementById('payment-request-' + request.id).checked = true;
            selected_requests.value.push(request.id);
          }
        });
      }
    };

    const submitBulkAction = action => {
      if (selected_requests.value.length <= 0) {
        toast.error('Select requests');
        return;
      }

      const formData = new FormData();
      if (action == 'rejected') {
        selected_requests.value.forEach(request => {
          formData.append('requests[]', request);
        });
        formData.append('rejection_reason', updateRequestRejectionReason.value);
        formData.append('custom_rejection_reason', customRequestRejectionReason.value);
        formData.append('status', 'rejected');
      }
      if (action == 'approve') {
        selected_requests.value.forEach(request => {
          formData.append('requests[]', request);
        });
        formData.append('status', 'approved');
      }

      axios
        .post(base_url + props.bank + '/requests/financing-requests/update', formData)
        .then(() => {
          toast.success('Financing Requests updated');
          bulk_action.value = '';
          // getRequests();
          setTimeout(() => {
            window.location.reload();
          }, 3000);
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    watch(bulk_action, newVal => {
      if (selected_requests.value.length > 0) {
        if (newVal == 'rejected') {
          showRejectionModal.value.click();
        }
        approveRequests.value = [];
        approveRequestsTotalAmount.value = 0;
        if (newVal == 'approve') {
          selected_requests.value.forEach(selected_request => {
            approveRequests.value.push(requests.value.data.filter(request => request.id == selected_request)[0]);
          });
          approveRequests.value.forEach(selected => {
            approveRequestsTotalAmount.value += selected.amount;
          });
          showApprovalModal.value.click();
        }
      } else {
        toast.error('Select Payment Requests');
      }
    });

    watch([per_page], async ([per_page]) => {
      await axios
        .get(base_url + props.bank + '/requests/dealer-financing-requests/data', {
          params: {
            per_page: per_page
          }
        })
        .then(response => {
          requests.value = response.data.payment_requests;
        })
        .catch(err => {
          console.log(err);
        });
    });

    const filter = async () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + props.bank + '/requests/dealer-financing-requests/data', {
          params: {
            per_page: per_page.value,
            invoice_number: invoice_number_search.value,
            payment_reference_number: payment_reference_number_search.value,
            vendor: vendor_search.value,
            anchor: anchor_search.value,
            status: status_search.value,
            sort_by: sort_by.value,
            request_date: request_date_search.value
          }
        })
        .then(response => {
          requests.value = response.data.payment_requests;
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = async () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      invoice_number_search.value = '';
      status_search.value = '';
      payment_reference_number_search.value = '';
      invoice_number_search.value = '';
      vendor_search.value = '';
      anchor_search.value = '';
      status_search.value = '';
      sort_by.value = '';
      request_date_search.value = '';
      await axios
        .get(base_url + props.bank + '/requests/dealer-financing-requests/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          requests.value = response.data.payment_requests;
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    const exportReport = () => {
      axios
        .get(base_url + props.bank + '/requests/portfolio/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            type: 'Dealer Financing',
            invoice_number: invoice_number_search.value,
            status: status_search.value,
            payment_reference_number: payment_reference_number_search.value,
            invoice_number: invoice_number_search.value,
            vendor: vendor_search.value,
            anchor: anchor_search.value,
            status: status_search.value,
            sort_by: sort_by.value,
            request_date: request_date_search.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Payment_Requests_${moment().format('Do_MMM_YYYY')}.xlsx`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    onMounted(() => {
      getRequests();
    });

    const resolvePaymentRequestStatus = status => {
      let style = '';
      switch (status) {
        case 'Pending Maker':
          style = 'bg-label-primary';
          break;
        case 'Pending Checker':
          style = 'bg-label-warning';
          break;
        case 'Created':
          style = 'bg-label-secondary';
          break;
        case 'Pending':
          style = 'bg-label-secondary';
          break;
        case 'Approved':
          style = 'bg-label-success';
          break;
        case 'Paid':
          style = 'bg-label-success';
          break;
        case 'Failed':
          style = 'bg-label-danger';
          break;
        case 'Rejected':
          style = 'bg-label-danger';
          break;
        case 'Past Due':
          style = 'bg-label-danger';
          break;
        default:
          style = 'bg-label-primary';
          break;
      }

      return style;
    };

    const resolveFinancingStatus = status => {
      let style = '';
      switch (status) {
        case 'pending':
          style = 'bg-label-primary';
          break;
        case 'financed':
          style = 'bg-label-primary';
          break;
          case 'disbursed':
          style = 'bg-label-primary';
          break;
        case 'closed':
          style = 'bg-label-success';
          break;
        case 'denied':
          style = 'bg-label-danger';
          break;
        default:
          style = 'bg-label-primary';
          break;
      }
      return style;
    };

    const updateSelectedRejectionReason = id => {
      let val = document.getElementById('rejection-reason-' + id).value;
      if (val == 'other') {
        document.getElementById('custom-rejection-reason-' + id).classList.remove('d-none');
      } else {
        document.getElementById('custom-rejection-reason-' + id).classList.add('d-none');
        document.getElementById('custom-rejection-reason-' + id).value = '';
      }
    };

    const bulkUpdateRejectionReason = () => {
      let val = document.getElementById('rejection-reason').value;
      if (val == 'other') {
        document.getElementById('custom-rejection-reason').classList.remove('d-none');
      } else {
        document.getElementById('custom-rejection-reason').classList.add('d-none');
        document.getElementById('custom-rejection-reason').value = '';
      }
    };

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            type: 'Dealer Financing',
            invoice_number: invoice_number_search.value,
            status: status_search.value,
            payment_reference_number: payment_reference_number_search.value,
            invoice_number: invoice_number_search.value,
            vendor: vendor_search.value,
            anchor: anchor_search.value,
            status: status_search.value,
            sort_by: sort_by.value,
            request_date: request_date_search.value
          }
        })
        .then(response => {
          requests.value = response.data.payment_requests;
        });
    };

    const updateStatus = (payment_request, status) => {
      is_updating.value = true;
      const formData = new FormData();
      formData.append('payment_request_id', payment_request.id);
      formData.append('status', status);
      formData.append('rejection_reason', updateRequestRejectionReason.value);
      formData.append('custom_rejection_reason', customRequestRejectionReason.value);
      axios
        .post(base_url + props.bank + '/requests/payment-requests/update', formData)
        .then(() => {
          toast.success('Dealer Financing Request updated successfully');
          getRequests();
          if (status == 'approved') {
            document.getElementById('approve-payment-request-close-btn-' + payment_request.id).click();
          } else {
            document.getElementById('reject-payment-request-close-btn-' + payment_request.id).click();
          }
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        })
        .finally(() => {
          is_updating.value = false;
        });
    };

    const showInvoice = async (invoice, modal) => {
      document.getElementById('loading-modal-btn').click();
      await axios
        .get(base_url + props.bank + '/requests/invoices/' + invoice + '/details')
        .then(response => {
          invoice_details.value = response.data;
          invoice_details_key.value++;
          nextTick(() => {
            document.getElementById(modal).click();
          });
        })
        .catch(err => {
          console.log(err);
        });
    };

    const getTaxPercentage = (invoice_amount, tax_amount) => {
      return ((tax_amount / invoice_amount) * 100).toFixed(2);
    };

    const showCompany = async (company, modal) => {
      document.getElementById('loading-modal-btn').click();
      await axios
        .get(base_url + props.bank + '/companies/' + company + '/details')
        .then(response => {
          company_details.value = response.data.company;
          nextTick(() => {
            document.getElementById(modal).click();
          });
        })
        .catch(err => {
          console.log(err);
        });
    };

    return {
      moment,
      bank_url,
      requests,
      selected_requests,
      bulk_action,
      showBulkRejectionReasonModal,
      showRejectionModal,
      showApprovalModal,
      approveRequests,
      approveRequestsTotalAmount,
      updateRequestStatus,
      updateRequestRejectionReason,

      // Search fields
      payment_reference_number_search,
      invoice_number_search,
      status_search,
      anchor_search,
      vendor_search,
      sort_by,
      request_date_search,

      filter,
      refresh,

      rejection_reasons,
      customRequestRejectionReason,
      bulkUpdateRejectionReason,
      updateSelectedRejectionReason,

      // Pagination
      per_page,

      invoice_details,

      company_details,

      showCompany,

      selectAll,
      resolvePaymentRequestStatus,
      changePage,
      resolveFinancingStatus,
      updateStatus,
      updateSelected,
      submitBulkAction,
      exportReport,
      showInvoice,
      getTaxPercentage,

      date_format,
      is_updating,
      invoice_details_key
    };
  }
};
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
