<template>
  <Carousel v-if="dashboard_data && dashboard_data.has_data" v-bind="carouselConfig">
    <Slide>
      <div v-if="can_view_vendor_financing_requests" class="carousel__item p-0" id="vendor-financing">
        <div class="row match-height">
          <div class="col-lg-3 col-sm-6 mb-4">
            <div class="card h-100 border border-primary" style="box-shadow: none">
              <div class="card-body d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">
                  <h6>{{ $t('Onboarded Companies') }}</h6>
                  <h5 class="mb-0 me-2">
                    {{ $t('Anchors') }}
                    <span class="text-success">({{ dashboard_data.vendor_financing_anchors_count }})</span>
                  </h5>
                  <h6 class="mb-0 me-2">{{ $t('Total Vendors') }} - {{ dashboard_data.vendors_count }}</h6>
                </div>
                <div class="card-icon">
                  <span class="badge bg-label-primary p-2">
                    <i class="ti ti-user ti-sm"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-sm-6 mb-4">
            <a :href="current_bank + '/report/maturing-payments-report'">
              <div class="card h-100 border border-secondary" style="box-shadow: none">
                <div class="card-body d-flex justify-content-between align-items-center">
                  <div class="card-title mb-0">
                    <h6>
                      {{ $t('Maturing Payments') }} <span>({{ $t('in 7 Days') }})</span>
                    </h6>
                    <h5 class="mb-0 me-2">
                      {{ new Intl.NumberFormat().format(dashboard_data.vendor_financing_maturing_payments_count) }}
                    </h5>
                    <h6 class="mb-0 me-2">{{ $t('Vendor Financing') }}</h6>
                  </div>
                  <div class="card-icon">
                    <span class="badge bg-label-danger p-2">
                      <i class="ti ti-user-plus ti-sm"></i>
                    </span>
                  </div>
                </div>
              </div>
            </a>
          </div>
          <div class="col-lg-3 col-sm-6 mb-4">
            <div class="card h-100 border border-warning" style="box-shadow: none">
              <div class="card-body d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">
                  <h6>{{ $t('Payments Pending Approval') }}</h6>
                  <h5 class="mb-0 me-2">
                    {{
                      new Intl.NumberFormat().format(dashboard_data.vendor_financing_payments_pending_approval_count)
                    }}
                  </h5>
                  <h6 class="mb-0 me-2">{{ $t('Vendor Financing') }}</h6>
                </div>
                <div class="card-icon">
                  <span class="badge bg-label-success p-2">
                    <i class="ti ti-user-check ti-sm"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-sm-6 mb-4">
            <a :href="current_bank + '/requests/reverse-factoring?status_search=disbursed&period=mtd'">
              <div class="card h-100 border border-danger" style="box-shadow: none">
                <div class="card-body d-flex justify-content-between align-items-center">
                  <div class="card-title mb-0">
                    <h6>{{ $t('Disbursed Payments') }} ({{ $t('MTD') }})</h6>
                    <h5 class="mb-0 me-2">
                      {{ new Intl.NumberFormat().format(dashboard_data.vendor_financing_disbursed_payments_count) }}
                    </h5>
                    <h6 class="mb-0 me-2">{{ $t('Vendor Financing') }}</h6>
                  </div>
                  <div class="card-icon">
                    <span class="badge bg-label-warning rounded-pill p-2">
                      <i class="ti ti-user ti-sm"></i>
                    </span>
                  </div>
                </div>
              </div>
            </a>
          </div>
        </div>
      </div>
    </Slide>
    <Slide>
      <div v-if="can_view_vendor_financing_requests" class="carousel__item p-0" id="factoring">
        <div class="row match-height">
          <div class="col-lg-3 col-sm-6 mb-4">
            <div class="card h-100 border border-primary" style="box-shadow: none">
              <div class="card-body d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">
                  <h6>{{ $t('Onboarded Companies') }}</h6>
                  <h5 class="mb-0 me-2">
                    {{ $t('Anchors') }} <span class="text-success">({{ dashboard_data.factoring_anchors_count }})</span>
                  </h5>
                  <h6 class="mb-0 me-2">{{ $t('Total Buyers') }} - {{ dashboard_data.buyers_count }}</h6>
                </div>
                <div class="card-icon">
                  <span class="badge bg-label-primary p-2">
                    <i class="ti ti-user-check ti-sm"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-sm-6 mb-4">
            <a :href="current_bank + '/report/maturing-payments-report'">
              <div class="card h-100 border border-secondary" style="box-shadow: none">
                <div class="card-body d-flex justify-content-between align-items-center">
                  <div class="card-title mb-0">
                    <h6>
                      {{ $t('Maturing Payments') }} <span>({{ $t('in 7 Days') }})</span>
                    </h6>
                    <h5 class="mb-0 me-2">
                      {{ new Intl.NumberFormat().format(dashboard_data.factoring_maturing_payments_count) }}
                    </h5>
                    <h6 class="mb-0 me-2">{{ $t('Factoring') }}</h6>
                  </div>
                  <div class="card-icon">
                    <span class="badge bg-label-danger p-2">
                      <i class="ti ti-user-plus ti-sm"></i>
                    </span>
                  </div>
                </div>
              </div>
            </a>
          </div>
          <div class="col-lg-3 col-sm-6 mb-4">
            <div class="card h-100 border border-waring" style="box-shadow: none">
              <div class="card-body d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">
                  <h6>{{ $t('Payments Pending Approval') }}</h6>
                  <h5 class="mb-0 me-2">
                    {{ new Intl.NumberFormat().format(dashboard_data.factoring_payments_pending_approval_count) }}
                  </h5>
                  <h6 class="mb-0 me-2">{{ $t('Factoring') }}</h6>
                </div>
                <div class="card-icon">
                  <span class="badge bg-label-success p-2">
                    <i class="ti ti-user-check ti-sm"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-sm-6 mb-4">
            <a :href="current_bank + '/requests/factoring?status_search=disbursed&period=mtd'">
              <div class="card h-100 border border-danger" style="box-shadow: none">
                <div class="card-body d-flex justify-content-between align-items-center">
                  <div class="card-title mb-0">
                    <h6>{{ $t('Disbursed Payments') }} ({{ $t('MTD') }})</h6>
                    <h5 class="mb-0 me-2">
                      {{ new Intl.NumberFormat().format(dashboard_data.factoring_disbursed_payments_count) }}
                    </h5>
                    <h6 class="mb-0 me-2">{{ $t('Factoring') }}</h6>
                  </div>
                  <div class="card-icon">
                    <span class="badge bg-label-warning rounded-pill p-2">
                      <i class="ti ti-user ti-sm"></i>
                    </span>
                  </div>
                </div>
              </div>
            </a>
          </div>
        </div>
      </div>
    </Slide>
    <Slide>
      <div v-if="can_view_dealer_financing_requests" class="carousel__item p-0" id="dealer-financing">
        <div class="row match-height">
          <div class="col-lg-3 col-sm-6 mb-4">
            <div class="card h-100 border border-primary" style="box-shadow: none">
              <div class="card-body d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">
                  <h6>{{ $t('Onboarded Companies') }}</h6>
                  <h5 class="mb-0 me-2">
                    {{ $t('Anchors') }}
                    <span class="text-success">({{ dashboard_data.dealer_financing_anchors_count }})</span>
                  </h5>
                  <h6 class="mb-0 me-2">{{ $t('Total Dealers') }} - {{ dashboard_data.dealers_count }}</h6>
                </div>
                <div class="card-icon">
                  <span class="badge bg-label-primary p-2">
                    <i class="ti ti-user-check ti-sm"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-sm-6 mb-4">
            <a :href="current_bank + '/report/maturing-payments-report'">
              <div class="card h-100 border border-secondary" style="box-shadow: none">
                <div class="card-body d-flex justify-content-between align-items-center">
                  <div class="card-title mb-0">
                    <h6>
                      {{ $t('Maturing Payments') }} <span>({{ $t('in 7 Days') }})</span>
                    </h6>
                    <h5 class="mb-0 me-2">
                      {{ new Intl.NumberFormat().format(dashboard_data.dealer_financing_maturing_payments_count) }}
                    </h5>
                    <h6 class="mb-0 me-2">{{ $t('Dealer Financing') }}</h6>
                  </div>
                  <div class="card-icon">
                    <span class="badge bg-label-danger p-2">
                      <i class="ti ti-user-plus ti-sm"></i>
                    </span>
                  </div>
                </div>
              </div>
            </a>
          </div>
          <div class="col-lg-3 col-sm-6 mb-4">
            <div class="card h-100 border border-warning" style="box-shadow: none">
              <div class="card-body d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">
                  <h6>{{ $t('Payments Pending Approval') }}</h6>
                  <h5 class="mb-0 me-2">
                    {{
                      new Intl.NumberFormat().format(dashboard_data.dealer_financing_payments_pending_approval_count)
                    }}
                  </h5>
                  <h6 class="mb-0 me-2">{{ $t('Dealer Financing') }}</h6>
                </div>
                <div class="card-icon">
                  <span class="badge bg-label-success p-2">
                    <i class="ti ti-user-check ti-sm"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-sm-6 mb-4">
            <a :href="current_bank + '/requests/dealer-financing?status_search=disbursed&period=mtd'">
              <div class="card h-100 border border-info" style="box-shadow: none">
                <div class="card-body d-flex justify-content-between align-items-center">
                  <div class="card-title mb-0">
                    <h6>{{ $t('Disbursed Payments') }}({{ $t('MTD') }})</h6>
                    <h5 class="mb-0 me-2">
                      {{ new Intl.NumberFormat().format(dashboard_data.dealer_financing_disbursed_payments_count) }}
                    </h5>
                    <h6 class="mb-0 me-2">{{ $t('Dealer Financing') }}</h6>
                  </div>
                  <div class="card-icon">
                    <span class="badge bg-label-warning rounded-pill p-2">
                      <i class="ti ti-user ti-sm"></i>
                    </span>
                  </div>
                </div>
              </div>
            </a>
          </div>
        </div>
      </div>
    </Slide>
  </Carousel>
