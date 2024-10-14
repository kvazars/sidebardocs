<template>
  <CRow :xs="{ gutter: 4 }">
    <CCol :sm="6" :xl="4" :xxl="3">
      <CWidgetStatsA color="primary">
        <template #value
          >26K
          <span class="fs-6 fw-normal"> (-12.4% <CIcon icon="cil-arrow-bottom" />) </span>
        </template>
        <template #title>Users</template>
        <template #action>
          <CDropdown placement="bottom-end">
            <CDropdownToggle color="transparent" class="p-0 text-white" :caret="false">
              <CIcon icon="cil-options" class="text-white" />
            </CDropdownToggle>
            <CDropdownMenu>
              <CDropdownItem href="#">Action</CDropdownItem>
              <CDropdownItem href="#">Another action</CDropdownItem>
              <CDropdownItem href="#">Something else here</CDropdownItem>
            </CDropdownMenu>
          </CDropdown>
        </template>
       
      </CWidgetStatsA>
    </CCol>
    <CCol :sm="6" :xl="4" :xxl="3">
      <CWidgetStatsA color="info">
        <template #value
          >$6.200
          <span class="fs-6 fw-normal"> (40.9% <CIcon icon="cil-arrow-top" />) </span>
        </template>
        <template #title>Income</template>
        <template #action>
          <CDropdown placement="bottom-end">
            <CDropdownToggle color="transparent" class="p-0 text-white" :caret="false">
              <CIcon icon="cil-options" class="text-white" />
            </CDropdownToggle>
            <CDropdownMenu>
              <CDropdownItem href="#">Action</CDropdownItem>
              <CDropdownItem href="#">Another action</CDropdownItem>
              <CDropdownItem href="#">Something else here</CDropdownItem>
            </CDropdownMenu>
          </CDropdown>
        </template>
       
      </CWidgetStatsA>
    </CCol>
    <CCol :sm="6" :xl="4" :xxl="3">
      <CWidgetStatsA color="warning">
        <template #value
          >2.49%
          <span class="fs-6 fw-normal"> (84.7% <CIcon icon="cil-arrow-top" />) </span>
        </template>
        <template #title>Conversion Rate</template>
        <template #action>
          <CDropdown placement="bottom-end">
            <CDropdownToggle color="transparent" class="p-0 text-white" :caret="false">
              <CIcon icon="cil-options" class="text-white" />
            </CDropdownToggle>
            <CDropdownMenu>
              <CDropdownItem href="#">Action</CDropdownItem>
              <CDropdownItem href="#">Another action</CDropdownItem>
              <CDropdownItem href="#">Something else here</CDropdownItem>
            </CDropdownMenu>
          </CDropdown>
        </template>
       
      </CWidgetStatsA>
    </CCol>
    <CCol :sm="6" :xl="4" :xxl="3">
      <CWidgetStatsA color="danger">
        <template #value
          >44K
          <span class="fs-6 fw-normal"> (-23.6% <CIcon icon="cil-arrow-bottom" />) </span>
        </template>
        <template #title>Sessions</template>
        <template #action>
          <CDropdown placement="bottom-end">
            <CDropdownToggle color="transparent" class="p-0 text-white" :caret="false">
              <CIcon icon="cil-options" class="text-white" />
            </CDropdownToggle>
            <CDropdownMenu>
              <CDropdownItem href="#">Action</CDropdownItem>
              <CDropdownItem href="#">Another action</CDropdownItem>
              <CDropdownItem href="#">Something else here</CDropdownItem>
            </CDropdownMenu>
          </CDropdown>
        </template>
      
      </CWidgetStatsA>
    </CCol>
  </CRow>
</template>
