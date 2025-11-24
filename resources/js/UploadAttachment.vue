<template>
  <div class="modal fade" tabindex="-2" aria-hidden="true" :id="'add-attachment-' + invoice_details.id">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalCenterTitle">
            {{ $t('Attachments for Invoice') }}: {{ invoice_details.invoice_number }}
          </h5>
          <button
            type="button"
            class="btn-close"
            :id="'close-upload-attachment-' + invoice_details.id"
            data-bs-dismiss="modal"
            aria-label="Close"
          ></button>
        </div>
        <div class="modal-body">
          <div class="table-responsive">
            <table class="table">
              <thead class="bg-label-primary">
                <tr>
                  <th>{{ $t('Attached On') }}</th>
                  <th>{{ $t('Attached By') }}</th>
                  <th>{{ $t('User Type') }}</th>
                  <th>{{ $t('File Name') }}</th>
                  <th>{{ $t('View') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(attachment, key) in invoice_details.media" :key="key">
                  <td>
                    {{ moment(attachment.created_at).format('DD MMM YYY') }}
                  </td>
                  <td>
                    <span v-if="attachment.custom_properties && attachment.custom_properties.user_name">
                      {{ attachment.custom_properties.user_name }}
                    </span>
                  </td>
                  <td>
                    <span v-if="attachment.custom_properties && attachment.custom_properties.user_type">
                      {{
                        attachment.custom_properties.user_type === 'App\\Models\\Company' ? 'Company User' : 'Bank User'
                      }}
                    </span>
                  </td>
                  <td>
                    {{ attachment.name }}
                  </td>
                  <td>
                    <a target="_blank" :href="attachment.original_url"> <i class="ti ti-eye text-xs"></i></a>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <form @submit.prevent="uploadFile" class="d-flex gap-2 mt-4">
            <div>
              <label for="Add Attachements" class="form-label">{{ $t('Select Attachments') }}</label>
              <input type="file" accept=".pdf" @change="selectFile" required multiple="multiple" class="form-control" />
            </div>
            <button style="height: fit-content" class="btn btn-primary" type="submit" :disabled="uploading_file">
              {{ uploading_file ? $t('Processing...') : $t('Submit') }}
            </button>
          </form>
          <div v-if="selected_file.length > 0">
            <h6 class="mt-2 mb-1">{{ $t('Selected Files') }}</h6>
            <span class="d-flex flex-column gap-" v-for="selected in selected_file" :key="selected">
              {{ selected.name }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { ref } from 'vue';
import axios from 'axios';
import moment from 'moment';
import { useToast } from 'vue-toastification';

export default {
  props: ['invoiceDetails'],
  setup(props, context) {
    const toast = useToast();
    const invoice_details = props.invoiceDetails;
    const selected_file = ref([]);
    const uploading_file = ref(false);
    const uploading_key = ref(0);

    const selectFile = e => {
      e.preventDefault();
      selected_file.value = e.target.files;
    };

    const uploadFile = async () => {
      uploading_file.value = true;
      const formData = new FormData();
      formData.append('id', props.invoiceDetails.id);
      let index = 0;
      for (let file of selected_file.value) {
        formData.append('files[' + index + ']', file);
        index++;
      }

      axios
        .post('/invoice/attachment/upload', formData)
        .then(() => {
          document.getElementById('close-upload-attachment-' + props.invoiceDetails.id).click();
          toast.success('Attachment added successfully');
          uploading_key.value++;
        })
        .catch(err => {
          const message = Object.values(err.response.data)[0];
          toast.error(message[0]);
        })
        .finally(() => {
          uploading_file.value = false;
        });
    };

    return {
      moment,
      invoice_details,
      selectFile,
      uploadFile,
      selected_file,
      uploading_file
    };
  }
};
</script>
