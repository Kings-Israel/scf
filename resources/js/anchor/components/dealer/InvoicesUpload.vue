<template>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadInvoices" type="button">
    {{ $t('Upload Invoices') }}
  </button>
  <div class="modal modal-top fade" id="uploadInvoices" tabindex="-1">
    <div class="modal-dialog">
      <form
        class="modal-content"
        method="POST"
        enctype="multipart/form-data"
        id="upload-invoices-form"
        @submit.prevent="uploadInvoices()"
      >
        <div class="modal-header">
          <h5 class="modal-title" id="modalTopTitle">{{ $t('Upload Invoices') }}</h5>
          <button
            type="button"
            class="btn-close"
            id="close-upload-modal"
            data-bs-dismiss="modal"
            aria-label="Close"
          ></button>
        </div>
        <div class="modal-body">
          <div class="form-group mt-2">
            <input
              class="form-control"
              id="file-upload"
              name="invoices"
              ref="fileUpload"
              type="file"
              accept=".xlsx"
              @change="selectFile"
              required
              :key="selected_file"
            />
          </div>
          <span class="py-1 text-danger d-none" id="show-download-error">{{
            $t('An error occurred while downloading sample')
          }}</span>
          <a
            href="invoices/upload/error-report/download"
            v-if="show_error_download_link"
            class="btn btn-danger btn-sm mt-2"
          >
            {{ $t('Click Here to Download Error Report') }}
          </a>
        </div>
        <div class="modal-footer">
          <a href="invoices/sample/download" id="download-invoices" class="btn btn-label-warning">{{
            $t('Download Sample')
          }}</a>
          <a href="invoices/dealers" target="_blank" id="view-dealers" class="btn btn-label-info">{{
            $t('View Dealers')
          }}</a>
          <button type="submit" class="btn btn-primary" id="submit-invoices-btn" :disabled="!can_submit">
            {{ $t('Submit') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
<script>
import moment from 'moment';
import { ref, inject } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
export default {
  name: 'InvoicesUpload',
  props: ['dealer_financing'],
  setup(props) {
    const base_url = inject('baseURL');
    const invoices_file = ref(null);
    const dealer_financing = props.dealer_financing;
    const toast = useToast();
    const can_submit = ref(true);
    const show_error_download_link = ref(false);
    const selected_file = ref(0);

    const uploadInvoices = () => {
      can_submit.value = false;
      $('#submit-invoices-btn').html('Uploading');
      const formData = new FormData();
      formData.append('invoices', invoices_file.value);

      axios
        .post(base_url + 'dealer/invoices/import', formData)
        .then(response => {
          window.location.reload();
        })
        .catch(error => {
          if (error.response.status == 422) {
            selected_file.value++;
            toast.error(error.response.data.message);
            return;
          }
          if (error.response.data.uploaded > 0) {
            toast.success(error.response.data.uploaded + ' uploaded.');
          }
          if (error.response.data.total_rows - error.response.data.uploaded > 0) {
            toast.error('Failed to upload.');
            toast.error(error.response.data.total_rows - error.response.data.uploaded + ' failed to upload.');
            show_error_download_link.value = true;
            selected_file.value++;
          } else {
            document.getElementById('close-upload-modal').click();
            setTimeout(() => {
              window.location.reload();
            }, 3000);
          }
        })
        .finally(() => {
          can_submit.value = true;
          $('#submit-invoices-btn').html('Submit');
        });
    };

    const selectFile = e => {
      e.preventDefault();
      let files = e.target.files || e.dataTransfer.files;
      if (!files.length) {
        return;
      }
      invoices_file.value = files[0];
    };
    return {
      selected_file,
      dealer_financing,
      can_submit,
      show_error_download_link,
      selectFile,
      uploadInvoices
    };
  }
};
</script>
