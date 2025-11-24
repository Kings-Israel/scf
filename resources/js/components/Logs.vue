<template>
  <div class="card p-3">
    <div class="d-flex flex-column flex-md-row justify-content-between">
      <div class="d-flex flex-wrap gap-1">
        <div class="">
          <label for="" class="form-label">{{ $t('Search by description') }}</label>
          <input
            type="text"
            class="form-control form-search"
            id="defaultFormControlInput"
            placeholder="Search Description"
            aria-describedby="defaultFormControlHelp"
            v-model="description"
            v-on:keyup.enter="filter"
          />
        </div>
        <div class="">
          <label for="" class="form-label">{{ $t('User Type') }}</label>
          <select class="form-select form-search" id="exampleFormControlSelect1" v-model="user_type">
            <option value="">{{ $t('Select') }}</option>
            <option value="">{{ $t('All Users') }}</option>
            <option value="Bank">{{ $t('Bank') }}</option>
            <option value="Anchor">{{ $t('Anchor') }}</option>
            <option value="Vendor">{{ $t('Vendor') }}</option>
            <option value="Dealer">{{ $t('Dealer') }}</option>
          </select>
        </div>
        <div class="">
          <label class="form-label" for="">{{ $t('Date') }}</label>
          <input
            type="text"
            id="date_search"
            class="form-control form-search"
            name="daterange"
            placeholder="Select Dates"
            autocomplete="off"
          />
        </div>
        <div class="table-search-btn">
          <button class="btn btn-primary btn-md" @click="filter"><i class="ti ti-search"></i></button>
        </div>
        <div class="table-clear-btn">
          <button class="btn btn-primary btn-md" @click="refresh"><i class="ti ti-refresh"></i></button>
        </div>
      </div>
      <div class="d-flex justify-content-end gap-1 mt-2 mt-md-auto">
        <div class="">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page" style="height: fit-content">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
          </select>
        </div>
        <div class="">
          <download-buttons @download-report="downloadReport" @download-pdf="downloadPdf"></download-buttons>
        </div>
      </div>
    </div>
    <pagination
      v-if="logs.meta"
      class="mx-2"
      :from="logs.meta.from"
      :to="logs.meta.to"
      :links="logs.meta.links"
      :next_page="logs.links.next"
      :prev_page="logs.links.prev"
      :total_items="logs.meta.total"
      :first_page_url="logs.links.first"
      :last_page_url="logs.links.last"
      @change-page="changePage"
    ></pagination>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>{{ $t('User') }}</th>
            <th>{{ $t('User Type') }}</th>
            <th>{{ $t('Description') }}</th>
            <th>{{ $t('IP') }}</th>
            <th>{{ $t('Device Info') }}</th>
            <th>{{ $t('Date & Time') }}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr v-if="!logs.data">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('Loading Data') }}...</span>
            </td>
          </tr>
          <tr v-if="logs.data && logs.data.length <= 0">
            <td colspan="12" class="text-center">
              <span class="text-center">{{ $t('No Data Available') }}...</span>
            </td>
          </tr>
          <tr v-for="log in logs.data" :key="log.id">
            <td>
              <span v-if="log.causer && log.causer_type == 'App\\Models\\User'">
                {{ log.causer.name }}
              </span>
            </td>
            <td>
              <span v-if="log.properties && log.properties.user_type">{{ log.properties.user_type }}</span>
              <span v-else>-</span>
            </td>
            <td>
              <span v-if="log.causer && log.causer_type == 'App\\Models\\User'">
                {{ log.causer.name }}
              </span>
              <span class="mx-1">
                {{ log.description }}
              </span>
              <span v-if="log.subject && log.subject_type == 'App\\Models\\Company'">
                {{ ', Company: ' + log.subject }}
              </span>
              <span v-if="log.subject && log.subject_type == 'App\\Models\\Invoice'">
                {{ ', Invoice: ' + log.subject }}
              </span>
              <span v-if="log.subject && log.subject_type == 'App\\Models\\CbsTransaction'">
                {{ ', CBS Transaction: ' + log.subject }}
              </span>
              <span v-if="log.subject && log.subject_type == 'App\\Models\\PaymentRequest'">
                {{ ', Payment Request: ' + log.subject }}
              </span>
            </td>
            <td>
              <span v-if="log.properties && log.properties.ip">{{ log.properties.ip }}</span>
              <span v-else>-</span>
            </td>
            <td style="cursor: pointer; max-width: 250px; overflow-x: clip">
              <span v-if="log.properties && log.properties.device_info" :title="log.properties.device_info">{{
                log.properties.device_info
              }}</span>
              <span v-else>-</span>
            </td>
            <td>
              {{ moment(log.created_at).format('DD MMM YYYY HH:mm A') }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination
      v-if="logs.meta"
      class="mx-2"
      :from="logs.meta.from"
      :to="logs.meta.to"
      :links="logs.meta.links"
      :next_page="logs.links.next"
      :prev_page="logs.links.prev"
      :total_items="logs.meta.total"
      :first_page_url="logs.links.first"
      :last_page_url="logs.links.last"
      @change-page="changePage"
    ></pagination>
  </div>
</template>
<script>
import { useToast } from 'vue-toastification';
import { computed, watch, onMounted, ref, inject } from 'vue';
import Pagination from './partials/Pagination.vue';
import DownloadButtons from './reports/partials/DownloadButtons.vue';
import axios from 'axios';
import moment from 'moment';

export default {
  name: 'ActivityLogs',
  props: ['bank'],
  components: {
    Pagination,
    DownloadButtons
  },
  setup(props) {
    const toast = useToast();
    const base_url = inject('baseURL');
    const logs = ref([]);

    const user_type = ref('');
    const user_role = ref('');
    const description = ref('');
    const start_date = ref('');
    const end_date = ref('');
    const date_search = ref('');

    const per_page = ref(50);

    const getLogs = async () => {
      await axios
        .get(base_url + props.bank + '/reports/logs/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          logs.value = response.data.data;
        });
    };

    onMounted(() => {
      getLogs();
    });

    const filter = async () => {
      let parent = $('.ti-search').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      date_search.value = $('#date_search').val();
      if (date_search.value) {
        start_date.value = moment(date_search.value.split(' - ')[0]).format('YYYY-MM-DD');
        end_date.value = moment(date_search.value.split(' - ')[1]).format('YYYY-MM-DD');
      }
      await axios
        .get(base_url + props.bank + '/reports/logs/data', {
          params: {
            per_page: per_page.value,
            user_type: user_type.value,
            user_role: user_role.value,
            description: description.value,
            start_date: start_date.value,
            end_date: end_date.value
          }
        })
        .then(response => {
          logs.value = response.data.data;
          parent.html('<i class="ti ti-search"></i>');
        });
    };

    const refresh = async () => {
      let parent = $('.ti-refresh').parent();
      parent.html('<img src="../../../../../assets/img/spinner.svg" />');
      user_type.value = '';
      user_role.value = '';
      description.value = '';
      start_date.value = '';
      end_date.value = '';
      date_search.value = '';
      await axios
        .get(base_url + props.bank + '/reports/logs/data', {
          params: {
            per_page: per_page.value
          }
        })
        .then(response => {
          logs.value = response.data.data;
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    const changePage = async page => {
      await axios
        .get(page, {
          params: {
            per_page: per_page.value,
            user_type: user_type.value,
            user_role: user_role.value,
            description: description.value,
            start_date: start_date.value,
            end_date: end_date.value
          }
        })
        .then(response => {
          logs.value = response.data.data;
          parent.html('<i class="ti ti-refresh"></i>');
        });
    };

    const downloadReport = () => {
      axios
        .get(base_url + props.bank + '/reports/data/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            type: 'logs',
            user_type: user_type.value,
            user_role: user_role.value,
            description: description.value,
            start_date: start_date.value,
            end_date: end_date.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `ActivityLogs_${moment().format('Do_MMM_YYYY')}.csv`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    const downloadPdf = () => {
      axios
        .get(base_url + props.bank + '/reports/pdf/export', {
          responseType: 'arraybuffer',
          method: 'GET',
          params: {
            type: 'logs',
            user_type: user_type.value,
            user_role: user_role.value,
            description: description.value,
            start_date: start_date.value,
            end_date: end_date.value
          }
        })
        .then(response => {
          const fileURL = window.URL.createObjectURL(new Blob([response.data]));
          const fileLink = document.createElement('a');

          fileLink.href = fileURL;
          fileLink.setAttribute('download', `Activity_logs_${moment().format('Do_MMM_YYYY')}.pdf`);
          document.body.appendChild(fileLink);

          fileLink.click();
        })
        .catch(err => {
          console.log(err);
        });
    };

    return {
      moment,
      logs,
      per_page,
      user_type,
      user_role,
      description,
      start_date,
      end_date,

      filter,
      refresh,
      changePage,
      downloadReport,
      downloadPdf
    };
  }
};
</script>
