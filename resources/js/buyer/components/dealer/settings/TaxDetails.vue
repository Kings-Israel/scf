<template>
  <div class="py-3">
    <table class="table">
      <tbody class="table-border-bottom-0">
        <tr class="text-nowrap" v-for="tax in taxes" :key="tax.id">
          <td>
            <div class="">
              <input type="text" class="form-control" :value="tax.tax_name" readonly />
            </div>
          </td>
          <td>
            <div class="">
              <input type="text" class="form-control" :value="tax.tax_number" readonly />
            </div>
          </td>
          <td>
            <div class="">
              <input type="text" class="form-control" :value="tax.tax_value" readonly />
            </div>
          </td>
          <td>
            <div v-if="can_edit">
              <i
                class="tf-icons ti ti-trash ti-xs me-1 text-danger"
                @click="deleteTax(tax.id)"
                style="cursor: pointer"
              ></i>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
    <hr />
    <form @submit.prevent="addTax" method="post">
      <table class="table">
        <tbody class="table-border-bottom-0">
          <tr class="text-nowrap">
            <td>
              <div class="">
                <input type="text" class="form-control" v-model="tax_name" placeholder="Tax Name" />
              </div>
            </td>
            <td>
              <div class="">
                <input type="text" class="form-control" v-model="tax_number" placeholder="Tax Number" />
              </div>
            </td>
            <td>
              <div class="">
                <input
                  type="number"
                  step=".01"
                  min="0"
                  class="form-control"
                  v-model="tax_value"
                  placeholder="Tax Value"
                />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <div class="d-flex px-3" v-if="can_edit">
        <button class="btn btn-primary" type="submit">Add</button>
      </div>
    </form>
  </div>
</template>
<script>
import { ref } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
export default {
  name: 'TaxDetails',
  props: ['can_edit'],
  setup(props, context) {
    const can_edit = props.can_edit;
    const toast = useToast();
    const taxes = ref([]);
    const tax_name = ref('');
    const tax_number = ref('');
    const tax_value = ref('');

    const getTaxes = () => {
      axios.get('../settings/taxes').then(response => {
        taxes.value = response.data;
      });
    };

    context.expose({ getTaxes });

    const addTax = () => {
      axios
        .post('settings/taxes', {
          tax_name: tax_name.value,
          tax_number: tax_number.value,
          tax_value: tax_value.value
        })
        .then(() => {
          getTaxes();
          toast.success('Tax added successfully');
          tax_name.value = '';
          tax_number.value = '';
          tax_value.value = '';
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred while adding');
        });
    };

    const deleteTax = id => {
      axios
        .delete(`../settings/taxes/${id}`)
        .then(() => {
          getTaxes();
          toast.success('Tax deleted successfully');
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred while deleting');
        });
    };

    return {
      can_edit,
      taxes,
      tax_name,
      tax_number,
      tax_value,
      addTax,
      deleteTax
    };
  }
};
</script>
