<template>
  <div class="card p-2">
    <div class="d-flex justify-content-between">
      <div class="d-flex flex-wrap">
      </div>
      <div class="d-flex">
        <div class="mx-1">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page" style="height: fit-content">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <button
          type="button"
          class="btn btn-primary text-nowrap d-none"
          v-if="can_upload"
          data-bs-toggle="modal"
          data-bs-target="#addRate"
          @click="addNewRate"
        >
          New Rate
        </button>
        <div class="modal modal-top fade" id="addRate" tabindex="-1">
          <div class="modal-dialog">
            <form class="modal-content" method="POST" @submit.prevent="addNewBranch">
              <div class="modal-header">
                <h5 class="modal-title" id="modalTopTitle">Add Conversion Rate</h5>
                <button
                  type="button"
                  class="btn-close"
                  id="close-upload-modal"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                ></button>
              </div>
              <div class="modal-body my-1">
                <div class="form-group">
                  <label for="Name" class="form-label">From Currency</label>
                  <select name="" v-model="from_currency" id="" class="form-control">
                    <option value="">Select Currency</option>
                    <option v-for="currency in from_currency_options" :key="currency.id" :value="currency.code">{{ currency.name }}</option>
                  </select>
                </div>
                <div class="form-group my-1">
                  <label for="Name" class="form-label">To Currency</label>
                  <select name="" v-model="to_currency" id="" class="form-control">
                    <option value="">Select Currency</option>
                    <option v-for="currency in to_currency_options" :key="currency.id" :value="currency.code">{{ currency.name }}</option>
                  </select>
                </div>
                <div class="form-group my-1">
                  <label for="Name" class="form-label">Rate</label>
                  <input type="number" step=".01" name="name" id="" class="form-control" v-model="rate" />
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Submit</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>From Currency</th>
            <th>To Currency</th>
            <th>Rate</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr class="text-nowrap" v-for="data in rates.data" :key="data.id">
            <td>
              {{ data.from_currency }}
            </td>
            <td>{{ data.to_currency }}</td>
            <td>{{ data.rate }}</td>
            <td class="flex">
              <i
                class="ti ti-pencil ti-sm text-warning mx-1"
                @click="editBranch(data)"
                data-bs-toggle="modal"
                :data-bs-target="'#rate-' + data.id"
                style="cursor: pointer"
              ></i>
              <div class="modal modal-top fade" :id="'rate-' + data.id" tabindex="-1">
                <div class="modal-dialog">
                  <form class="modal-content" method="POST" @submit.prevent="updateBranch">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalTopTitle">Edit Conversion Rate</h5>
                      <button
                        type="button"
                        class="btn-close"
                        id="close-upload-modal"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                      ></button>
                    </div>
                    <div class="modal-body my-1">
                      <div class="form-group">
                        <label for="Name" class="form-label">From Currency</label>
                        <select name="" v-model="from_currency" id="" class="form-control" disabled>
                          <option value="">Select Currency</option>
                          <option v-for="currency in from_currency_options" :selected="currency.code == from_currency" :key="currency.id" :value="currency.code">{{ currency.name }}</option>
                        </select>
                      </div>
                      <div class="form-group my-1">
                        <label for="Name" class="form-label">To Currency</label>
                        <select name="" v-model="to_currency" id="" class="form-control" disabled>
                          <option value="">Select Currency</option>
                          <option v-for="currency in to_currency_options" :selected="currency.code == to_currency" :key="currency.id" :value="currency.code">{{ currency.name }}</option>
                        </select>
                      </div>
                      <div class="form-group my-1">
                        <label for="Name" class="form-label">Rate</label>
                        <input type="number" step=".01" name="name" id="" class="form-control" v-model="rate" />
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                  </form>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination
      :from="rates.from"
      :to="rates.to"
      :links="rates.links"
      :next_page="rates.next_page_url"
      :prev_page="rates.prev_page_url"
      :total_items="rates.total"
      :first_page_url="rates.first_page_url"
      :last_page_url="rates.last_page_url"
      @change-page="changePage"
    ></pagination>
  </div>
</template>
<script>
import { ref, watch, onMounted, inject } from 'vue';
import { useToast } from 'vue-toastification';
import axios from 'axios';
import Pagination from '../partials/Pagination.vue';
export default {
  name: 'Branches',
  components: {
    Pagination
  },
  props: ['bank', 'can_upload'],
  setup(props) {
    const toast = useToast();
    const base_url = inject('baseURL');
    const can_upload = props.can_upload;

    const rates = ref([]);

    const all_currencies = ref([])

    const per_page = ref(50);

    const from_currency_options = ref([])
    const to_currency_options = ref([])

    const from_currency = ref('');
    const to_currency = ref('');
    const rate = ref('');

    const edit_id = ref('');

    const showEditModal = ref(false);

    const getBranches = async () => {
      await axios
        .get(base_url + props.bank + '/configurations/conversion-rates/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          rates.value = response.data.data;
          all_currencies.value = response.data.currencies
        })
        .catch(err => {
          console.log(err);
        });
    };

    const editBranch = data => {
      edit_id.value = data.id;
      from_currency.value = data.from_currency;
      from_currency_options.value = all_currencies.value
      all_currencies.value.forEach(currency => {
        if (currency.code != from_currency.value) {
          to_currency_options.value.push(currency)
        }
      })
      to_currency.value = data.to_currency;
      rate.value = data.rate;
      showEditModal.value = true;
    };

    const addNewRate = () => {
      from_currency_options.value = all_currencies.value
    }

    watch([from_currency], ([old_from_currency]) => {
      to_currency_options.value = []
      // to_currency.value = ''
      all_currencies.value.forEach(currency => {
        if (currency.code != old_from_currency) {
          to_currency_options.value.push(currency)
        }
      })
    })

    const addNewBranch = () => {
      axios
        .post(base_url + props.bank + '/configurations/conversion-rates/store', {
          from_currency: from_currency.value,
          to_currency: to_currency.value,
          rate: rate.value,
        })
        .then(res => {
          toast.success(res.data.message);
          from_currency.value = '';
          to_currency.value = '';
          rate.value = '';
          setTimeout(() => {
            window.location.reload();
          }, 4000);
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    const updateBranch = () => {
      axios
        .post(base_url + props.bank + '/configurations/conversion-rates/'+edit_id.value+'/update', {
          from_currency: from_currency.value,
          to_currency: to_currency.value,
          rate: rate.value,
        })
        .then((res) => {
          toast.success(res.data.message);
          edit_id.value = '';
          from_currency.value = '';
          to_currency.value = '';
          rate.value = '';
          showEditModal.value = false;
          setTimeout(() => {
            window.location.reload();
          }, 4000);
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    const approveChange = (type, id, status) => {
      axios
        .post(base_url + props.bank + '/configurations/change/status/update', {
          type: type,
          id: id,
          status: status
        })
        .then(res => {
          toast.success(res.data.message);
          setTimeout(() => {
            window.location.reload();
          }, 3000);
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    onMounted(() => {
      getBranches();
    });

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          rates.value = response.data;
        });
    };

    return {
      per_page,
      can_upload,
      all_currencies,
      rates,
      from_currency,
      from_currency_options,
      to_currency,
      to_currency_options,
      rate,
      showEditModal,
      addNewRate,
      addNewBranch,
      updateBranch,
      editBranch,
      approveChange,
      changePage
    };
  }
};
</script>

<style scoped>
.no-label {
  margin-left: -60px !important;
}
.form-check {
  margin-left: 30px !important;
}
.yes-label {
  margin-left: 45px !important;
}
</style>
