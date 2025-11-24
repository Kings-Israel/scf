<template>
  <nav aria-label="Page navigation" class="mt-2 d-flex flex-column flex-md-row justify-content-between">
    <div>
      <div class="d-flex my-auto text-primary" v-if="from && to && total_items">
        <span class="mr-1 fw-light">Showing</span>
        <span class="mx-1 fw-bold">{{ from }}</span>
        <span class="mx-1 fw-light">to</span>
        <span class="mx-1 fw-bold">{{ to }}</span>
        <span class="mx-1 fw-light">of</span>
        <span class="mx-1 fw-bold">{{ total_items }}</span>
      </div>
    </div>
    <ul class="pagination justify-content-md-end">
      <li class="page-item prev">
        <a class="page-link" href="javascript:void(0);" @click="changePage(first_page_url)"
          ><i class="ti ti-chevrons-left ti-xs"></i
        ></a>
      </li>
      <li class="page-item" v-for="link in links" :key="link" :class="link.active ? 'active' : ''">
        <a
          class="page-link"
          href="javascript:void(0);"
          v-if="link.url"
          @click="changePage(link.url)"
          v-html="link.label"
        ></a>
      </li>
      <li class="page-item next">
        <a class="page-link" href="javascript:void(0);" @click="changePage(last_page_url)"
          ><i class="ti ti-chevrons-right ti-xs"></i
        ></a>
      </li>
    </ul>
  </nav>
</template>

<script>
export default {
  name: 'Pagination',
  props: {
    next_page: {
      Type: String
    },
    prev_page: {
      Type: String
    },
    from: {
      Type: Number
    },
    to: {
      Type: Number
    },
    total_items: {
      Type: Number
    },
    links: {
      Type: Array
    },
    first_page_url: {
      Type: String,
      default: ''
    },
    last_page_url: {
      Type: String,
      default: ''
    }
  },
  methods: {
    changePage(page) {
      this.$emit('change-page', page);
    }
  }
};
</script>
