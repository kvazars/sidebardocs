<script setup>
import { RouterLink } from 'vue-router'

import { logo } from '@/assets/brand/logo'
import SidebarNav from '@/components/SidebarNav.vue'
import { useSidebarStore } from '@/stores/sidebar.js'

const menu = [
  {
    href: '/',
    title: 'Dashboard',
    id: 0,
    icon: 'fa fa-user',
  },
  {
    title: 'Charts',
    id: 150,
    icon: 'fa fa-folder',
    child: [
      {
        title: 'Editor',
        id: 151,
        icon: 'fa fa-folder',
        child: [
          {
            title: '123',
            id: 152,
            icon: 'fa fa-folder',
            child: [
              {
                title: '456',
                id: 160,
                icon: 'fa fa-folder',
                //child: [
                  // {
                    // id: 161,
                    // href: '/editors/editor/2',
                    // icon: 'fa fa-file',
                    // title: 'paksfkq',
                  // },
                // ]
              },
              {
                id: 153,
                href: '/files/2',
                icon: 'fa fa-file',
                title: 'Editor 2',
              },
              {
                id: 154,
                href: '/files/1',
                icon: 'fa fa-file',
                title: 'Editor 1'
              },]
          },

        ],
      },
    ],
  },
];

const sidebar = useSidebarStore()
</script>

<template>
  <CSidebar class="border-end" colorScheme="light" position="fixed" :unfoldable="sidebar.unfoldable"
    :visible="sidebar.visible" @visible-change="(value) => sidebar.toggleVisible(value)">
    <CSidebarHeader class="border-bottom">
      <RouterLink custom to="/" v-slot="{ href, navigate }">
        <CSidebarBrand v-bind="$attrs" as="a" :href="href" @click="navigate">
          <CIcon custom-class-name="sidebar-brand-full" :icon="logo" :height="32" />
        </CSidebarBrand>
      </RouterLink>
      <CCloseButton class="d-lg-none" dark @click="sidebar.toggleVisible()" />
    </CSidebarHeader>
    <SidebarNav :menu="menu" />
    <CSidebarFooter class="border-top d-none d-lg-flex">
      <CSidebarToggler @click="sidebar.toggleUnfoldable()" />
    </CSidebarFooter>
  </CSidebar>
</template>
