<script setup>
import { RouterLink } from 'vue-router'

import SidebarNav from '@/components/SidebarNav.vue'
import { useSidebarStore } from '@/stores/sidebar.js'

const props = defineProps(['menu'])
const sidebar = useSidebarStore();

</script>
<template>

  <CSidebar class="border-end" colorScheme="light" position="fixed" :unfoldable="sidebar.unfoldable"
    :visible="sidebar.visible" @visible-change="(value) => sidebar.toggleVisible(value)">
    <CSidebarHeader class="border-bottom">
      <RouterLink custom to="/" v-slot="{ href, navigate }">
        <CSidebarBrand v-bind="$attrs" as="a" :href="href" @click="navigate">
        </CSidebarBrand>
      </RouterLink>
      <CCloseButton class="d-lg-none" dark @click="sidebar.toggleVisible()" />
    </CSidebarHeader>
    <SidebarNav :menu="props.menu" />
    <CSidebarFooter class="border-top d-none d-lg-flex">
      <div>
        <router-link :to="{ name: 'NewFolder', params: {'parent': 0} }">
          <i class="fa fa-plus-circle fs-4" aria-hidden="true"></i>
        </router-link>
      </div>
      <CSidebarToggler @click="sidebar.toggleUnfoldable()" />
    </CSidebarFooter>
  </CSidebar>
</template>
