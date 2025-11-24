<template>
  <div class="nav-align-top nav-tabs-shadow mb-4">
    <ul class="nav nav-tabs" role="tablist">
      <li v-if="can_view_payment_requests" class="nav-item">
        <button
          type="button"
          class="nav-link active text-uppercase"
          role="tab"
          data-bs-toggle="tab"
          data-bs-target="#navs-all-invoices"
          aria-controls="navs-all-invoices"
          aria-selected="true"
          @click="switchTabs('payment_requests')"
        >
          {{ $t('Payment Requests') }}
        </button>
      </li>
      <!-- <li v-if="can_view_payment_requests" class="nav-item">
        <button type="button" class="nav-link text-uppercase" role="tab" data-bs-toggle="tab" data-bs-target="#navs-all-credit-accounts" aria-controls="navs-all-credit-accounts" aria-selected="true" @click="switchTabs('credit_account_requests')">{{ $t('Credit Account Requests') }}</button>
      </li> -->
      <li v-if="can_view_cbs" class="nav-item">
        <button
          type="button"
          class="nav-link text-sm text-uppercase"
          role="tab"
          data-bs-toggle="tab"
          data-bs-target="#navs-cbs-transactions"
          aria-controls="navs-cbs-transactions"
          aria-selected="false"
          @click="switchTabs('cbs_transactions')"
        >
          {{ $t('CBS Transactions') }}
        </button>
      </li>
    </ul>
    <div class="tab-content">
      <div v-if="can_view_payment_requests" class="tab-pane fade show active" id="navs-all-invoices" role="tabpanel">
        <payment-requests :bank="bank" ref="payment_requests" :date_format="date_format"></payment-requests>
      </div>
      <!-- <div v-if="can_view_payment_requests" class="tab-pane fade show" id="navs-all-credit-accounts" role="tabpanel">
        <credit-account-requests :bank="bank" ref="credit_account_requests"></credit-account-requests>
      </div> -->
      <div v-if="can_view_cbs" class="tab-pane fade show" id="navs-cbs-transactions" role="tabpanel">
        <cbs-transactions
          :bank="bank"
          :can_update="can_update"
          :can_upload="can_upload"
          ref="cbs_transactions"
          :date_format="date_format"
        ></cbs-transactions>
      </div>
    </div>
  </div>
</template>
<script>
import { ref, onMounted, nextTick } from 'vue';
import PaymentRequests from './PaymentRequests.vue';
import PaymentReports from './PaymentReports.vue';
import CbsTransactions from './CbsTransactions.vue';
import CreditAccountRequests from './CreditAccountRequests.vue';

export default {
  name: 'Payments',
  components: {
    PaymentRequests,
    PaymentReports,
    CbsTransactions,
    CreditAccountRequests
  },
  props: ['bank', 'can_upload', 'can_update', 'can_view_cbs', 'can_view_payment_requests', 'date_format'],
  setup(props) {
    const can_upload = props.can_upload;
    const can_update = props.can_update;
    const can_view_cbs = props.can_view_cbs;
    const can_view_payment_requests = props.can_view_payment_requests;
    const date_format = props.date_format;

    const payment_requests = ref(null);
    const payment_reports = ref(null);
    const credit_account_requests = ref(null);
    const cbs_transactions = ref(null);

    onMounted(() => {
      payment_requests.value.getRequests();
    });

    const switchTabs = tab => {
      switch (tab) {
        case 'payment_requests':
          payment_requests.value.getRequests();
          break;
        case 'payment_reports':
          payment_reports.value.getRequests();
          break;
        case 'credit_account_requests':
          credit_account_requests.value.getRequests();
          break;
        case 'cbs_transactions':
          cbs_transactions.value.getRequests();
          break;
        default:
          break;
      }
    };

    return {
      can_upload,
      can_update,
      can_view_cbs,
      can_view_payment_requests,
      payment_requests,
      payment_reports,
      credit_account_requests,
      cbs_transactions,
      date_format,
      switchTabs
    };
  }
};
</script>
