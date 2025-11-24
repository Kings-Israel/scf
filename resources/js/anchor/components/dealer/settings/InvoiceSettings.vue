<template>
  <div>
    <div class="d-flex justify-content-between p-1">
      <h5 class="fw-bold py-3">
        <span class="fw-light px-3">{{ $t('Maker / Checker') }}</span>
      </h5>
      <div v-if="can_approve && invoice_settings.proposed_update" class="my-auto px-2">
        <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#changes-approval">
          {{ $t('View Proposed Changes') }}
        </button>
        <div class="modal fade" id="changes-approval" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-md" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="modalCenterTitle">{{ $t('Confirm Changes') }}</h5>
                <button
                  type="button"
                  id="close-modal"
                  class="btn-close"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                ></button>
              </div>
              <div class="modal-body">
                <div v-for="change in invoice_settings.proposed_update.changes" :key="change">
                  <div v-for="(key, value) in change" :key="key">
                    <div v-if="value == 'maker_checker_creating_updating'">
                      {{ $t('Change Maker/Checker for Invoice Approval from') }}
                      <span v-if="maker_checker">{{ $t('Yes to No') }}</span>
                      <span v-else>{{ $t('No to Yes') }}</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button class="btn btn-danger btn-sm" @click="updateStatus('reject')">{{ $t('Reject') }}</button>
                <button class="btn btn-success btn-sm" @click="updateStatus('approve')">{{ $t('Approve') }}</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <form @submit.prevent="updateInvoiceSettings" method="post">
      <div class="row px-2 mb-2">
        <div class="col-md-4">
          <div class="row border rounded border-primary mx-2 py-2">
            <div class="col-md-9 d-flex flex-column">
              <label class="col-form-label">{{ $t('Invoice / Payment Instruction (PI) Approval') }}</label>
              <small>{{ $t('Request checker approval for approval of invoices') }}</small>
            </div>
            <div class="col-md-3 mt-2">
              <div class="form-check form-switch">
                <label class="form-check-label no-label" for="flexSwitchCheckChecked">{{ $t('No') }}</label>
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="flexSwitchCheckChecked"
                  :checked="maker_checker"
                  v-model="maker_checker"
                />
                <label class="form-check-label yes-label" for="flexSwitchCheckChecked">{{ $t('Yes') }}</label>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="d-flex my-2 mx-3 d-flex justify-content-end" v-if="can_submit_upper && can_edit == 1">
        <button class="btn btn-primary" type="submit">
          {{ $t('Submit') }}
        </button>
      </div>
    </form>
  </div>
</template>
<script>
import { ref, watch } from 'vue';
import axios from 'axios';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { useToast } from 'vue-toastification';

export default {
  name: 'InvoiceSettings',
  components: {
    QuillEditor
  },
  props: ['can_edit'],
  setup(props, context) {
    const can_edit = props.can_edit;
    const checker = props.checker;
    const toast = useToast();
    const invoice_settings = ref([]);
    const maker_checker = ref(false);
    const can_approve = ref(false);
    const can_submit_upper = ref(false);

    const getInvoiceSettings = () => {
      axios
        .get('invoice-settings')
        .then(response => {
          invoice_settings.value = response.data.settings;
          maker_checker.value = invoice_settings.value.maker_checker_creating_updating;
          if (invoice_settings.value.proposed_update) {
            can_approve.value =
              response.data.can_approve && response.data.user_id != invoice_settings.value.proposed_update.user_id;
          }
        })
        .catch(err => {
          console.log(err);
        });
    };

    context.expose({ getInvoiceSettings });

    const updateInvoiceSettings = () => {
      const formData = new FormData();
      formData.append('maker_checker_creating_updating', maker_checker.value);
      axios
        .post('invoice-settings/update', formData)
        .then(response => {
          getInvoiceSettings();
          toast.success(response.data.message);
        })
        .catch(err => {
          toast.error(err.response.data.message);
        });
    };

    watch([maker_checker], ([maker_checker]) => {
      if (maker_checker != invoice_settings.value.maker_checker_creating_updating) {
        can_submit_upper.value = true;
      } else {
        can_submit_upper.value = false;
      }
    });

    const updateStatus = status => {
      axios
        .get('invoice-settings/status/' + status + '/update')
        .then(response => {
          getInvoiceSettings();
          toast.success(response.data.message);
          document.getElementById('close-modal').click();
        })
        .catch(err => {
          toast.error(err.response.data.message);
        });
    };

    return {
      can_edit,
      checker,
      can_approve,
      invoice_settings,
      maker_checker,
      updateStatus,
      updateInvoiceSettings,
      can_submit_upper
    };
  }
};
</script>
<style scoped>
.no-label {
  margin-left: -75px !important;
}
.yes-label {
  margin-left: 65px !important;
}
</style>
