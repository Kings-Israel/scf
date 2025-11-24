<template>
  <div>
    <h5 class="fw-bold py-3 mb-2">
      <span class="fw-light px-3">Bank A/C Details</span>
    </h5>
    <table class="table">
      <tbody class="table-border-bottom-0">
        <tr class="text-nowrap" v-for="bank_account in bank_accounts" :key="bank_account.id">
          <td>
            <div class="">
              <input type="text" class="form-control" :value="bank_account.name_as_per_bank" readonly />
            </div>
          </td>
          <td>
            <div class="">
              <input type="text" class="form-control" :value="bank_account.account_number" readonly />
            </div>
          </td>
          <td>
            <div class="">
              <input type="text" class="form-control" :value="bank_account.bank_name" readonly />
            </div>
          </td>
          <td>
            <div class="">
              <input type="text" class="form-control" :value="bank_account.branch" readonly />
            </div>
          </td>
          <td>
            <div class="">
              <input type="text" class="form-control" :value="bank_account.swift_code" readonly />
            </div>
          </td>
          <td>
            <div class="">
              <input type="text" class="form-control" :value="bank_account.account_type" readonly />
            </div>
          </td>
          <td>
            <div class="">
              <input type="text" class="form-control" placeholder="Currency" readonly />
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
<script>
import { ref } from 'vue';
import axios from 'axios';
export default {
  name: 'BankAccounts',
  setup(props, context) {
    const bank_accounts = ref([]);

    const getBankAccounts = () => {
      axios
        .get('../settings/bank-accounts')
        .then(response => {
          bank_accounts.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    context.expose({ getBankAccounts });

    return {
      bank_accounts
    };
  }
};
</script>
