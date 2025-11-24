<template>
  <div class="d-flex gap-1">
    <button
      type="button"
      id="download-excel"
      @click="downloadReport"
      style="height: fit-content"
      class="btn btn-primary"
    >
      <span class="d-flex" v-if="!downloadingExcel"> <i class="ti ti-download ti-xs px-1"></i> {{ $t('Excel') }} </span>
      <img :src="spinner" style="width: 1rem" v-else />
    </button>
    <button type="button" id="download-pdf" @click="downloadPdf" style="height: fit-content" class="btn btn-primary">
      <span class="d-flex" v-if="!downloadingPdf"> <i class="ti ti-download ti-xs px-1"></i> {{ $t('PDF') }} </span>
      <img :src="spinner" style="width: 1rem" v-else />
    </button>
  </div>
</template>
<script>
export default {
  name: 'DownloadButtons',
  props: ['downloadingPdf', 'downloadingExcel'],
  data: () => {
    return {
      spinner: '../../assets/img/spinner.svg'
    };
  },
  methods: {
    downloadReport() {
      this.$emit('download-report');
    },
    downloadPdf() {
      this.$emit('download-pdf');
    }
  }
};
</script>