</template>
<script>
import { computed, onMounted, ref, watch, inject } from 'vue';
import { RouterLink } from 'vue-router';
import 'vue3-carousel/carousel.css';
import { Carousel, Slide } from 'vue3-carousel';
import axios from 'axios';
export default {
  name: 'Companies',
  props: ['bank', 'can_view_vendor_financing_requests', 'can_view_dealer_financing_requests'],
  components: {
    RouterLink,
    Carousel,
    Slide
  },
  setup(props) {
    const current_bank = props.bank;
    const base_url = inject('baseURL');
    const can_view_vendor_financing_requests = props.can_view_vendor_financing_requests == '1' ? ref(true) : ref(false);
    const can_view_dealer_financing_requests = props.can_view_dealer_financing_requests == '1' ? ref(true) : ref(false);

    const carouselConfig = {
      itemsToShow: 1,
      wrapAround: true,
      autoplay: 10000
    };

    const dashboard_data = ref([]);

    const getData = () => {
      axios.get(base_url + props.bank + '/dashboard/data').then(res => {
        dashboard_data.value = res.data;
      });
    };

    onMounted(() => {
      getData();
    });

    return {
      current_bank,
      carouselConfig,
      dashboard_data,
      can_view_dealer_financing_requests,
      can_view_vendor_financing_requests
    };
  }
};
</script>
<style scoped>
.carousel__slide {
  display: block;
}
</style>
