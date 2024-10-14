<script setup>

const options = {
  elements: {
    line: {
      tension: 0.4,
    },
    point: {
      radius: 0,
      hitRadius: 10,
      hoverRadius: 4,
      hoverBorderWidth: 3,
    },
  },
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false,
    },
  },
  scales: {
    x: {
      display: false,
    },
    y: {
      display: false,
    },
  },
}
</script>

<template>
  <CRow :xs="{ gutter: 4 }">
    <CCol :sm="6" :xl="4" :xxl="3">
      <CWidgetStatsD
        style="--cui-card-cap-bg: #3b5998"
        :values="[
          { title: 'friends', value: '89K' },
          { title: 'feeds', value: '459' },
        ]"
      >
        <template #icon><CIcon icon="cib-facebook" height="52" class="my-4 text-white" /></template>
     
      </CWidgetStatsD>
    </CCol>
    <CCol :sm="6" :xl="4" :xxl="3">
      <CWidgetStatsD
        style="--cui-card-cap-bg: #00aced"
        :values="[
          { title: 'followers', value: '973k' },
          { title: 'tweets', value: '1.792' },
        ]"
      >
        <template #icon><CIcon icon="cib-twitter" height="52" class="my-4 text-white" /></template>
    
      </CWidgetStatsD>
    </CCol>
    <CCol :sm="6" :xl="4" :xxl="3">
      <CWidgetStatsD
        style="--cui-card-cap-bg: #4875b4"
        :values="[
          { title: 'contacts', value: '500' },
          { title: 'feeds', value: '1.292' },
        ]"
      >
        <template #icon><CIcon icon="cib-linkedin" height="52" class="my-4 text-white" /></template>

      </CWidgetStatsD>
    </CCol>
    <CCol :sm="6" :xl="4" :xxl="3">
      <CWidgetStatsD
        color="warning"
        :values="[
          { title: 'events', value: '12+' },
          { title: 'meetings', value: '4' },
        ]"
      >
        <template #icon><CIcon icon="cil-calendar" height="52" class="my-4 text-white" /></template>
 
      </CWidgetStatsD>
    </CCol>
  </CRow>
</template>
