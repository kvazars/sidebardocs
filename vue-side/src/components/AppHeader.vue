<script setup>
import { onMounted, ref } from 'vue'
import { useColorModes } from '@coreui/vue'

import AppBreadcrumb from '@/components/AppBreadcrumb.vue'
import { useSidebarStore } from '@/stores/sidebar.js'

const headerClassNames = ref('mb-4 p-0')
const { colorMode, setColorMode } = useColorModes('coreui-free-vue-admin-template-theme')
const sidebar = useSidebarStore()

onMounted(() => {
  document.addEventListener('scroll', () => {
    if (document.documentElement.scrollTop > 0) {
      headerClassNames.value = 'mb-4 p-0 shadow-sm'
    } else {
      headerClassNames.value = 'mb-4 p-0'
    }
  })
})
</script>

<template>
  <CHeader position="sticky" :class="headerClassNames">
    <CContainer class="border-bottom px-4" fluid>
      <CHeaderToggler @click="sidebar.toggleVisible()" style="margin-inline-start: -14px">
        
        <i class="fa fa-bars" aria-hidden="true"></i>
      </CHeaderToggler>
      <CHeaderNav>
        <li class="nav-item py-1">
          <div class="vr h-100 mx-2 text-body text-opacity-75"></div>
        </li>
        <CDropdown variant="nav-item" placement="bottom-end">
          <CDropdownToggle :caret="false">
            <i class="fa" v-if="colorMode == 'dark'" :class="{ 'fa-moon-o': colorMode == 'dark' }"></i>
            <i class="fa" v-else-if="colorMode == 'light'" :class="{ 'fa-sun-o': colorMode == 'light' }"></i>
            <i class="fa" v-else :class="'fa-cogs'"></i>

          </CDropdownToggle>
          <CDropdownMenu>
            <CDropdownItem :active="colorMode === 'light'" class="d-flex align-items-center" component="button"
              type="button" @click="setColorMode('light')">
              <i class="fa fa-sun-o me-2"></i> Светлая тема
            </CDropdownItem>
            <CDropdownItem :active="colorMode === 'dark'" class="d-flex align-items-center" component="button"
              type="button" @click="setColorMode('dark')">
              <i class="fa fa-moon-o me-2"></i> Темная тема
            </CDropdownItem>
            <CDropdownItem :active="colorMode === 'auto'" class="d-flex align-items-center" component="button"
              type="button" @click="setColorMode('auto')">
              <i class="fa fa-cogs me-2"></i> Системная тема
            </CDropdownItem>
          </CDropdownMenu>
        </CDropdown>
        <li class="nav-item py-1">
          <div class="vr h-100 mx-2 text-body text-opacity-75"></div>
        </li>
        <CDropdown placement="bottom-end" variant="nav-item">
          <CDropdownToggle class=" pe-0" :caret="false">
            <i class="fa fa-user"></i>
          </CDropdownToggle>
          <CDropdownMenu class="pt-0">

            <CDropdownItem>
              <i class="fa fa-cog"></i> Настройки
            </CDropdownItem>

            <CDropdownDivider />
            <CDropdownItem>
              <i class="fa fa-lock"></i> Вход
            </CDropdownItem>
            <CDropdownItem>
              <i class="fa fa-unlock"></i> Выход
            </CDropdownItem>
          </CDropdownMenu>
        </CDropdown>

      </CHeaderNav>
    </CContainer>
    <CContainer class="px-4" fluid>
      <AppBreadcrumb />
    </CContainer>
  </CHeader>
</template>
