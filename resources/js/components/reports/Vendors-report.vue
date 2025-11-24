<template>
  <div class="card p-2" id="companies">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="w-75 row">
        <div class="col-3">
          <input
            type="text"
            class="form-control"
            v-model="name_search"
            id="defaultFormControlInput"
            placeholder="Company Name"
            aria-describedby="defaultFormControlHelp"
          />
        </div>
        <div class="col-3">
          <select class="form-select" id="exampleFormControlSelect" v-model="bulk_action">
            <option value="">{{ $t('Bulk Actions') }}</option>
            <option value="active">{{ $t('Activate') }}</option>
            <option value="inactive">{{ $t('Deactivate') }}</option>
          </select>
        </div>
        <button
          class="d-none"
          ref="showApprovalModal"
          data-bs-toggle="modal"
          :data-bs-target="'#bulk-approval-modal'"
        ></button>
        <div class="modal fade" id="bulk-approval-modal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="modalCenterTitle">{{ $t('Update Statuses to Active') }}</h5>
                <button
                  type="button"
                  class="btn-close"
                  id="close-activate-modal"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                ></button>
              </div>
              <form method="post" @submit.prevent="submitBulkAction('active')">
                <div class="modal-body">
                  <h4>{{ $t('Are you sure you want to activate these companies in the program') }}?</h4>
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
          <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="modalCenterTitle">{{ $t('Update Statuses to Inactive') }}</h5>
                <button
                  type="button"
                  class="btn-close"
                  id="close-deactivate-modal"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                ></button>
              </div>
              <form method="post" @submit.prevent="submitBulkAction('inactive')">
                <div class="modal-body">
                  <h4>{{ $t('Are you sure you want to deactivate these companies in the program') }}?</h4>
                </div>
                <div class="modal-footer">
                  <button class="btn btn-primary" type="submit">{{ $t('Confirm') }}</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      <div class="d-flex justify-content-md-end mt-2 mt-md-auto gap-1">
        <div class="">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page" style="height: fit-content">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <!-- <div class="mx-2">
          <button type="button" class="btn btn-primary" style="height: fit-content">
            <i class="ti ti-download ti-sm"></i>
          </button>
        </div> -->
      </div>
    </div>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('Name') }}</th>
            <th>{{ $t('Payment/OD Account') }}</th>
            <th>{{ $t('Sanctioned Limit') }}</th>
            <th>{{ $t('Utilized Amount') }}</th>
            <th>{{ $t('Pipeline Requests') }}</th>
            <th>{{ $t('Available Limit') }}</th>
            <th>{{ $t('Status') }}</th>
            <th>{{ $t('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr class="text-nowrap" v-for="company in companies.data" :key="company.id">
            <td class="text-primary text-decoration-underline">
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
            </td>
            <td class="">{{ company.vendor_configuration.payment_account_number }}</td>
            <td class="">{{ new Intl.NumberFormat().format(company.vendor_configuration.sanctioned_limit) }}</td>
            <td class="">{{ new Intl.NumberFormat().format(company.utilized_amount) }}</td>
            <td class="">{{ new Intl.NumberFormat().format(company.pipeline_amount) }}</td>
            <td class="">
              {{
                new Intl.NumberFormat().format(company.vendor_configuration.sanctioned_limit - company.utilized_amount)
              }}
            </td>
            <td>
              <span :class="'m_title badge ' + resolveStatus(company.vendor_configuration.status)">{{
                company.vendor_configuration.status
              }}</span>
            </td>
            <td class="d-flex">
              <span data-bs-toggle="modal" :data-bs-target="'#vendor-mapping-details-' + company.id">
                <i class="ti ti-eye ti-sm text-primary" style="cursor: pointer" title="View Mapping Details"></i>
              </span>
              <div class="modal modal-top fade modal-xl" :id="'vendor-mapping-details-' + company.id" tabindex="-1">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalTopTitle">{{ company.name }} {{ $t('Mapping details') }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="row">
                        <div class="col-sm-4 text-align-center d-flex justify-content-between">
                          <h6 class="mr-2 fw-light">{{ $t('Sanctioned Limit') }}:</h6>
                          <h5 class="px-2 text-right">
                            {{ new Intl.NumberFormat().format(company.vendor_configuration.sanctioned_limit) }}
                          </h5>
                        </div>
                        <div class="col-sm-4 text-align-center d-flex justify-content-between">
                          <h6 class="mr-2 fw-light">{{ $t('Eligibility') }}:</h6>
                          <h5 class="px-2 text-right">{{ company.vendor_configuration.eligibility }}%</h5>
                        </div>
                        <div class="col-sm-4 text-align-center d-flex justify-content-between">
                          <h6 class="mr-2 fw-light">{{ $t('Payment Account') }}:</h6>
                          <h5 class="px-2 text-right">
                            {{ company.vendor_configuration.payment_account_number }}
                          </h5>
                        </div>
                        <div class="col-sm-4 text-align-center d-flex justify-content-between">
                          <h6 class="mr-2 fw-light">{{ $t('Limit Approval Date') }}:</h6>
                          <h5 class="px-2 text-right">
                            {{ moment(company.vendor_configuration.limit_approved_date).format('Do MMM YYYY') }}
                          </h5>
                        </div>
                        <div class="col-sm-4 text-align-center d-flex justify-content-between">
                          <h6 class="mr-2 fw-light">{{ $t('Limit Expiry Date') }}:</h6>
                          <h5 class="px-2 text-right">
                            {{ moment(company.vendor_configuration.limit_expiry_date).format('Do MMM YYYY') }}
                          </h5>
                        </div>
                        <div class="col-sm-4 text-align-center d-flex justify-content-between">
                          <h6 class="mr-2 fw-light">{{ $t('Limit Review Date') }}:</h6>
                          <h5 class="px-2 text-right">
                            {{ moment(company.vendor_configuration.limit_review_date).format('Do MMM YYYY') }}
                          </h5>
                        </div>
                        <div class="col-sm-4 text-align-center d-flex justify-content-between">
                          <h6 class="mr-2 fw-light">{{ $t('Request Auto Finance') }}:</h6>
                          <h5 class="px-2 text-right">
                            {{ company.vendor_configuration.request_auto_finance ? 'Yes' : 'No' }}
                          </h5>
                        </div>
                      </div>
                      <hr />
                      <div class="">
                        <h5>{{ $t('Discounts') }}</h5>
                      </div>
                      <div class="">
                        <div class="table-responsive">
                          <table class="table">
                            <thead>
                              <tr>
                                <th>{{ $t('Benchmark Rate') }}</th>
                                <th>{{ $t('Business Strategy Spread') }}</th>
                                <th>{{ $t('Credit Spread') }}</th>
                                <th>{{ $t('Total Spread') }}</th>
                                <th>{{ $t('Total ROI') }}</th>
                                <th v-if="vendors_type == 'vendors' || vendors_type == 'buyers'">
                                  {{ $t('Anchor Discount Bearing') }}
                                </th>
                                <th v-if="vendors_type == 'vendors' || vendors_type == 'buyers'">
                                  {{ $t('Vendor Discount Bearing') }}
                                </th>
                              </tr>
                            </thead>
                            <tbody class="table-border-bottom-0 text-nowrap">
                              <tr v-for="discount_detail in company.vendor_discount_details" :key="discount_detail.id">
                                <td>{{ discount_detail.benchmark_rate }}%</td>
                                <td>{{ discount_detail.business_strategy_spread }}%</td>
                                <td>{{ discount_detail.credit_spread }}%</td>
                                <td>{{ discount_detail.total_spread }}%</td>
                                <td>{{ discount_detail.total_roi }}%</td>
                                <td v-if="vendors_type == 'vendors' || vendors_type == 'buyers'">
                                  {{ discount_detail.vendor_discount_bearing }}%
                                </td>
                                <td v-if="vendors_type == 'vendors' || vendors_type == 'buyers'">
                                  {{ discount_detail.anchor_discount_bearing }}%
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                      <hr />
                      <div class="py-1r">
                        <h5>{{ $t('Fees') }}</h5>
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
                                  {{ $t('Vendor Bearing') }}
                                </th>
                                <th v-if="vendors_type == 'vendors' || vendors_type == 'buyers'">
                                  {{ $t('Anchor Bearing') }}
                                </th>
                                <th v-if="vendors_type == 'dealers'">{{ $t('Dealer Bearing') }}</th>
                              </tr>
                            </thead>
                            <tbody class="table-border-bottom-0 text-nowrap">
                              <tr v-for="fee in company.vendor_fee_details" :key="fee.id">
                                <td>{{ fee.fee_name }}</td>
                                <td class="m_title">{{ fee.type }}</td>
                                <td>{{ fee.value }}</td>
                                <td v-if="vendors_type == 'vendors' || vendors_type == 'buyers'">
                                  {{ fee.vendor_bearing_discount }}
                                </td>
                                <td v-if="vendors_type == 'vendors' || vendors_type == 'buyers'">
                                  {{ fee.anchor_bearing_discount }}
                                </td>
                                <td v-if="vendors_type == 'dealers'">{{ fee.dealer_bearing }}</td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                      <hr />
                      <div class="py-1r">
                        <h5>{{ $t('Bank Accounts Details') }}</h5>
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
  name: 'VendorReport',
  props: ['bank', 'program', 'type'],
  components: {
    RouterLink,
    Pagination
  },
  setup(props) {
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
        .get(base_url + props.bank + '/reports/' + props.program + '/vendors/data?per_page=' + per_page.value)
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
          if (document.getElementById('company-' + company.id).checked == true) {
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
          document.getElementById('company-' + company.id).checked = true;
          selected_companies.value.push(company.id);
        });
      }
    };

    const resolveStatus = status => {
      let style = '';
      switch (status) {
        case 'active':
          style = 'bg-label-success';
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

    watch([name_search, per_page], ([new_name_search, new_per_page]) => {
      axios
        .get(base_url + props.bank + '/reports/' + props.program + '/vendors/data', {
          params: {
            per_page: new_per_page,
            name: new_name_search
          }
        })
        .then(response => {
          companies.value = response.data;
        });
    });

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
        })
        .catch(err => {
          console.log(err);
        });
    };

    const updateApprovalStatus = (company, status) => {
      axios
        .post(`companies/${company}/status/update`, {
          status: status,
          rejection_reason: rejection_reason.value
        })
        .then(() => {
          getCompanies();
          toast.success('Company status updated');
          setTimeout(() => {
            window.location.reload();
          }, 3000);
        })
        .catch(err => {
          console.log(err);
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

      program_id,
      vendors_type,

      // Search fields
      name_search,

      // Pagination
      per_page,

      bulk_action,

      showRejectionModal,
      showApprovalModal,

      selectAll,
      updateSelected,
      resolveStatus,
      updateActiveStatus,
      submitBulkAction,
      updateApprovalStatus,
      changePage,
      hasAnchorRole
    };
  }
};
</script>

<style>
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
