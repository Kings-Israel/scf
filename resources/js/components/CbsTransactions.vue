<template>
  <div class="">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <h5>{{ $t('CBS Transactions List') }}</h5>
      <div class="d-flex gap-1 justify-content-end">
        <button
          type="button"
          class="btn btn-primary h-fit"
          style="height: fit-content"
          @click="downloadCreatedCbsTransactions()"
          title="Download Created Transactions"
        >
          {{ $t('Download Transctions') }}
        </button>
        <button
          v-if="can_upload"
          type="button"
          class="btn btn-primary h-fit"
          style="height: fit-content"
          data-bs-toggle="modal"
          data-bs-target="#uploadCbsTransactions"
        >
          {{ $t('Upload Transactions') }}
        </button>
        <button
          type="button"
          class="btn btn-primary d-none"
          style="height: fit-content"
          data-bs-toggle="modal"
          data-bs-target="#createCbsTransactions"
        >
          {{ $t('Initiate Transaction') }}
        </button>
        <div class="modal modal-top fade" id="createCbsTransactions" tabindex="-1">
          <div class="modal-dialog">
            <form class="modal-content" method="POST" @submit.prevent="submitNewTransactions">
              <div class="modal-header">
                <h5 class="modal-title" id="modalTopTitle">{{ $t('New Transaction') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="row mb-1">
                  <div class="form-group">
                    <label for="" class="form-label">{{ $t('Credit To') }}</label>
                    <input type="text" class="form-control" v-model="addCreditTo" />
                  </div>
                  <div class="form-group">
                    <label for="" class="form-label">{{ $t('Credit To Account Name') }}</label>
                    <input type="text" class="form-control" v-model="addCreditToName" />
                  </div>
                  <div class="form-group">
                    <label for="" class="form-label">{{ $t('Debit From') }}</label>
                    <input type="text" class="form-control" v-model="addDebitFrom" />
                  </div>
                  <div class="form-group">
                    <label for="" class="form-label">{{ $t('Debit From Account Name') }}</label>
                    <input type="text" class="form-control" v-model="addDebitFromName" />
                  </div>
                  <div class="form-group">
                    <label for="Amount" class="form-label">{{ $t('Amount') }}</label>
                    <input type="number" min="1" class="form-control" v-model="addAmount" />
                  </div>
                  <div class="form-group">
                    <label for="" class="form-label">{{ $t('Transaction Reference') }}</label>
                    <input type="text" class="form-control" v-model="addTransactionReference" />
                  </div>
                  <div class="form-group">
                    <label for="" class="form-label">{{ $t('Pay Date') }}</label>
                    <input
                      type="date"
                      name="pay_date"
                      class="form-control"
                      id="html5-date-input"
                      v-model="addPayDate"
                    />
                  </div>
                  <div class="form-group">
                    <label for="Status" class="form-label">{{ $t('Status') }}</label>
                    <select name="status" id="" class="form-select" v-model="addStatus">
                      <option value="Successful">{{ $t('Successful') }}</option>
                      <option value="Created">{{ $t('Created') }}</option>
                      <option value="Failed">{{ $t('Failed') }}</option>
                      <option value="Permanently Failed">{{ $t('Permanently Failed') }}</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="Status" class="form-label">{{ $t('Transaction Type') }}</label>
                    <select name="status" id="" class="form-select" v-model="addTransactionType">
                      <option value="Payment Disbursement">{{ $t('Payment Disbursement') }}</option>
                      <option value="Fees/Charges">{{ $t('Fees/Charges') }}</option>
                      <option value="Discount Charge">{{ $t('Discount Charge') }}</option>
                      <option value="Bank Invoice Payment">{{ $t('Bank Invoice Payment') }}</option>
                      <option value="Repayment">{{ $t('Repayment') }}</option>
                      <option value="Funds Transfer">{{ $t('Funds Transfer') }}</option>
                      <option value="OD Drawdown">{{ $t('OD Drawdown') }}</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal" :disabled="!can_close">
                  {{ $t('Close') }}
                </button>
                <button type="submit" class="btn btn-primary" :disabled="!can_submit">
                  {{ can_submit ? $t('Submit') : $t('Processing') }}
                </button>
              </div>
            </form>
          </div>
        </div>
        <div class="modal modal-top fade" id="uploadCbsTransactions" tabindex="-1">
          <div class="modal-dialog">
            <form class="modal-content" method="POST" @submit.prevent="submitTransactions">
              <div class="modal-header">
                <h5 class="modal-title" id="modalTopTitle">{{ $t('Upload CBS Transactions') }}</h5>
                <button
                  type="button"
                  class="btn-close"
                  id="close-upload-modal"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                ></button>
              </div>
              <div class="modal-body">
                <div class="form-group">
                  <input
                    class="form-control"
                    id="file-upload"
                    name="documents"
                    ref="fileUpload"
                    type="file"
                    accept=".xlsx"
                    @change="updatedFile"
                    :key="selected_file"
                  />
                </div>
                <div v-if="uploadErrors.length > 0">
                  <ul>
                    <li v-for="error in uploadErrors" :key="error" class="text-danger">{{ error }}</li>
                  </ul>
                </div>
                <a
                  href="cbs-transactions/error-report/export"
                  v-if="show_error_download_link"
                  class="btn btn-danger btn-sm mt-2"
                >
                  {{ $t('Click Here to Download Error Report') }}
                </a>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal" :disabled="!can_close">
                  {{ $t('Close') }}
                </button>
                <button type="submit" class="btn btn-primary" id="upload-cbs-btn" :disabled="!can_submit">
                  {{ can_submit ? $t('Submit') : $t('Processing') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <form @submit.prevent="filter">
        <div class="d-flex flex-wrap gap-1">
          <div class="">
            <label for="CBS ID" class="form-label mx-1">{{ $t('CBS ID') }}</label>
            <input
              type="text"
              class="form-control form-search"
              id="defaultFormControlInput"
              placeholder="CBS ID"
              v-model="cbs_id_search"
              aria-describedby="defaultFormControlHelp"
            />
          </div>
          <div class="">
            <label for="Debit From" class="form-label">{{ $t('Debit From/Credit To') }}</label>
            <input
              type="text"
              class="form-control form-search"
              id="defaultFormControlInput"
              placeholder="Debit From/Credit To"
              v-model="account_search"
              aria-describedby="defaultFormControlHelp"
            />
          </div>
          <div class="">
            <label for="Invoice Number" class="form-label">{{ $t('Invoice Number') }}</label>
            <input
              type="text"
              class="form-control form-search"
              id="defaultFormControlInput"
              placeholder="Invoice/Unique Ref"
              v-model="invoice_number_search"
              aria-describedby="defaultFormControlHelp"
            />
          </div>
          <div class="">
            <label for="Status" class="form-label">{{ $t('Status') }}</label>
            <select class="form-select form-search select2" multiple id="cbs-status-select" v-model="status_search">
              <!-- <option value="">{{ $t('Status') }}</option> -->
              <option value="Created">{{ $t('Created') }}</option>
              <option value="Successful">{{ $t('Successful') }}</option>
              <option value="Permanently Failed">{{ $t('Permanently Failed') }}</option>
              <option value="Failed">{{ $t('Failed') }}</option>
            </select>
          </div>
          <div class="">
            <label for="Program Type" class="form-label">{{ $t('Program Type') }}</label>
            <select class="form-select form-search" id="exampleFormControlSelect" v-model="product_type_search">
              <option value="vendor_financing_receivable">{{ $t('Vendor Financing Receivable') }}</option>
              <option value="factoring_with_recourse">{{ $t('Factoring With Recourse') }}</option>
              <option value="factoring_without_recourse">{{ $t('Factoring Without Recourse') }}</option>
              <option value="dealer_financing">{{ $t('Dealer Financing') }}</option>
            </select>
          </div>
          <div class="">
            <label class="form-label" for="">{{ $t('Date') }} ({{ $t('Pay Date') }})</label>
            <input
              type="text"
              id="cbs_date_search"
              class="form-control form-search"
              name="cbs_daterange"
              placeholder="Select Dates"
              autocomplete="off"
            />
          </div>
          <div class="">
            <label class="form-label" for="">{{ $t('Date') }} ({{ $t('Transaction Date') }})</label>
            <input
              type="text"
              id="cbs_transaction_date_search"
              class="form-control form-search"
              name="cbs_transaction_daterange"
              placeholder="Select Dates"
              autocomplete="off"
            />
          </div>
          <div class="form-group">
            <label for="Status" class="form-label">{{ $t('Transaction Type') }}</label>
            <select class="form-select select2" multiple id="transaction-type-select" v-model="transaction_type">
              <option value="">{{ $t('Select Transaction Type') }}</option>
              <option value="Balance DF Invoice Payment">{{ $t('Balance DF Invoice Payment') }}</option>
              <option value="Bank Invoice Payment">{{ $t('Bank Invoice Payment') }}</option>
              <option value="Discount Charge">{{ $t('Discount Charge') }}</option>
              <option value="Fees/Charges">{{ $t('Fees/Charges') }}</option>
              <option value="Funds Transfer">{{ $t('Funds Transfer') }}</option>
              <option value="OD Account Debit">{{ $t('OD Account Debit') }}</option>
              <option value="OD Drawdown">{{ $t('OD Drawdown') }}</option>
              <option value="Overdue Account">{{ $t('Overdue Account') }}</option>
              <option value="Payment Disbursement">{{ $t('Payment Disbursement') }}</option>
              <option value="Platform Charges">{{ $t('Platform Charges') }}</option>
              <option value="Repayment">{{ $t('Repayment') }}</option>
              <!-- <option value="Advanced Discount Settlement">{{ $t('Advanced Discount Settlement') }}</option>
              <option value="Unrealized Discount Settlement">{{ $t('Unrealized Discount Settlement') }}</option> -->
            </select>
          </div>
          <div class="">
            <label for="Sort by" class="form-label">{{ $t('Sort By') }}</label>
            <select class="form-select form-search" id="exampleFormControlSelect" v-model="sort_by">
              <option value="">{{ $t('Sort By') }}</option>
              <option value="asc">{{ $t('Ascending') }}</option>
              <option value="desc">{{ $t('Descending') }}</option>
            </select>
          </div>
          <div class="table-search-btn">
            <button class="btn btn-primary btn-md" type="submit"><i class="ti ti-search"></i></button>
          </div>
          <div class="table-clear-btn">
            <button class="btn btn-primary btn-md" type="button" @click="refresh"><i class="ti ti-refresh"></i></button>
          </div>
        </div>
      </form>
      <div class="d-flex justify-content-md-end mt-2 mt-md-auto gap-1">
        <div class="">
          <select
            class="form-select"
            id="exampleFormControlSelect1"
            v-model="per_page"
            style="height: fit-content; width: 5rem"
          >
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <div class="d-flex gap-1">
          <button
            type="button"
            class="btn btn-primary h-fit"
            style="height: fit-content"
            @click="downloadCbsTransactions()"
            title="Download Filtered Transactions"
          >
            <i class="ti ti-download ti-xs px-1 filtered"></i>
            {{ $t('Excel') }}
          </button>
        </div>
      </div>
    </div>
    <pagination
      v-if="transactions.meta"
      class="mx-2"
      :from="transactions.meta.from"
      :to="transactions.meta.to"
      :links="transactions.meta.links"
      :next_page="transactions.links.next"
      :prev_page="transactions.links.prev"
      :total_items="transactions.meta.total"
      :first_page_url="transactions.links.first"
      :last_page_url="transactions.links.last"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th class="px-1">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" value="" id="select-all" @change="selectAll()" />
              </div>
            </th>
            <th class="">{{ $t('CBS ID') }}</th>
            <th class="">{{ $t('Debit From') }}</th>
            <th class="">{{ $t('Credit To') }}</th>
            <th class="">{{ $t('Amount') }}</th>
            <th class="">{{ $t('Invoice / Unique Ref No') }}.</th>
            <th class="">{{ $t('Pay Date') }}</th>
            <th class="">{{ $t('Transaction Date') }}</th>
            <th class="">{{ $t('Product Type') }}</th>
            <th class="">{{ $t('Transaction Type') }}</th>
            <th class="">{{ $t('Status') }}</th>
            <th>{{ $t('Actions') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr v-if="!transactions.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="transactions.data && transactions.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr v-for="transaction in transactions.data" :key="transaction">
            <td class="px-1">
              <div class="form-check">
                <input
                  class="form-check-input"
                  type="checkbox"
                  value=""
                  :id="'transaction-' + transaction.id"
                  @change="updateSelected(transaction.id)"
                />
              </div>
            </td>
            <td class="">
              <span class="fw-medium">{{ transaction.id }}</span>
            </td>
            <td class="">
              <span>{{ transaction.debit_from_account }}</span>
            </td>
            <td class="">
              <span>{{ transaction.credit_to_account }}</span>
            </td>
            <td class="text-success text-nowrap">
              {{ transaction.currency }} {{ new Intl.NumberFormat().format(transaction.amount) }}
            </td>
            <td style="cursor: pointer">
              <span
                v-if="transaction.payment_request && transaction.payment_request.invoice_number"
                class="text-primary text-decoration-underline"
                @click="
                  showInvoice(
                    transaction.payment_request.invoice_id,
                    'show-cbs-details-btn-' + transaction.payment_request.invoice_id
                  )
                "
              >
                {{ transaction.payment_request.invoice_number }}
              </span>
              <span v-else>-</span>
            </td>
            <td v-if="transaction.pay_date" class="">
              {{ moment(transaction.pay_date).format(date_format) }}
            </td>
            <td v-else class="">-</td>
            <td class="" v-if="transaction.status == 'Successful'">
              {{ moment(transaction.transaction_date).format(date_format) }}
            </td>
            <td class="" v-else>-</td>
            <td class="m_title">{{ transaction.product }}</td>
            <td class="">{{ transaction.transaction_type }}</td>
            <td class="">
              <span class="badge me-1" :class="resolvePaymentRequestStatus(transaction.status)">{{
                transaction.status
              }}</span>
            </td>
            <td>
              <div class="d-flex">
                <i
                  class="ti ti-eye ti-sm text-primary"
                  data-bs-toggle="modal"
                  :data-bs-target="'#cbs-transactions-details-' + transaction.id"
                  style="cursor: pointer"
                ></i>
                <i
                  v-if="can_update && transaction.status != 'Successful'"
                  class="ti ti-pencil ti-sm text-warning mx-1"
                  @click="showEditModal(transaction)"
                  data-bs-toggle="modal"
                  :data-bs-target="'#cbs-transactions-' + transaction.id"
                  style="cursor: pointer"
                ></i>
              </div>
            </td>
            <td>
              <button
                v-if="transaction.payment_request && transaction.payment_request.invoice_number"
                class="d-none"
                :id="'show-cbs-details-btn-' + transaction.payment_request.invoice_id"
                data-bs-toggle="modal"
                :data-bs-target="'#cbs-invoice-' + transaction.payment_request.invoice_id"
              ></button>
              <button
                v-if="transaction.payment_request && transaction.payment_request.invoice_number"
                class="d-none"
                :id="'show-cbs-pi-btn-' + transaction.payment_request.invoice_id"
                data-bs-toggle="modal"
                :data-bs-target="'#cbs-pi-' + transaction.payment_request.invoice_id"
              ></button>
              <button
                class="d-none"
                id="cbs-loading-modal-btn"
                data-bs-toggle="modal"
                data-bs-target="#cbs-loading-modal"
              ></button>
              <div class="modal fade" id="cbs-loading-modal" tabindex="-1" aria-hidden="true">
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
              <div
                class="modal fade"
                :id="'cbs-transactions-details-' + transaction.id"
                tabindex="-1"
                aria-hidden="true"
              >
                <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('CBS Transaction') }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="table-responsive">
                        <table class="table">
                          <thead style="background: #f0f0f0">
                            <tr>
                              <th>{{ $t('Date Time') }}</th>
                              <th>{{ $t('Code') }}</th>
                              <th>{{ $t('Reference Code') }}</th>
                              <th>{{ $t('Message') }}</th>
                              <th>{{ $t('Transaction Type') }}</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr>
                              <td>{{ moment(transaction.transaction_date).format(date_format) }}</td>
                              <td>{{ transaction.product == 'Vendor Financing' ? 'VF' : 'DF' }}</td>
                              <td>{{ transaction.transaction_reference ? transaction.transaction_reference : '-' }}</td>
                              <td>-</td>
                              <td>{{ transaction.transaction_type }}</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal fade" :id="'cbs-transactions-' + transaction.id" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Update CBS Transaction') }}</h5>
                      <button
                        :id="'close-modal-' + transaction.id"
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                      ></button>
                    </div>
                    <div class="modal-body">
                      <div class="row mb-1">
                        <div class="col-md-6 col-sm-12 my-1">
                          <label for="" class="form-label"
                            >{{ $t('Debit From') }} <span class="text-danger text-lg">*</span></label
                          >
                        </div>
                        <div class="col-md-6 col-sm-12 my-1">
                          <input type="text" class="form-control" v-model="updateDebitFrom" />
                        </div>
                        <div class="col-md-6 col-sm-12 my-1">
                          <label for="" class="form-label">{{ $t('Debit From Account Name') }}</label>
                        </div>
                        <div class="col-md-6 col-sm-12 my-1">
                          <input type="text" class="form-control" v-model="updateDebitFromAccountName" />
                        </div>
                        <div class="col-md-6 col-sm-12 my-1">
                          <label for="" class="form-label"
                            >{{ $t('Credit To') }} <span class="text-danger text-lg">*</span></label
                          >
                        </div>
                        <div class="col-md-6 col-sm-12 my-1">
                          <input type="text" class="form-control" v-model="updateCreditTo" />
                        </div>
                        <div class="col-md-6 col-sm-12 my-1">
                          <label for="" class="form-label">{{ $t('Credit To Account Name') }}</label>
                        </div>
                        <div class="col-md-6 col-sm-12 my-1">
                          <input type="text" class="form-control" v-model="updateCreditToAccountName" />
                        </div>
                        <div class="col-md-6 col-sm-12 my-1">
                          <label for="" class="form-label"
                            >{{ $t('Status') }} <span class="text-danger text-lg">*</span></label
                          >
                        </div>
                        <div class="col-md-6 col-sm-12 my-1">
                          <select name="" id="" class="form-select" v-model="updateStatus">
                            <option
                              v-for="status in cbs_transaction_statuses"
                              :key="status"
                              :selected="transaction.status == status"
                              :value="status"
                            >
                              {{ status }}
                            </option>
                          </select>
                        </div>
                        <div class="col-md-6 col-sm-12 my-1" v-if="updateStatus == 'Successful'">
                          <label for="" class="form-label"
                            >{{ $t('Transaction Ref') }} <span class="text-danger text-lg">*</span></label
                          >
                        </div>
                        <div class="col-md-6 col-sm-12 my-1" v-if="updateStatus == 'Successful'">
                          <input
                            type="text"
                            class="form-control"
                            v-model="addTransactionReference"
                            :required="updateStatus === 'Successful'"
                          />
                        </div>
                        <div class="col-md-6 col-sm-12 my-1" v-if="updateStatus == 'Successful'">
                          <label for="" class="form-label"
                            >{{ $t('Paid Date') }} <span class="text-danger text-lg">*</span></label
                          >
                        </div>
                        <div class="col-md-6 col-sm-12 my-1" v-if="updateStatus == 'Successful'">
                          <input
                            type="date"
                            class="form-control"
                            :min="
                              transaction.payment_request
                                ? moment(transaction.payment_request.invoice_created_at).format('YYYY-MM-DD')
                                : ''
                            "
                            v-model="addPayDate"
                            :required="updateStatus === 'Successful'"
                          />
                        </div>
                        <div class="col-md-6 col-sm-12 my-1">
                          <label for="" class="form-label">{{ $t('Transaction Type') }}</label>
                        </div>
                        <div class="col-md-6 col-sm-12 my-1">
                          <input type="text" :value="transaction.transaction_type" class="form-control" readonly />
                        </div>
                        <div class="col-md-6 col-sm-12 my-1">
                          <label for="" class="form-label">{{ $t('Product Type') }}</label>
                        </div>
                        <div class="col-md-6 col-sm-12 my-1">
                          <input type="text" :value="transaction.product" class="form-control" readonly />
                        </div>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button class="btn btn-primary" @click="updateTransactionStatus" :disabled="!can_submit">
                        {{ can_submit ? $t('Submit') : $t('Processing...') }}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <div
                class="modal fade"
                v-if="transaction.payment_request && transaction.payment_request.invoice_number && invoice_details"
                :id="'cbs-invoice-' + transaction.payment_request.invoice_id"
                tabindex="-1"
                aria-hidden="true"
              >
                <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                  <div class="modal-content">
                    <div class="modal-header d-flex flex-column flex-md-row justify-content-between">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Invoice Details') }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      <div class="d-flex flex-wrap gap-1">
                        <div>
                          <button
                            class="btn btn-primary"
                            type="button"
                            data-bs-toggle="modal"
                            :data-bs-target="'#cbs-add-attachment-' + invoice_details.id"
                          >
                            <i class="ti ti-paperclip"></i>{{ $t('Attachment') }}
                          </button>
                        </div>
                        <a :href="'/invoices/' + invoice_details.id + '/pdf/download'" class="btn btn-primary"
                          ><i class="ti ti-printer"></i> {{ $t('Print') }}</a
                        >
                      </div>
                    </div>
                    <div class="modal-body">
                      <div class="d-flex flex-column flex-md-row justify-content-between mb-4">
                        <div class="mb-md-3">
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Anchor') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">
                              {{
                                invoice_details.buyer ? invoice_details.buyer.name : invoice_details.program.anchor.name
                              }}
                            </h6>
                          </span>
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Delivery Address') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">{{ invoice_details.delivery_address }}</h6>
                          </span>
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Debit From') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">
                              {{ invoice_details.bank_details[0].account_number }}
                            </h6>
                          </span>
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Vendor') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">
                              {{
                                invoice_details.buyer
                                  ? invoice_details.program.anchor.name
                                  : invoice_details.company.name
                              }}
                            </h6>
                          </span>
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Remarks') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">{{ invoice_details.remarks }}</h6>
                          </span>
                          <span
                            class="d-flex flex-column flex-md-row justify-content-between"
                            v-if="invoice_details.credit_to"
                          >
                            <h5 class="fw-light my-auto">{{ $t('Credit To') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">{{ invoice_details.credit_to }}</h6>
                          </span>
                          <span
                            class="d-flex flex-column flex-md-row justify-content-between"
                            v-if="invoice_details.rejected_reason"
                          >
                            <h5 class="fw-light my-auto">{{ $t('Rejection Reason') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">{{ invoice_details.rejected_reason }}</h6>
                          </span>
                        </div>
                        <div class="mb-md-3">
                          <span
                            v-if="invoice_details.pi_number"
                            class="d-flex flex-column flex-md-row justify-content-between"
                          >
                            <h5 class="my-auto fw-light">{{ $t('PI No') }}:</h5>
                            <h6
                              class="fw-bold mx-md-2 my-auto text-decoration-underline text-primary pointer"
                              data-bs-toggle="modal"
                              :data-bs-target="'#cbs-pi-' + invoice_details.id"
                            >
                              {{ invoice_details.pi_number }}
                            </h6>
                          </span>
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Invoice No.') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">{{ invoice_details.invoice_number }}</h6>
                          </span>
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('PO No.') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">
                              {{
                                invoice_details.purchase_order
                                  ? invoice_details.purchase_order.purchase_order_number
                                  : ''
                              }}
                            </h6>
                          </span>
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Payment / OD Account No.') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">
                              {{ invoice_details.vendor_configurations.payment_account_number }}
                            </h6>
                          </span>
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Invoice Date.') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">
                              {{ moment(invoice_details.invoice_date).format('DD MMM YYYY') }}
                            </h6>
                          </span>
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Amount') }}:</h5>
                            <h6 class="fw-bold text-success mx-md-2 my-auto">
                              {{ invoice_details.currency }} {{ new Intl.NumberFormat().format(invoice_details.total) }}
                            </h6>
                          </span>
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Status.') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">{{ invoice_details.approval_stage }}</h6>
                          </span>
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Due Date') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">
                              {{ moment(invoice_details.due_date).format('DD MMM YYYY') }}
                            </h6>
                          </span>
                        </div>
                      </div>
                      <div class="table-responsive">
                        <table class="table">
                          <thead class="bg-label-primary">
                            <tr>
                              <th>{{ $t('Item') }}</th>
                              <th>{{ $t('Quantity') }}</th>
                              <th>{{ $t('Price Per Quantity') }}</th>
                              <th>{{ $t('Total') }}</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="item in invoice_details.invoice_items" :key="item.id">
                              <td>{{ item.item }}</td>
                              <td>{{ item.quantity }}</td>
                              <td>{{ item.price_per_quantity }}</td>
                              <td>{{ new Intl.NumberFormat().format(item.quantity * item.price_per_quantity) }}</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                      <div class="px-2">
                        <span class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto">{{ $t('Discount') }}:</h6>
                          <h5 class="text-success my-auto">
                            {{ new Intl.NumberFormat().format(invoice_details.total_invoice_discount) }}
                          </h5>
                        </span>
                      </div>
                      <div v-if="invoice_details.invoice_taxes.length" class="px-2">
                        <span
                          v-for="tax in invoice_details.invoice_taxes"
                          :key="tax.id"
                          class="d-flex justify-content-end"
                        >
                          <h6 class="mx-2 my-auto">{{ tax.name }}</h6>
                          <h5 class="text-success my-auto">{{ new Intl.NumberFormat().format(tax.value) }}</h5>
                        </span>
                      </div>
                      <div v-else class="px-2">
                        <span class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto">{{ $t('Tax') }}</h6>
                          <h5 class="text-success my-auto">0.0</h5>
                        </span>
                      </div>
                      <div class="bg-label-secondary px-2">
                        <span class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto py-1">{{ $t('Total') }}</h6>
                          <h5 class="text-success my-auto py-1">
                            {{ invoice_details.currency }}
                            {{
                              new Intl.NumberFormat().format(
                                invoice_details.total +
                                  invoice_details.total_invoice_taxes -
                                  invoice_details.total_invoice_discount
                              )
                            }}
                          </h5>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div
                class="modal fade"
                v-if="transaction.payment_request && transaction.payment_request.invoice_number && invoice_details"
                :id="'cbs-pi-' + transaction.payment_request.invoice_id"
                tabindex="-1"
                aria-hidden="true"
              >
                <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                  <div class="modal-content">
                    <div class="modal-header d-flex flex-column flex-md-row justify-content-between">
                      <h5 class="modal-title" id="modalCenterTitle">{{ $t('Invoice Details') }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      <div class="d-flex flex-wrap gap-1">
                        <div>
                          <button
                            class="btn btn-primary"
                            type="button"
                            data-bs-toggle="modal"
                            :data-bs-target="'#cbs-add-attachment-' + invoice_details.id"
                          >
                            <i class="ti ti-paperclip"></i>{{ $t('Attachment') }}
                          </button>
                        </div>
                        <a
                          :href="'/invoices/payment-instruction/' + invoice_details.id + '/pdf/download'"
                          class="btn btn-primary"
                          ><i class="ti ti-printer"></i> {{ $t('Print') }}</a
                        >
                      </div>
                    </div>
                    <div class="modal-body">
                      <div class="d-flex flex-column flex-md-row justify-content-between mb-4">
                        <div class="mb-md-3">
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Anchor') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">
                              {{
                                invoice_details.buyer ? invoice_details.buyer.name : invoice_details.program.anchor.name
                              }}
                            </h6>
                          </span>
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Delivery Address') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">{{ invoice_details.delivery_address }}</h6>
                          </span>
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Debit From') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">
                              {{ invoice_details.bank_details[0].account_number }}
                            </h6>
                          </span>
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Vendor') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">
                              {{
                                invoice_details.buyer
                                  ? invoice_details.program.anchor.name
                                  : invoice_details.company.name
                              }}
                            </h6>
                          </span>
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="fw-light my-auto">{{ $t('Remarks') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">{{ invoice_details.remarks }}</h6>
                          </span>
                          <span
                            class="d-flex flex-column flex-md-row justify-content-between"
                            v-if="invoice_details.credit_to"
                          >
                            <h5 class="fw-light my-auto">{{ $t('Credit To') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">{{ invoice_details.credit_to }}</h6>
                          </span>
                          <span
                            class="d-flex flex-column flex-md-row justify-content-between"
                            v-if="invoice_details.rejected_reason"
                          >
                            <h5 class="fw-light my-auto">{{ $t('Rejection Reason') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">{{ invoice_details.rejected_reason }}</h6>
                          </span>
                        </div>
                        <div class="mb-md-3">
                          <span
                            v-if="invoice_details.pi_number"
                            class="d-flex flex-column flex-md-row justify-content-between"
                          >
                            <h5 class="my-auto fw-light">{{ $t('Invoice No.') }}:</h5>
                            <h6
                              class="fw-bold mx-md-2 my-auto text-decoration-underline text-primary pointer"
                              data-bs-toggle="modal"
                              :data-bs-target="'#cbs-invoice-' + invoice_details.id"
                            >
                              {{ invoice_details.invoice_number }}
                            </h6>
                          </span>
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('PI No.') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">{{ invoice_details.pi_number }}</h6>
                          </span>
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('PO No.') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">
                              {{
                                invoice_details.purchase_order
                                  ? invoice_details.purchase_order.purchase_order_number
                                  : ''
                              }}
                            </h6>
                          </span>
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Payment / OD Account No.') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">
                              {{ invoice_details.vendor_configurations.payment_account_number }}
                            </h6>
                          </span>
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Invoice Date.') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">
                              {{ moment(invoice_details.invoice_date).format('DD MMM YYYY') }}
                            </h6>
                          </span>
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Amount') }}:</h5>
                            <h6 class="fw-bold text-success mx-md-2 my-auto">
                              {{ invoice_details.currency }} {{ new Intl.NumberFormat().format(invoice_details.total) }}
                            </h6>
                          </span>
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Status.') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">{{ invoice_details.approval_stage }}</h6>
                          </span>
                          <span class="d-flex flex-column flex-md-row justify-content-between">
                            <h5 class="my-auto fw-light">{{ $t('Due Date') }}:</h5>
                            <h6 class="fw-bold mx-md-2 my-auto">
                              {{ moment(invoice_details.due_date).format('DD MMM YYYY') }}
                            </h6>
                          </span>
                        </div>
                      </div>
                      <div class="table-responsive">
                        <table class="table">
                          <thead class="bg-label-primary">
                            <tr>
                              <th>{{ $t('Item') }}</th>
                              <th>{{ $t('Quantity') }}</th>
                              <th>{{ $t('Price Per Quantity') }}</th>
                              <th>{{ $t('Total') }}</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="item in invoice_details.invoice_items" :key="item.id">
                              <td>{{ item.item }}</td>
                              <td>{{ item.quantity }}</td>
                              <td>{{ item.price_per_quantity }}</td>
                              <td>{{ new Intl.NumberFormat().format(item.quantity * item.price_per_quantity) }}</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                      <div class="px-2">
                        <span class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto">{{ $t('Discount') }}:</h6>
                          <h5 class="text-success my-auto">
                            {{ new Intl.NumberFormat().format(invoice_details.total_invoice_discount) }}
                          </h5>
                        </span>
                      </div>
                      <div v-if="invoice_details.invoice_taxes.length" class="px-2">
                        <span
                          v-for="tax in invoice_details.invoice_taxes"
                          :key="tax.id"
                          class="d-flex justify-content-end"
                        >
                          <h6 class="mx-2 my-auto">{{ tax.name }}</h6>
                          <h5 class="text-success my-auto">{{ new Intl.NumberFormat().format(tax.value) }}</h5>
                        </span>
                      </div>
                      <div v-else class="px-2">
                        <span class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto">{{ $t('Tax') }}</h6>
                          <h5 class="text-success my-auto">0.0</h5>
                        </span>
                      </div>
                      <div v-if="invoice_details.invoice_fees.length > 0" class="px-2">
                        <span
                          v-for="fee in invoice_details.invoice_fees"
                          :key="fee.id"
                          class="d-flex justify-content-end"
                        >
                          <h6 class="mx-2 my-auto py-1">{{ fee.name }}</h6>
                          <h5
                            class="text-success my-auto py-1"
                            v-if="
                              fee.name != 'Credit Note Amount' &&
                              fee.name != 'Credit Note' &&
                              fee.name != 'Credit Amount'
                            "
                          >
                            ({{
                              getTaxPercentage(
                                invoice_details.total +
                                  invoice_details.total_invoice_taxes -
                                  invoice_details.total_invoice_discount,
                                fee.amount
                              )
                            }}%) {{ new Intl.NumberFormat().format(fee.amount.toFixed(2)) }}
                          </h5>
                          <h5 class="text-success my-auto py-1" v-else>{{ fee.amount }}</h5>
                        </span>
                      </div>
                      <div v-else class="px-2">
                        <span class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto py-1">{{ $t('WHT Tax') }}:</h6>
                          <h5 class="text-success my-auto py-1">(0.0%) 0</h5>
                        </span>
                        <span class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto py-1">{{ $t('WHT VAT') }}:</h6>
                          <h5 class="text-success my-auto py-1">(0.0%) 0</h5>
                        </span>
                        <span class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto py-1">{{ $t('Credit Note Amount') }}:</h6>
                          <h5 class="text-success my-auto py-1">0</h5>
                        </span>
                      </div>
                      <div class="bg-label-secondary px-2">
                        <span class="d-flex justify-content-end">
                          <h6 class="mx-2 my-auto py-1">{{ $t('Total') }}</h6>
                          <h5 class="text-success my-auto py-1">
                            {{ new Intl.NumberFormat().format(invoice_details.invoice_total_amount) }}
                          </h5>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <cbs-upload-attachment
                v-if="transaction.payment_request && transaction.payment_request.invoice_number && invoice_details"
                :invoiceDetails="invoice_details"
                :key="upload_attachment_key"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination
      v-if="transactions.meta"
      class="mx-2"
      :from="transactions.meta.from"
      :to="transactions.meta.to"
      :links="transactions.meta.links"
      :next_page="transactions.links.next"
      :prev_page="transactions.links.prev"
      :total_items="transactions.meta.total"
      :first_page_url="transactions.links.first"
      :last_page_url="transactions.links.last"
      @change-page="changePage"
    ></pagination>
  </div>
</template>
<script>
import { computed, inject, onMounted, ref, watch, nextTick } from 'vue';
import axios from 'axios';
// Notification
import { useToast } from 'vue-toastification';
import moment from 'moment';
import Pagination from './partials/Pagination.vue';
// import '../../../public/assets/vendor/libs/select2/select2.js'
import 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js';
import '../../../public/assets/vendor/libs/select2/select2.css';
import '../../../public/assets/js/forms-selects.js';
import CbsUploadAttachment from '../CbsUploadAttachment.vue';

export default {
  name: 'CbsTransactions',
  components: {
    Pagination,
    CbsUploadAttachment
  },
  props: ['bank', 'can_update', 'can_upload', 'date_format'],
  setup(props, context) {
    const date_format = props.date_format;
    const toast = useToast();
    const base_url = inject('baseURL');
    const can_update = props.can_update;
    const can_upload = props.can_upload;
    const transactions = ref([]);
    const cbs_transactions = ref(null);
    const update_cbs_modal = ref({});

    const show_error_download_link = ref(false);

    const cbs_transaction_statuses = ref(['Successful', 'Created', 'Failed', 'Permanently Failed']);

    const cbsTransaction = ref(null);
    const updateDebitFrom = ref('');
    const updateCreditTo = ref('');
    const updateDebitFromAccountName = ref('');
    const updateCreditToAccountName = ref('');
    const updateNarration = ref('');
    const updateStatus = ref('');

    const addDebitFrom = ref('');
    const addCreditTo = ref('');
    const addDebitFromName = ref('');
    const addCreditToName = ref('');
    const addAmount = ref('');
    const addStatus = ref('');
    const addPayDate = ref('');
    const addTransactionReference = ref('');
    const addTransactionType = ref('');
    const uploadErrors = ref([]);

    const fileUpload = ref(null);

    const can_submit = ref(true);
    const can_close = ref(true);

    const close_modal = ref(null);

    const selected_transactions = ref([]);

    // Search fields
    const cbs_id_search = ref('');
    const invoice_number_search = ref('');
    const account_search = ref('');
    const transaction_ref_search = ref('');
    const status_search = ref([]);
    const product_type_search = ref('');
    const from_date = ref('');
    const to_date = ref('');
    const from_transaction_date = ref('');
    const to_transaction_date = ref('');
    const transaction_type = ref([]);
    const sort_by = ref('desc');
    const date_search = ref('');
    const transaction_date_search = ref('');

    const invoice_details = ref(null);

    const selected_file = ref(0);

    // Pagination
    const per_page = ref(50);

    const upload_attachment_key = ref(0);

    const getRequests = async () => {
      await axios
        .get(base_url + props.bank + '/requests/cbs-transactions/data', {
          params: {
            per_page: per_page.value,
            cbs_id: cbs_id_search.value,
            transaction_ref: transaction_ref_search.value,
            invoice_number: invoice_number_search.value,
            account: account_search.value,
            status: status_search.value,
            product_type: product_type_search.value,
            from_date: from_date.value,
            to_date: to_date.value,
            from_transaction_date: from_transaction_date.value,
            to_transaction_date: to_transaction_date.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          transactions.value = response.data.transactions;
        })
        .catch(err => {
          console.log(err);
        });
    };

    const showInvoice = async (invoice, modal) => {
      document.getElementById('cbs-loading-modal-btn').click();
      await axios
        .get(base_url + props.bank + '/requests/invoices/' + invoice + '/details')
        .then(response => {
          invoice_details.value = response.data;
          upload_attachment_key.value++;
          nextTick(() => {
            document.getElementById(modal).click();
          });
        })
        .catch(err => {
          console.log(err);
        });
    };

    context.expose({ getRequests });

    onMounted(() => {
      // Get values of status filter
      $('#cbs-status-select').on('change', function () {
        var ids = $('#cbs-status-select').val();
        status_search.value = ids;
      });

      $('#transaction-type-select').on('change', function () {
        var ids = $('#transaction-type-select').val();
        transaction_type.value = ids;
      });
    });

    watch([per_page], async ([per_page]) => {
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        from_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      await axios
        .get(base_url + props.bank + '/requests/cbs-transactions/data', {
          params: {
            per_page: per_page,
            cbs_id: cbs_id_search.value,
            transaction_ref: transaction_ref_search.value,
            transaction_type: transaction_type.value,
            invoice_number: invoice_number_search.value,
            account: account_search.value,
            status: status_search.value,
            product_type: product_type_search.value,
            from_date: from_date.value,
            to_date: to_date.value,
            from_transaction_date: from_transaction_date.value,
            to_transaction_date: to_transaction_date.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          transactions.value = response.data.transactions;
        })
        .catch(err => {
          console.log(err);
        });
    });

    const filter = async () => {
      date_search.value = $('#cbs_date_search').val();
      if (date_search.value) {
        from_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      transaction_date_search.value = $('#cbs_transaction_date_search').val();
      if (transaction_date_search.value) {
        from_transaction_date.value = moment(transaction_date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_transaction_date.value = moment(transaction_date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      await axios
        .get(base_url + props.bank + '/requests/cbs-transactions/data', {
          params: {
            per_page: per_page.value,
            cbs_id: cbs_id_search.value,
            transaction_ref: transaction_ref_search.value,
            invoice_number: invoice_number_search.value,
            account: account_search.value,
            status: status_search.value,
            product_type: product_type_search.value,
            from_date: from_date.value,
            to_date: to_date.value,
            from_transaction_date: from_transaction_date.value,
            to_transaction_date: to_transaction_date.value,
            sort_by: sort_by.value,
            transaction_type: transaction_type.value
          }
        })
        .then(response => {
          transactions.value = response.data.transactions;
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = async () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      cbs_id_search.value = '';
      transaction_ref_search.value = '';
      invoice_number_search.value = '';
      account_search.value = '';
      status_search.value = '';
      product_type_search.value = '';
      from_date.value = '';
      to_date.value = '';
      sort_by.value = 'desc';
      transaction_type.value = '';
      date_search.value = '';
      from_transaction_date.value = '';
      to_transaction_date.value = '';
      $('#cbs_date_search').val('');
      $('#cbs_transaction_date_search').val('');
      $('#cbs-status-select').val('');
      $('#cbs-status-select').trigger('change');
      $('#transaction-type-select').val('');
      $('#transaction-type-select').trigger('change');
      await axios
        .get(base_url + props.bank + '/requests/cbs-transactions/data', {
          params: {
            per_page: per_page.value,
            sort_by: sort_by.value
          }
        })
        .then(response => {
          transactions.value = response.data.transactions;
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    const showEditModal = transaction => {
      cbsTransaction.value = transaction;
      updateDebitFrom.value = transaction.debit_from_account;
      updateCreditTo.value = transaction.credit_to_account;
      updateDebitFromAccountName.value = transaction.debit_from_account_name;
      updateCreditToAccountName.value = transaction.credit_to_account_name;
      addTransactionReference.value = transaction.transaction_reference;
      updateStatus.value = transaction.status;
      if (transaction.status == 'Successful') {
        cbs_transaction_statuses.value = ['Failed', 'Permanently Failed', 'Successful'];
      }
      if (transaction.status == 'Failed' || transaction.status == 'Permanently Failed') {
        if (transaction.status == 'Failed') {
          cbs_transaction_statuses.value = ['Failed', 'Permanently Failed', 'Successful'];
        }
        if (transaction.status == 'Permanently Failed') {
          cbs_transaction_statuses.value = ['Permanently Failed'];
        }
      }
    };

    const getTaxPercentage = (invoice_amount, tax_amount) => {
      return Math.round((tax_amount / invoice_amount) * 100);
    };

    const updateTransactionStatus = async () => {
      can_submit.value = false;
      can_close.value = false;
      await axios
        .post(base_url + props.bank + '/requests/cbs-transactions/' + cbsTransaction.value.id + '/data/update', {
          debit_from_account: updateDebitFrom.value,
          credit_to_account: updateCreditTo.value,
          debit_from_account_name: updateDebitFromAccountName.value,
          credit_to_account_name: updateCreditToAccountName.value,
          status: updateStatus.value,
          transaction_ref: addTransactionReference.value,
          pay_date: addPayDate.value
        })
        .then(() => {
          document.getElementById('close-modal-' + cbsTransaction.value.id).click();
          toast.success('Transaction updated successfully');
          getRequests();
          updateDebitFrom.value = '';
          updateCreditTo.value = '';
          updateDebitFromAccountName.value = '';
          updateCreditToAccountName.value = '';
          updateStatus.value = '';
          addTransactionReference.value = '';
          addPayDate.value = '';
        })
        .catch(err => {
          if (err.response.status === 422) {
            Object.keys(err.response.data).forEach(key => {
              toast.error(err.response.data[key][0], {
                timeout: 10000
              });
            });
          } else {
            toast.error('Something went wrong.');
            console.log(err);
          }
        })
        .finally(() => {
          can_submit.value = true;
          can_close.value = true;
        });
    };

    const getTotalAmount = invoice_items => {
      let amount = 0;
      invoice_items.forEach(item => {
        amount += item.quantity * item.price_per_quantity;
      });
      return amount;
    };

    const getTaxesAmount = invoice_taxes => {
      let amount = 0;
      invoice_taxes.forEach(item => {
        amount += item.value;
      });
      return amount;
    };

    const getFeesAmount = invoice_fees => {
      let amount = 0;
      invoice_fees.forEach(item => {
        amount += item.amount;
      });
      return amount;
    };

    const submitNewTransactions = () => {
      can_submit.value = false;
      can_close.value = false;
      axios
        .post(base_url + props.bank + '/requests/cbs-transactions/create', {
          debit_from_account: addDebitFrom.value,
          credit_to_account: addCreditTo.value,
          debit_from_account_name: addDebitFromName.value,
          credit_to_account_name: addCreditToName.value,
          status: addStatus.value,
          amount: addAmount.value,
          pay_date: addPayDate.value,
          type: addTransactionType.value,
          ref: addTransactionReference.value
        })
        .then(() => {
          toast.success('Transaction updated successfully');
          addDebitFrom.value = '';
          addCreditTo.value = '';
          addDebitFromName.value = '';
          addCreditToName.value = '';
          addStatus.value = '';
          addAmount.value = '';
          addPayDate.value = '';
          addTransactionType.value = '';
          addTransactionReference.value = '';
          getRequests();
        })
        .catch(err => {
          console.log(err.data);
          toast.error('An error occurred while updating the transaction');
        })
        .finally(() => {
          can_submit.value = true;
          can_close.value = true;
        });
    };

    const resolvePaymentRequestStatus = status => {
      let style = '';
      switch (status.toLowerCase()) {
        case 'created':
          style = 'bg-label-primary';
          break;
        case 'successful':
          style = 'bg-label-success';
          break;
        case 'failed':
          style = 'bg-label-danger';
          break;
        case 'permanently failed':
          style = 'bg-label-danger';
          break;
        default:
          style = 'bg-label-primary';
          break;
      }

      return style;
    };

    const updatedFile = e => {
      let files = e.target.files || e.dataTransfer.files;
      if (!files.length) {
        return;
      }
      cbs_transactions.value = files[0];
    };

    const downloadCbsTransactions = () => {
      let parent = $('.filtered').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      date_search.value = $('#cbs_date_search').val();
      if (date_search.value) {
        from_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      axios
        .get(base_url + props.bank + '/requests/cbs-transactions/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            cbs_id: cbs_id_search.value,
            transaction_ref: transaction_ref_search.value,
            invoice_number: invoice_number_search.value,
            account: account_search.value,
            status: status_search.value,
            product_type: product_type_search.value,
            from_date: from_date.value,
            to_date: to_date.value,
            from_transaction_date: from_transaction_date.value,
            to_transaction_date: to_transaction_date.value,
            sort_by: sort_by.value,
            transaction_type: transaction_type.value,
            selected_transactions: selected_transactions.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `CBS_Transactions_${moment().format('Do_MMM_YYYY')}.xlsx`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          if (err.response.status == 400) {
            toast.error('You can only export 3 months of data at a time. Kindly select a date range.', {
              timeout: 12000
            });
          } else {
            toast.error('Something went wrong while exporting the data');
          }
        })
        .finally(() => {
          parent.html('<i class="ti ti-download ti-xs px-1"></i> Excel');
        });
    };

    const downloadCreatedCbsTransactions = () => {
      let parent = $('.created').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      date_search.value = $('#cbs_date_search').val();
      if (date_search.value) {
        from_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      axios
        .get(base_url + props.bank + '/requests/cbs-transactions/created/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            from_date: from_date.value,
            to_date: to_date.value,
            from_transaction_date: from_transaction_date.value,
            to_transaction_date: to_transaction_date.value,
            status: ['Created']
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `CBS_Transactions_${moment().format('Do_MMM_YYYY')}.xlsx`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          if (err.response.status == 400) {
            toast.error('You can only export 3 months of data at a time. Kindly select a date range.', {
              timeout: 12000
            });
          } else {
            toast.error('Something went wrong while exporting the data');
          }
        })
        .finally(() => {
          parent.html('Download Transactions');
        });
    };

    const updateSelected = id => {
      if (selected_transactions.value.includes(id)) {
        const index = selected_transactions.value.indexOf(id);
        selected_transactions.value.splice(index, 1);
      } else {
        selected_transactions.value.push(id);
      }
    };

    const selectAll = () => {
      if (!document.getElementById('select-all').checked) {
        transactions.value.data.forEach(transaction => {
          if (document.getElementById('transaction-' + transaction.id).checked == true) {
            document.getElementById('transaction-' + transaction.id).checked = false;
            let f;
            let index = selected_transactions.value.filter(function (id, index) {
              f = index;
              id == transaction.id;
            });
            if (!index) {
              return false;
            }
            selected_transactions.value.splice(f, 1);
          }
        });
      } else {
        transactions.value.data.forEach(transaction => {
          document.getElementById('transaction-' + transaction.id).checked = true;
          selected_transactions.value.push(transaction.id);
        });
      }
    };

    const submitTransactions = async () => {
      can_close.value = false;
      can_submit.value = false;
      uploadErrors.value = [];
      if (cbs_transactions.value == null) {
        toast.error('Select File to upload');
        return;
      }

      const formData = new FormData();
      formData.append('transactions', cbs_transactions.value);

      await axios
        .post(base_url + props.bank + '/requests/payment-requests/transactions/import', formData)
        .then(res => {
          if (res.headers['content-type'] !== 'application/json') {
            show_error_download_link.value = true;
            document.getElementById('file-upload').value = '';
            toast.error('Invalid Data. Download Error report and resolve Errors to upload.');
          } else {
            document.getElementById('close-upload-modal').click();
            toast.success('File Uploaded Successfully');
            getRequests();
          }
        })
        .catch(error => {
          console.log(error);
          // toast.error('Invalid headers or missing column. Download and use the CBS Transactions template to import.');
          if (error.response.status == 422) {
            selected_file.value++;
            toast.error(error.response.data.message);
            return;
          }

          if (error.response.data.uploaded > 0) {
            toast.success(error.response.data.uploaded + ' uploaded successfully.');
          }

          if (error.response.data.successful_rows > 0) {
            toast.info(error.response.data.successful_rows + ' were already successful.');
          }

          if (
            error.response.data.total_rows - (error.response.data.uploaded + error.response.data.successful_rows) >
            0
          ) {
            toast.error(
              error.response.data.total_rows -
                (error.response.data.uploaded + error.response.data.successful_rows) +
                ' failed to upload. View error report for details.'
            );
            show_error_download_link.value = true;
            document.getElementById('file-upload').value = '';
          } else {
            document.getElementById('close-upload-modal').click();
            getRequests();
          }
        })
        .finally(() => {
          can_close.value = true;
          can_submit.value = true;
          selected_file.value++;
        });
    };

    const changePage = async page => {
      date_search.value = $('#cbs_date_search').val();
      if (date_search.value) {
        from_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        to_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      await axios
        .get(page, {
          params: {
            per_page: per_page.value,
            cbs_id: cbs_id_search.value,
            transaction_ref: transaction_ref_search.value,
            invoice_number: invoice_number_search.value,
            account: account_search.value,
            status: status_search.value,
            product_type: product_type_search.value,
            from_date: from_date.value,
            to_date: to_date.value,
            from_transaction_date: from_transaction_date.value,
            to_transaction_date: to_transaction_date.value,
            sort_by: sort_by.value,
            transaction_type: transaction_type.value
          }
        })
        .then(response => {
          transactions.value = response.data.transactions;
        });
    };

    return {
      invoice_details,
      moment,
      show_error_download_link,
      can_update,
      can_upload,
      transactions,
      cbs_transactions,
      cbsTransaction,
      updateCreditTo,
      updateDebitFrom,
      updateDebitFromAccountName,
      updateCreditToAccountName,
      updateNarration,
      updateStatus,
      cbs_transaction_statuses,
      addDebitFrom,
      addCreditTo,
      addDebitFromName,
      addCreditToName,
      addAmount,
      addStatus,
      addPayDate,
      addTransactionReference,
      addTransactionType,
      update_cbs_modal,
      can_submit,
      can_close,
      close_modal,
      cbs_id_search,
      invoice_number_search,
      account_search,
      transaction_ref_search,
      status_search,
      from_date,
      to_date,
      from_transaction_date,
      to_transaction_date,
      sort_by,
      product_type_search,
      transaction_type,
      per_page,
      selected_file,
      filter,
      refresh,
      submitTransactions,
      submitNewTransactions,
      updateTransactionStatus,
      resolvePaymentRequestStatus,
      changePage,
      updatedFile,
      showEditModal,
      getTotalAmount,
      getTaxesAmount,
      getFeesAmount,
      getTaxPercentage,
      downloadCbsTransactions,
      downloadCreatedCbsTransactions,
      updateSelected,
      selectAll,
      uploadErrors,
      fileUpload,
      showInvoice,
      date_format,
      upload_attachment_key
    };
  }
};
</script>
<style>
[data-title]:hover:after {
  opacity: 1;
  transition: all 0.1s ease 0.5s;
  visibility: visible;
}

[data-title]:after {
  content: attr(data-title);
  background-color: #0b0b0b;
  color: #f9f9f9;
  font-size: 16px;
  position: absolute;
  padding: 1px 5px 2px 5px;
  bottom: -1.6em;
  left: 100%;
  box-shadow: 1px 1px 3px #222222;
  opacity: 0;
  border: 1px solid #111111;
  z-index: 99999;
  visibility: hidden;
  border-radius: 5px;
  min-width: 250px;
  max-width: 550px;
}

[data-title] {
  position: relative;
}

input[readonly] {
  background-color: #e8e8e8;
}
</style>
