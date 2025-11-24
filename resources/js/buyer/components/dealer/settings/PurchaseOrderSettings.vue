<template>
  <div>
    <div class="d-flex justify-content-between p-1">
      <h5 class="fw-bold py-3">
        <span class="fw-light px-3">Maker / Checker</span>
      </h5>
      <div v-if="invoice_settings.proposed_update" class="my-auto px-2">
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
                      {{ $t('Change Maker/Checker for PO Creating Updating from') }}
                      <span v-if="maker_checker">{{ $t('Yes to No') }}</span>
                      <span v-else>{{ $t('No to Yes') }}</span>
                    </div>
                    <div v-if="value == 'auto_request_financing'">
                      {{ $t('Change Auto Request Financing from') }}
                      <span v-if="auto_request_financing">{{ $t('Yes to No') }}</span>
                      <span v-else>{{ $t('No to Yes') }}</span>
                    </div>
                    <div v-if="value == 'request_finance_add_repayment'">
                      {{ $t('Change Maker/Checker for Requesting Financing and Repayment from') }}
                      <span v-if="request_finance_add_repayment">{{ $t('Yes to No') }}</span>
                      <span v-else>{{ $t('No to Yes') }}</span>
                    </div>
                    <div v-if="value == 'logo'">
                      <span>{{ $t('Change Logo to') }} </span>
                      <a
                        :href="'../../storage/purchase-orders/logo/' + key"
                        target="_blank"
                        class="text-primary text-underline"
                        >{{ $t('View File') }}</a
                      >
                    </div>
                    <div v-if="value == 'description'">
                      {{ $t('Change Description To') }}: <br />
                      <span v-html="key"></span>
                    </div>
                    <div v-if="value == 'footer'">
                      {{ $t('Change Footer To') }}: <br />
                      {{ key }}
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal-footer" v-if="can_approve">
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
              <label class="col-form-label">{{ $t('Purchase Orders Creation / Updation') }}</label>
              <small>{{ $t('Request checker approval for creating and updating POs') }}</small>
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
        <div class="col-md-4">
          <div class="row border rounded border-primary mx-2 py-2">
            <div class="col-md-9 d-flex flex-column">
              <label class="col-form-label">{{ $t('Request Financing') }}</label>
              <small>{{
                $t('If selected ‘Yes’, the financing request and repayment will go to Checker for authorization')
              }}</small>
            </div>
            <div class="col-md-3 mt-2">
              <div class="form-check form-switch">
                <label class="form-check-label no-label" for="flexSwitchCheckChecked">No</label>
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="flexSwitchCheckChecked"
                  :checked="request_finance_add_repayment"
                  v-model="request_finance_add_repayment"
                />
                <label class="form-check-label yes-label" for="flexSwitchCheckChecked">Yes</label>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="d-flex my-2 mx-3 d-flex justify-content-end" v-if="can_submit_upper && can_edit == 1">
        <button class="btn btn-primary" type="submit">{{ $t('Submit') }}</button>
      </div>
    </form>
    <hr />
    <h5 class="fw-bold py-3 mb-2">
      <span class="fw-light px-3">{{ $t('Purchase Order Settings') }}</span>
    </h5>
    <form @submit.prevent="updateInvoiceSettings" method="post">
      <div class="row px-3 mb-2">
        <div class="col-md-6 border-bottom d-flex flex-column">
          <label for="html5-text-input" class="col-form-label">Company Logo</label>
          <small>Recommended size 160 x 80 pixels, image format PNG/JPG</small>
        </div>
        <div class="col-md-6">
          <input class="form-control" type="file" id="formFile" accept=".png,.jpg" @change="changeFile" />
        </div>
      </div>
      <div class="row px-3 mb-2">
        <div class="col-md-6 d-flex flex-column">
          <label for="" class="col-form-label">PO Description</label>
          <small>Will be shown in PO as description</small>
        </div>
        <div class="col-md-6 mb-4">
          <QuillEditor theme="snow" ref="editor" @blur="changeDescription" />
        </div>
      </div>
      <div class="row px-3 mb-2 mt-4">
        <div class="col-md-6 d-flex flex-column">
          <label for="html5-text-input" class="col-form-label">PO Footer</label>
          <small>Will be shown in PO footer</small>
        </div>
        <div class="col-md-6">
          <input
            class="form-control"
            type="text"
            placeholder="PO Footer"
            id="html5-text-input"
            v-model="invoice_footer"
          />
        </div>
      </div>
      <div class="d-flex my-2 mx-3 d-flex justify-content-end" v-if="can_submit_lower && can_edit == 1">
        <button class="btn btn-primary" type="submit">Submit</button>
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
  name: 'PurchaseOrderSettings',
  props: ['can_edit'],
  components: {
    QuillEditor
  },
  setup(props, context) {
    const can_edit = props.can_edit;
    const toast = useToast();
    const invoice_settings = ref([]);
    const invoice_footer = ref('');
    const invoice_content = ref('');
    const invoice_logo = ref(null);
    const maker_checker = ref(false);
    const request_finance_add_repayment = ref(false);
    const auto_request_financing = ref(false);
    const editor = ref(null);
    const can_approve = ref(false);
    const can_submit_upper = ref(false);
    const can_submit_lower = ref(false);

    const getPurchaseOrderSettings = () => {
      axios
        .get('../settings/purchase-order-settings')
        .then(response => {
          invoice_settings.value = response.data.settings;
          maker_checker.value = invoice_settings.value.maker_checker_creating_updating;
          request_finance_add_repayment.value = invoice_settings.value.request_finance_add_repayment;
          auto_request_financing.value = invoice_settings.value.auto_request_financing;
          invoice_content.value = invoice_settings.value.description;
          invoice_footer.value = invoice_settings.value.footer;
          if (invoice_settings.value.proposed_update) {
            can_approve.value =
              response.data.can_approve && response.data.user_id != invoice_settings.value.proposed_update.user_id;
          }
        })
        .catch(err => {
          console.log(err);
        });
    };

    context.expose({ getPurchaseOrderSettings });

    const updateInvoiceSettings = () => {
      const formData = new FormData();
      formData.append('maker_checker_creating_updating', maker_checker.value);
      formData.append('auto_request_financing', auto_request_financing.value);
      formData.append('request_finance_add_repayment', request_finance_add_repayment.value);
      formData.append('company_logo', invoice_logo.value);
      formData.append('description', invoice_content.value);
      formData.append('footer', invoice_footer.value);
      axios
        .post('../settings/purchase-order-settings/update', formData)
        .then(response => {
          getPurchaseOrderSettings();
          invoice_logo.value = null;
          toast.success(response.data.message);
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred while updating PO settings');
        });
    };

    const changeFile = e => {
      e.preventDefault();
      let files = e.target.files || e.dataTransfer.files;
      if (!files.length) {
        return;
      }
      invoice_logo.value = files[0];
    };

    const changeDescription = () => {
      invoice_content.value = editor.value.getHTML().toString();
    };

    const updateStatus = status => {
      axios
        .get('../settings/purchase-order-settings/status/' + status + '/update')
        .then(response => {
          getPurchaseOrderSettings();
          invoice_logo.value = null;
          toast.success(response.data.message);
          document.getElementById('close-modal').click();
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred while updating PO settings');
        });
    };

    watch(
      [maker_checker, request_finance_add_repayment, invoice_logo, invoice_content, invoice_footer],
      ([maker_checker, request_finance_add_repayment, invoice_logo, invoice_content, invoice_footer]) => {
        if (
          maker_checker != invoice_settings.value.maker_checker_creating_updating ||
          request_finance_add_repayment != invoice_settings.value.request_finance_add_repayment
        ) {
          can_submit_upper.value = true;
        } else {
          can_submit_upper.value = false;
        }

        if (
          invoice_footer != invoice_settings.value.footer ||
          invoice_content != invoice_settings.value.description ||
          invoice_logo
        ) {
          can_submit_lower.value = true;
        } else {
          can_submit_lower.value = false;
        }
      }
    );

    return {
      can_edit,
      editor,
      invoice_settings,
      invoice_content,
      invoice_logo,
      invoice_footer,
      maker_checker,
      request_finance_add_repayment,
      auto_request_financing,
      can_approve,
      updateStatus,
      changeFile,
      changeDescription,
      updateInvoiceSettings,
      can_submit_lower,
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
  margin-left: 55px !important;
}
</style>
