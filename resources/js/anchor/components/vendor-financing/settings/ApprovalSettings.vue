<template>
  <div>
    <h5 class="fw-bold py-3 mb-2 d-flex flex-column">
      <span class="fw-light px-3">{{ $t('Discounting') }}</span>
      <small class="px-3">{{ $t('Auto') }} - {{ $t('All anchor approved invoices will be sent to bank automatically') }}</small>
    </h5>
    <div class="row px-3 mb-2" v-for="program in programs" :key="program.id">
      <div class="col-md-4 d-flex flex-column">
        <label for="html5-text-input" class="col-form-label">{{ program.anchor.name }}</label>
      </div>
      <div class="col-md-4">
        <div class="form-check form-switch mb-2">
          <label class="form-check-label no-label" for="flexSwitchCheckChecked">{{ $t('Auto') }}</label>
          <input class="form-check-input" type="checkbox" id="flexSwitchCheckChecked" :checked="program.vendor_configuration.request_auto_finance" @change="update(program.id)">
          <label class="form-check-label yes-label" for="flexSwitchCheckChecked">{{ $t('Manual') }}</label>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { ref } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'
export default {
  name: 'ApprovalSettings',
  setup(props, context) {
    const toast = useToast()
    const programs = ref([])

    const getAnchors = () => {
      axios.get('/settings/anchors')
        .then(response => {
          programs.value = response.data
        })
        .catch(error => {
          console.log(error)
        })
    }

    context.expose({ getAnchors })

    const update = id => {
      axios.post('settings/anchors/update', {
        program_id: id
      })
        .then(() => {
          getAnchors()
          toast.success('Updated successfully')
        })
        .catch(err => {
          console.log(err)
          toast.error('An error occurred')
        })
    }

    return  {
      programs,
      update,
    }
  }
}
</script>
<style>
.no-label {
  margin-left: -80px !important;
}
.yes-label {
  margin-left: 45px !important;
}
</style>
