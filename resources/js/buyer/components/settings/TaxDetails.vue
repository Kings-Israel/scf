<template>
<div>
  <table class="table">
    <tbody class="table-border-bottom-0">
      <tr class="text-nowrap" v-for="tax in taxes" :key="tax.id">
        <td>
          <div class="">
            <input type="text" class="form-control" :value="tax.tax_name" readonly>
          </div>
        </td>
        <td>
          <div class="">
            <input type="text" class="form-control" :value="tax.tax_number" readonly>
          </div>
        </td>
        <td>
          <div class="">
            <input type="text" class="form-control" :value="tax.tax_value" readonly>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
  <div class="d-flex px-3">
    <button class="btn btn-primary">
      {{ $t('Add More') }}
    </button>
  </div>
</div>
</template>
<script>
import { ref } from 'vue'
import axios from 'axios'
export default {
  name: 'TaxDetails',
  setup(props, context) {
    const taxes = ref([])

    const getTaxes = () => {
      axios.get('settings/taxes')
        .then(response => {
          taxes.value = response.data
        })
    }

    context.expose({ getTaxes })

    return {
      taxes
    }
  }
}
</script>
