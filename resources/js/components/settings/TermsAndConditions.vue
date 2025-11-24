<template>
  <div class="card p-2">
    <div class="d-flex justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Name Search" class="form-label">{{ $t('Name Search') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control"
            id="defaultFormControlInput"
            placeholder="Name"
            aria-describedby="defaultFormControlHelp"
            v-model="name_search"
          />
        </div>
        <div class="">
          <label for="Status Search" class="form-label">{{ $t('Status Search') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect1" v-model="status_search">
            <option value="">{{ $t('Status') }}</option>
            <option value="active">{{ $t('Active') }}</option>
            <option value="inactive">{{ $t('Inactive') }}</option>
          </select>
        </div>
        <div class="">
          <label for="Product Type Search" class="form-label">{{ $t('Product Type Search') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect1" v-model="product_type_search">
            <option value="">{{ $t('Product Type') }}</option>
            <option value="vendor_financing">{{ $t('Vendor Financing Receivable') }}</option>
            <option value="factoring">{{ $t('Factoring') }}</option>
            <option value="dealer_financing">{{ $t('Dealer Financing') }}</option>
          </select>
        </div>
        <div class="table-search-btn">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="table-clear-btn">
          <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex justify-content-end gap-1 mt-auto">
        <div class="">
          <select class="form-select" v-model="per_page" style="height: fit-content">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <button
          type="button"
          class="btn btn-primary text-nowrap btn-md"
          v-if="can_upload"
          data-bs-toggle="modal"
          data-bs-target="#addBaseRate"
        >
          {{ $t('New Terms and Conditions') }}
        </button>
        <div class="modal modal-top fade modal-lg" id="addBaseRate" tabindex="-1">
          <div class="modal-dialog">
            <form class="modal-content" method="POST" @submit.prevent="addNewBaseRate">
              <div class="modal-header">
                <h5 class="modal-title" id="modalTopTitle">{{ $t('Add New Terms and Conditions') }}</h5>
                <button
                  type="button"
                  class="btn-close"
                  id="close-add-modal"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                ></button>
              </div>
              <div class="modal-body my-1">
                <div class="form-group">
                  <label for="Name" class="form-label">{{ $t('Name') }}</label>
                  <input type="text" name="name" id="" class="form-control" v-model="name" />
                </div>
                <div class="mb-4">
                  <label for="Body" class="form-label">{{ $t('Body') }}</label>
                  <QuillEditor theme="snow" ref="create_editor" @editorChange="changeDescription" />
                </div>
                <div class="form-group my-1">
                  <label for="Name" class="form-label">{{ $t('Product Type') }}</label>
                  <select name="" v-model="product_type" id="" class="form-select">
                    <option value="vendor_financing">{{ $t('Vendor Financing') }}</option>
                    <option value="factoring">{{ $t('Factoring') }}</option>
                    <option value="dealer_financing">{{ $t('Dealer Financing') }}</option>
                  </select>
                </div>
                <!-- <div class="form-group my-1">
                <label for="Name" class="form-label">Status</label>
                <select name="" v-model="status" id="" class="form-select">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div> -->
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ $t('Close') }}</button>
                <button type="submit" class="btn btn-primary">{{ $t('Submit') }}</button>
              </div>
            </form>
          </div>
        </div>
      </div>
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
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('Name') }}</th>
            <th>{{ $t('Product Type') }}</th>
            <th>{{ $t('Status') }}</th>
            <th>{{ $t('Actions') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr class="text-nowrap" v-for="data in rates.data" :key="data.id">
            <td>
              {{ data.name }}
              <i
                v-if="data.change || data.proposed_update"
                class="tf-icons ti ti-info-circle ti-xs text-danger"
                title="Terms and Conditions creation/updation awating approval"
              ></i>
            </td>
            <td>
              <span class="m_title">
                {{ data.product_type.replaceAll('_', ' ') }}
              </span>
            </td>
            <td>
              <span v-if="data.status == 'Active'" class="badge bg-label-success">{{ data.status }}</span>
              <span v-if="data.status == 'Inactive'" class="badge bg-label-danger">{{ data.status }}</span>
            </td>
            <td class="flex">
              <i
                class="ti ti-thumb-up ti-sm text-success"
                v-if="!data.change && data.status == 'Active'"
                title="Click to Deactivate Rate"
                style="cursor: pointer"
                @click="updateActiveStatus(data.id, 'inactive')"
              ></i>
              <i
                class="ti ti-thumb-down ti-sm text-danger"
                v-if="!data.change && data.status == 'Inactive'"
                title="Click to Activate Rate"
                style="cursor: pointer"
                @click="updateActiveStatus(data.id, 'active')"
              ></i>
              <i
                v-if="!data.change && !data.proposed_update && can_update"
                class="ti ti-pencil ti-sm text-warning mx-1"
                @click="editBaseRate(data)"
                data-bs-toggle="modal"
                :data-bs-target="'#rate-' + data.id"
                style="cursor: pointer"
              ></i>
              <div class="modal modal-top fade modal-lg" :id="'rate-' + data.id" tabindex="-1">
                <div class="modal-dialog">
                  <form class="modal-content" method="POST" @submit.prevent="updateBaseRate">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalTopTitle">{{ $t('Edit Terms and Conditions') }}</h5>
                      <button
                        type="button"
                        class="btn-close"
                        :id="'close-edit-modal-' + data.id"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                      ></button>
                    </div>
                    <div class="modal-body my-1">
                      <div class="form-group">
                        <label for="Name" class="form-label">{{ $t('Name') }}</label>
                        <input type="text" class="form-control" v-model="name" />
                      </div>
                      <div class="mb-4">
                        <label for="Body" class="form-label">{{ $t('Body') }}</label>
                        <QuillEditor theme="snow" ref="editor" @editorChange="editDescription" />
                      </div>
                      <div class="form-group my-1">
                        <label for="Name" class="form-label">{{ $t('Product Type') }}</label>
                        <select name="" v-model="product_type" id="" class="form-select">
                          <option value="vendor_financing">{{ $t('Vendor Financing') }}</option>
                          <option value="factoring">{{ $t('Factoring') }}</option>
                          <option value="dealer_financing">{{ $t('Dealer Financing') }}</option>
                        </select>
                      </div>
                      <div class="form-group my-1">
                        <label for="Name" class="form-label">{{ $t('Status') }}</label>
                        <select name="" v-model="status" id="" class="form-select">
                          <option value="active">{{ $t('Active') }}</option>
                          <option value="inactive">{{ $t('Inactive') }}</option>
                        </select>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        {{ $t('Close') }}
                      </button>
                      <button type="submit" class="btn btn-primary">{{ $t('Submit') }}</button>
                    </div>
                  </form>
                </div>
              </div>
              <i
                v-if="data.proposed_update && data.proposed_update.can_approve"
                class="ti ti-table ti-sm text-danger mx-1"
                title="View Proposed Changes"
                data-bs-toggle="modal"
                :data-bs-target="'#proposed-change-' + data.id"
                style="cursor: pointer"
              ></i>
              <div
                v-if="data.proposed_update && data.proposed_update.can_approve"
                class="modal modal-top fade"
                :id="'proposed-change-' + data.id"
                tabindex="-1"
              >
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalTopTitle">{{ $t('Proposed Terms and Conditions Changes') }}</h5>
                      <button
                        type="button"
                        class="btn-close"
                        :id="'close-changes-modal-' + data.id"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                      ></button>
                    </div>
                    <div class="modal-body my-1">
                      <div v-for="(change, key) in data.proposed_update.changes" :key="key" class="d-flex">
                        <div class="m_title">{{ key }}:</div>
                        <div class="mx-2">{{ change }}</div>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        {{ $t('Close') }}
                      </button>
                      <button
                        type="submit"
                        class="btn btn-danger"
                        @click="approveChange('App\\Models\\TermsConditionsConfig', data.id, 'reject')"
                      >
                        {{ $t('Reject') }}
                      </button>
                      <button
                        type="submit"
                        class="btn btn-primary"
                        @click="approveChange('App\\Models\\TermsConditionsConfig', data.id, 'approve')"
                      >
                        {{ $t('Approve') }}
                      </button>
                    </div>
                  </div>
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
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

export default {
  name: 'BaseRates',
  components: {
    Pagination,
    QuillEditor
  },
  props: ['bank', 'can_update', 'can_upload'],
  setup(props) {
    const toast = useToast();
    const base_url = inject('baseURL');
    const can_update = props.can_update;
    const can_upload = props.can_upload;
    const name_search = ref('');
    const status_search = ref('');
    const product_type_search = ref('');

    const rates = ref([]);

    const per_page = ref(50);

    const name = ref('');
    const product_type = ref('');
    const status = ref('');
    const body = ref('');

    const editor = ref(null);
    const create_editor = ref(null);

    const edit_id = ref('');

    const showEditModal = ref(false);

    const changeDescription = () => {
      body.value = create_editor.value.getHTML().toString();
    };

    const editDescription = () => {
      body.value = editor.value.getHTML().toString();
    };

    const getRates = async () => {
      await axios
        .get(base_url + props.bank + '/configurations/terms-and-conditions/data', {
          params: {
            per_page: per_page.value,
            name: name_search.value,
            status: status_search.value,
            product_type: product_type_search.value
          }
        })
        .then(response => {
          rates.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    const editBaseRate = data => {
      edit_id.value = data.id;
      name.value = data.name;
      product_type.value = data.product_type;
      status.value = data.status == 'Active' ? 'active' : 'inactive';
      body.value = data.body;
      showEditModal.value = true;
    };

    const addNewBaseRate = () => {
      axios
        .post(base_url + props.bank + '/configurations/terms-and-conditions/store', {
          name: name.value,
          product_type: product_type.value,
          status: status.value,
          body: body.value
        })
        .then(() => {
          toast.success('Terms and Conditions added successfully');
          name.value = '';
          body.value = '';
          status.value = '';
          product_type.value = false;
          getRates();
          document.getElementById('close-add-modal').click();
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    const updateBaseRate = () => {
      axios
        .post(base_url + props.bank + '/configurations/terms-and-conditions/update', {
          id: edit_id.value,
          name: name.value,
          status: status.value,
          product_type: product_type.value,
          body: body.value
        })
        .then(res => {
          toast.success(res.data.message);
          edit_id.value = '';
          name.value = '';
          status.value = '';
          body.value = '';
          product_type.value = false;
          showEditModal.value = false;
          getRates();
          document.getElementById('close-edit-modal-' + edit_id.value).click();
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    const updateActiveStatus = (terms_and_conditions, status) => {
      axios
        .get(
          base_url + props.bank + `/configurations/terms-and-conditions/${terms_and_conditions}/status/${status}/update`
        )
        .then(res => {
          getRates();
          toast.success(res.data.message);
        })
        .catch(err => {
          console.log(err.response.data.message);
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
          getRates();
          document.getElementById('close-changes-modal-' + id).click();
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    onMounted(() => {
      getRates();
    });

    const filter = () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      axios
        .get(base_url + props.bank + '/configurations/terms-and-conditions/data', {
          params: {
            per_page: per_page.value,
            name: name_search.value,
            status: status_search.value,
            product_type: product_type_search.value
          }
        })
        .then(response => {
          rates.value = response.data;
        })
        .finally(() => {
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = () => {
      name_search.value = '';
      status_search.value = '';
      product_type_search.value = '';
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../../assets/img/spinner.svg" />');
      axios
        .get(base_url + props.bank + '/configurations/terms-and-conditions/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          rates.value = response.data;
        })
        .finally(() => {
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    watch(per_page, per_page => {
      axios
        .get(base_url + props.bank + '/configurations/terms-and-conditions/data', {
          params: {
            per_page: per_page,
            name: name_search.value,
            status: status_search.value
          }
        })
        .then(response => {
          rates.value = response.data;
        });
    });

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            per_page: per_page.value,
            name: name_search.value,
            status: status_search.value,
            product_type: product_type_search.value
          }
        })
        .then(response => {
          rates.value = response.data;
        });
    };

    return {
      per_page,
      can_upload,
      can_update,
      rates,
      name,
      editor,
      create_editor,
      status,
      product_type,
      body,
      showEditModal,
      addNewBaseRate,
      updateBaseRate,
      updateActiveStatus,
      editBaseRate,
      changePage,
      changeDescription,
      approveChange,
      editDescription,
      filter,
      refresh,
      name_search,
      status_search,
      product_type_search
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
.m_title::first-letter {
  text-transform: uppercase;
}
</style>
