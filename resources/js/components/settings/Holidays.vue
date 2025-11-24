<template>
  <div class="card p-2">
    <div class="d-flex justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="Name" class="form-label">{{ $t('Name') }}</label>
          <input
            v-on:keyup.enter="filter"
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            :placeholder="$t('Name')"
            aria-describedby="defaultFormControlHelp"
            v-model="name_search"
          />
        </div>
        <div class="">
          <label for="Status" class="form-label">{{ $t('Status') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect1" v-model="status_search">
            <option value="">{{ $t('Status') }}</option>
            <option value="active">{{ $t('Active') }}</option>
            <option value="inactive">{{ $t('Inactive') }}</option>
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
          <select class="form-select" v-model="per_page" style="height: fit-content; width: 5rem">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <div class="">
          <button
            type="button"
            class="btn btn-primary text-nowrap"
            v-if="can_upload"
            data-bs-toggle="modal"
            data-bs-target="#addBaseRate"
          >
            {{ $t('Add New Holiday') }}
          </button>
        </div>
        <div class="modal modal-top fade" id="addBaseRate" tabindex="-1">
          <div class="modal-dialog">
            <form class="modal-content" method="POST" @submit.prevent="addNewBaseRate">
              <div class="modal-header">
                <h5 class="modal-title" id="modalTopTitle">{{ $t('Add New Holiday') }}</h5>
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
                  <label for="Name" class="form-label">{{ $t('Name') }}</label>
                  <input type="text" name="name" id="" class="form-control" v-model="name" />
                </div>
                <div class="form-group my-1">
                  <label for="Name" class="form-label">{{ $t('Date') }}</label>
                  <input type="date" min="0" name="name" id="" class="form-control" v-model="date" />
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ $t('Close') }}</button>
                <button type="submit" class="btn btn-primary">{{ $t('Submit') }}</button>
              </div>
            </form>
          </div>
        </div>
        <div>
          <button
            type="button"
            class="btn btn-primary btn-md text-nowrap"
            v-if="can_upload"
            data-bs-toggle="modal"
            data-bs-target="#uploadHolidays"
          >
            {{ $t('Bulk Upload') }}
          </button>
        </div>
        <div class="modal modal-top fade" id="uploadHolidays" tabindex="-1">
          <div class="modal-dialog">
            <form class="modal-content" method="POST" @submit.prevent="uploadHolidays">
              <div class="modal-header">
                <h5 class="modal-title" id="modalTopTitle">{{ $t('Upload Holidays') }}</h5>
                <button
                  type="button"
                  class="btn-close"
                  id="close-bulk-upload-modal"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                ></button>
              </div>
              <div class="modal-body my-1">
                <div class="form-group">
                  <label for="Name" class="form-label">{{ $t('Select File') }}</label>
                  <input type="file" accept=".xlsx,.csv" id="" class="form-control" @change="changeFile" />
                </div>
              </div>
              <div class="modal-footer">
                <button class="btn btn-warning" @click="downloadSample">{{ $t('Download Sample') }}</button>
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ $t('Close') }}</button>
                <button type="submit" class="btn btn-primary">{{ $t('Upload') }}</button>
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
            <th>{{ $t('Name') }}</th>
            <th>{{ $t('Date') }}</th>
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
                title="Holiday creation/updation awating approval"
              ></i>
            </td>
            <td>{{ moment(data.date).format(date_format) }}</td>
            <td>
              <span v-if="data.status == 'Active'" class="badge bg-label-success">{{ data.status }}</span>
              <span v-if="data.status == 'Inactive'" class="badge bg-label-danger">{{ data.status }}</span>
            </td>
            <td class="flex">
              <i
                class="ti ti-thumb-up ti-sm text-success"
                v-if="!data.change && data.status == 'Active'"
                title="Click to Deactivate Holiday"
                style="cursor: pointer"
                @click="updateActiveStatus(data.id, 'inactive')"
              ></i>
              <i
                class="ti ti-thumb-down ti-sm text-danger"
                v-if="!data.change && data.status == 'Inactive'"
                title="Click to Activate Holiday"
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
              <div class="modal modal-top fade" :id="'rate-' + data.id" tabindex="-1">
                <div class="modal-dialog">
                  <form class="modal-content" method="POST" @submit.prevent="updateBaseRate">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalTopTitle">{{ $t('Edit Holiday') }}</h5>
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
                        <label for="Name" class="form-label">{{ $t('Name') }}</label>
                        <input type="text" class="form-control" v-model="name" />
                      </div>
                      <div class="form-group my-1">
                        <label for="Name" class="form-label">{{ $t('Date') }}</label>
                        <input type="date" min="0" class="form-control" v-model="date" />
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
                      <h5 class="modal-title" id="modalTopTitle">{{ $t('Proposed Holiday Changes') }}</h5>
                      <button
                        type="button"
                        class="btn-close"
                        id="close-changes-modal"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                      ></button>
                    </div>
                    <div class="modal-body my-1">
                      <div v-for="(change, key) in data.proposed_update.changes" :key="key" class="row">
                        <div class="col-sm-12 col-md-6 m_title">{{ key }}:</div>
                        <div class="col-sm-12 col-md-6">{{ change }}</div>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        {{ $t('Close') }}
                      </button>
                      <button
                        type="submit"
                        class="btn btn-danger"
                        @click="approveChange('App\\Models\\BankHoliday', data.id, 'reject')"
                      >
                        {{ $t('Reject') }}
                      </button>
                      <button
                        type="submit"
                        class="btn btn-primary"
                        @click="approveChange('App\\Models\\BankHoliday', data.id, 'approve')"
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
import moment from 'moment';
import Pagination from '../partials/Pagination.vue';
export default {
  name: 'Holidays',
  components: {
    Pagination
  },
  props: ['bank', 'can_update', 'can_upload', 'date_format'],
  setup(props) {
    const toast = useToast();
    const base_url = inject('baseURL');
    const can_update = props.can_update;
    const can_upload = props.can_upload;
    const date_format = props.date_format;

    const rates = ref([]);

    const per_page = ref(50);

    const name = ref('');
    const date = ref('');

    const name_search = ref('');
    const status_search = ref('');

    const holidays_file = ref(null);

    const edit_id = ref('');

    const showEditModal = ref(false);

    const getRates = async () => {
      await axios
        .get(base_url + props.bank + '/configurations/holidays/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          rates.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    watch(per_page, per_page => {
      axios
        .get(base_url + props.bank + '/configurations/holidays/data', {
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

    const filter = async () => {
      await axios
        .get(base_url + props.bank + '/configurations/holidays/data', {
          params: {
            per_page: per_page.value,
            name: name_search.value,
            status: status_search.value
          }
        })
        .then(response => {
          rates.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    const refresh = async () => {
      name_search.value = '';
      status_search.value = '';
      await axios
        .get(base_url + props.bank + '/configurations/holidays/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          rates.value = response.data;
        })
        .catch(err => {
          console.log(err);
        });
    };

    const downloadSample = () => {
      axios
        .get(base_url + props.bank + '/configurations/holidays/sample/download', {
          responseType: 'arraybuffer',
          method: 'GET'
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Holidays.csv`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    const changeFile = e => {
      e.preventDefault();
      let files = e.target.files || e.dataTransfer.files;
      if (!files.length) {
        return;
      }
      holidays_file.value = files[0];
    };

    const uploadHolidays = () => {
      const formData = new FormData();
      formData.append('holidays', holidays_file.value);
      axios
        .post(base_url + props.bank + '/configurations/holidays/import', formData)
        .then(res => {
          toast.success('Holidays uploaded successfully');
          getRates();
        })
        .catch(error => {
          if (error.response.data.uploaded > 0) {
            toast.success(error.response.data.uploaded + ' uploaded.');
          }

          if (error.response.data.total_rows - error.response.data.uploaded > 0) {
            toast.error(error.response.data.total_rows - error.response.data.uploaded + ' failed to upload.');
            toast.error('An error occurred');
          }
          holidays_file.value = null;
          document.getElementById('close-bulk-upload-modal').click();
        })
        .finally(() => {
          getRates();
        });
    };

    const editBaseRate = data => {
      edit_id.value = data.id;
      name.value = data.name;
      date.value = data.date;
      showEditModal.value = true;
    };

    const addNewBaseRate = () => {
      axios
        .post(base_url + props.bank + '/configurations/holidays/store', {
          name: name.value,
          date: date.value
        })
        .then(res => {
          toast.success(res.data.message);
          name.value = '';
          date.value = '';
          setTimeout(() => {
            window.location.reload();
          }, 4000);
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    const updateBaseRate = () => {
      axios
        .post(base_url + props.bank + '/configurations/holidays/update', {
          edit_id: edit_id.value,
          name: name.value,
          date: date.value
        })
        .then(res => {
          toast.success(res.data.message);
          edit_id.value = '';
          name.value = '';
          date.value = '';
          showEditModal.value = false;
          setTimeout(() => {
            window.location.reload();
          }, 3000);
        })
        .catch(err => {
          console.log(err);
          toast.error('An error occurred');
        });
    };

    const updateActiveStatus = (holiday, status) => {
      axios
        .get(base_url + props.bank + `/configurations/holiday/${holiday}/status/${status}/update`)
        .then(() => {
          getRates();
          toast.success('Status updated');
        })
        .catch(err => {
          console.log(err);
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
      getRates();
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
      can_update,
      rates,
      name,
      date,
      showEditModal,
      name_search,
      status_search,
      filter,
      refresh,
      addNewBaseRate,
      updateBaseRate,
      editBaseRate,
      updateActiveStatus,
      approveChange,
      changePage,
      downloadSample,
      changeFile,
      uploadHolidays,
      moment,
      date_format
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
