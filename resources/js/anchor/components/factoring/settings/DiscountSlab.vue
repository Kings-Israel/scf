<template>
  <div class="card p-2">
    <div class="d-flex justify-content-between">
      <div class="d-flex flex-wrap">
      </div>
      <div class="d-flex">
        <div class="mx-1">
          <select class="form-select" id="exampleFormControlSelect1" v-model="per_page">
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
        <button type="button" class="btn btn-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#addBranch">{{ $t('Create Early Payment Discount Slab')}}</button>
        <div class="modal modal-center fade modal-lg" id="addBranch" tabindex="-1">
          <div class="modal-dialog">
            <form class="modal-content" method="POST" @submit.prevent="addEarlyPaymentDiscountSlab">
              <div class="modal-header">
                <h5 class="modal-title" id="modalTopTitle">{{ $t('Create Early Payment Discount Slab')}}</h5>
                <button type="button" class="btn-close" id="close-upload-modal" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body my-1">
                <div class="row">
                  <div class="col-6">
                    <div class="form-group">
                      <label for="Title" class="form-label">{{ __('Title')}} <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" v-model="title" autocomplete="off">
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="form-group my-1">
                      <label for="Name" class="form-label">{{ __('Status')}} <span class="text-danger">*</span></label>
                      <select name="" id="" class="form-control" v-model="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">{{ $t('Inactive')}}</option>
                      </select>
                    </div>
                  </div>
                </div>
                <br>
                <div class="d-flex justify-content-between">
                  <h6>{{ $t('Discount Slab')}}</h6>
                  <button class="btn btn-label-danger btn-sm" type="button" @click="addCount">{{ $t('Add Slab')}}</button>
                </div>
                <div class="row" v-for="count in slabs_count" :key="count">
                  <div class="col-3 mt-1">
                    <div class="form-group my-1">
                      <label for="Name" class="form-label">{{ $t('From')}}</label>
                      <input type="text" name="number" id="" class="form-control" v-model="from_days[count - 1]" autocomplete="off">
                    </div>
                  </div>
                  <div class="col-3">
                    <div class="form-group my-1">
                      <label for="Name" class="form-label">{{ $t('To')}}</label>
                      <input type="text" name="number" id="" class="form-control" v-model="to_days[count - 1]" autocomplete="off">
                    </div>
                  </div>
                  <div class="col-3">
                    <div class="form-group my-1">
                      <label for="Name" class="form-label">{{ $t('Discount Percentage')}}</label>
                      <input type="text" name="number" id="" class="form-control" v-model="discount_percentages[count - 1]" autocomplete="off">
                    </div>
                  </div>
                  <div class="col-3 my-1" v-if="slabs_count > 1 && count - 1 >= 1">
                    <button class="btn btn-danger btn-sm" @click="slabs_count -= 1">{{ $t('Delete')}}</button>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ $t('Close')}}</button>
                <button type="submit" class="btn btn-primary">{{ $t('Submit')}}</button>
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
            <th>{{ $t('Title')}}</th>
            <th>{{ $t('Discount Slab')}}</th>
            <th>{{ $t('Actions')}}</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr class="text-nowrap" v-for="data in slabs.data" :key="data.id">
            <td>{{ data.title }}</td>
            <td>
              <div class="d-flex">
                <template v-for="discount_slab in data.discount_slabs" :key="discount_slab.id">
                  <div class="d-flex mx-1">
                    <span>{{ discount_slab.from_day }}</span>
                    <span class="mx-1">to</span>
                    <span class="mx-1">{{ discount_slab.to_day }}</span>
                    <span>-</span>
                    <span class="mx-1">{{ discount_slab.discount_percentage }}</span>
                    <span class="">%</span>
                    <span>,</span>
                  </div>
                </template>
              </div>
            </td>
            <td class="flex">
              <i class="ti ti-pencil ti-sm text-warning mx-1" @click="editSlab(data)" data-bs-toggle="modal" :data-bs-target="'#rate-'+data.id" style="cursor: pointer;"></i>
              <div class="modal modal-center fade modal-lg" :id="'rate-'+data.id" tabindex="-1">
                <div class="modal-dialog">
                  <form class="modal-content" method="POST" @submit.prevent="updateEarlyPaymentDiscountSlab">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalTopTitle">{{ $t('Edit Early Payment Discount Slab')}}</h5>
                      <button type="button" class="btn-close" :id="'close-upload-modal-'+data.id" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body my-1">
                      <div class="row">
                        <div class="col-6">
                          <div class="form-group">
                            <label for="Title" class="form-label">{{ $t('Title')}} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="title" autocomplete="off">
                          </div>
                        </div>
                        <div class="col-6">
                          <div class="form-group my-1">
                            <label for="Name" class="form-label">{{ $t('Status')}} <span class="text-danger">*</span></label>
                            <select name="" id="" class="form-control" v-model="status" required>
                              <option value="active">{{ $t('Active')}}</option>
                              <option value="inactive">{{ $t('Inactive')}}</option>
                            </select>
                          </div>
                        </div>
                      </div>
                      <br>
                      <div class="d-flex justify-content-between">
                        <h6>{{ $t('Discount Slab')}}</h6>
                        <button class="btn btn-label-danger btn-sm" type="button" @click="addCount">{{ $t('Add')}}</button>
                      </div>
                      <div class="row" v-for="count in slabs_count" :key="count">
                        <div class="col-3 mt-1">
                          <div class="form-group my-1">
                            <label for="Name" class="form-label">{{ $t('From')}}</label>
                            <input type="text" name="number" id="" class="form-control" v-model="from_days[count - 1]" autocomplete="off">
                          </div>
                        </div>
                        <div class="col-3">
                          <div class="form-group my-1">
                            <label for="Name" class="form-label">{{ __('To')}}</label>
                            <input type="text" name="number" id="" class="form-control" v-model="to_days[count - 1]" autocomplete="off">
                          </div>
                        </div>
                        <div class="col-3">
                          <div class="form-group my-1">
                            <label for="Name" class="form-label">{{ $t('Discount Percentage')}}</label>
                            <input type="text" name="number" id="" class="form-control" v-model="discount_percentages[count - 1]" autocomplete="off">
                          </div>
                        </div>
                        <div class="col-3 my-1" v-if="slabs_count > 1 && count - 1 >= 1">
                          <button class="btn btn-danger btn-sm" @click="slabs_count -= 1">{{ $t('Delete')}}</button>
                        </div>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ $t('Close')}}</button>
                      <button type="submit" class="btn btn-primary">{{ $t('Submit')}}</button>
                    </div>
                  </form>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination :from="slabs.from" :to="slabs.to" :links="slabs.links" :next_page="slabs.next_page_url" :prev_page="slabs.prev_page_url" :total_items="slabs.total" :first_page_url="slabs.first_page_url" :last_page_url="slabs.last_page_url" @change-page="changePage"></pagination>
  </div>
  </template>
  <script>
  import { ref, onMounted, inject } from 'vue'
  import axios from 'axios'
  import { useToast } from 'vue-toastification'
  import Pagination from '../../partials/Pagination.vue';
  export default {
    name: 'DiscountSlab',
    components: {
      Pagination
    },
    setup() {
      const toast = useToast()
      const base_url = inject('baseURL')
      const slabs = ref([])
      const edit_id = ref('')
      const title = ref('')
      const status = ref('')
      const slabs_count = ref(1)
      const discount_percentages = ref([])
      const from_days = ref([])
      const to_days = ref([])

      const per_page = ref(50)

      const getDiscountSlabs = () => {
        axios.get(base_url+'factoring/settings/slab-settings/data', {
          params: {
            per_page: per_page.value,
          }
        })
          .then(response => {
            slabs.value = response.data.slabs
          })
      }

      onMounted(() => {
        getDiscountSlabs()
      })

      const addEarlyPaymentDiscountSlab = () => {
        const formData = new FormData
        formData.append('title', title.value)
        formData.append('status', status.value)
        formData.append('from_days', JSON.stringify(from_days.value))
        formData.append('to_days', JSON.stringify(to_days.value))
        formData.append('discount_percentages', JSON.stringify(discount_percentages.value))
        axios.post(base_url+'factoring/settings/slab-settings/store', formData)
        .then(() => {
          getDiscountSlabs()
          toast.success('Slab added successfully')
          title.value = ''
          status.value = ''
          slabs_count.value = 1
          discount_percentages.value = []
          from_days.value = []
          to_days.value = []
          document.getElementById('close-upload-modal').click()
        })
        .catch(err => {
          console.log(err)
          toast.error('An error occurred while adding')
        })
      }

      const editSlab = discount_slab => {
        edit_id.value = discount_slab.id
        title.value = discount_slab.title
        status.value = discount_slab.status
        slabs_count.value = 1
        discount_percentages.value = []
        from_days.value = []
        to_days.value = []
        discount_slab.discount_slabs.forEach(slab => {
          discount_percentages.value.push(slab.discount_percentage)
          from_days.value.push(slab.from_day)
          to_days.value.push(slab.to_day)
          slabs_count.value += 1
        });
      }

      const updateEarlyPaymentDiscountSlab = () => {
        const formData = new FormData
        formData.append('slab_id', edit_id.value)
        formData.append('title', title.value)
        formData.append('status', status.value)
        formData.append('from_days', JSON.stringify(from_days.value))
        formData.append('to_days', JSON.stringify(to_days.value))
        formData.append('discount_percentages', JSON.stringify(discount_percentages.value))
        axios.post(base_url+'factoring/settings/slab-settings/update', formData)
        .then(() => {
          getDiscountSlabs()
          document.getElementById('close-upload-modal-'+edit_id.value).click()
          toast.success('Slab updated successfully')
          title.value = ''
          status.value = ''
          slabs_count.value = 1
          discount_percentages.value = []
          from_days.value = []
          to_days.value = []
        })
        .catch(err => {
          console.log(err)
          toast.error('An error occurred while adding')
        })
      }

      const addCount = () => {
        slabs_count.value += 1
      }

      const changePage = async (page) => {
      await axios.get(page, {
        params: {
            per_page: per_page.value,
          }
      })
        .then(response => {
          slabs.value = response.data.slabs
        })
    }

      return {
        per_page,
        slabs,
        title,
        status,
        discount_percentages,
        from_days,
        to_days,
        slabs_count,
        addEarlyPaymentDiscountSlab,
        updateEarlyPaymentDiscountSlab,
        addCount,
        editSlab,
        changePage,
      }
    }
  }
  </script>
