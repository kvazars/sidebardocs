<script setup>
// import { ref } from 'vue';
import { RouterLink } from 'vue-router'

import SidebarNav from '@/components/SidebarNav.vue'
import { useSidebarStore } from '@/stores/sidebar.js'

const props = defineProps(['menu'])
const sidebar = useSidebarStore();
// let menu = ref([]);
// menu = await props.datasend('folder', 'GET', {}).then((res) => {
//   let menus = res;
//   // console.log(menus);
//   // let r = JSON.parse(a);

//   function menucreate(i = 0) {
//     let rrr = [];
//     menus.forEach((e) => {
//       if (e.tree_id == i) {
//         e.href = '/';
//         e.title = e.name;
//         e.icon = 'fa fa-folder';
//         e.child = menucreate(e.id);
//         rrr.push(e);

//       }
//     });

//     //rrr.sort((f, s) => f.tree_id - s.tree_id);
//     //console.log(rrr);
//     return rrr;
//   }

//   //let menu = 
//   // console.log(menu);

//   return menucreate();
//   //co//nsole.log(menu);

//   // sidebar = useSidebarStore();

//   // this.sidebar = useSidebarStore();
//   // console.log(menu12);

// }).catch((error) => {
//   console.log(error);
// });

// // console.log(menu);
// const sidebar = useSidebarStore();


//[
//   {
//     href: '/',
//     title: 'Dashboard',
//     id: 0,
//     icon: 'fa fa-user',
//   },
//   {
//     title: 'Charts',
//     id: 150,
//     icon: 'fa fa-folder',
//     child: [
//       {
//         title: 'Editor',
//         id: 151,
//         icon: 'fa fa-folder',
//         child: [
//           {
//             title: '123',
//             id: 152,
//             icon: 'fa fa-folder',
//             child: [
//               {
//                 title: '456',
//                 id: 160,
//                 icon: 'fa fa-folder',
//                 child: [
//                   {

//                   },
//                 ]
//               },
//               {
//                 id: 153,
//                 href: '/files/2',
//                 icon: 'fa fa-file',
//                 title: 'Editor 2',
//               },
//               {
//                 id: 154,
//                 href: '/files/1',
//                 icon: 'fa fa-file',
//                 title: 'Editor 1'
//               },]
//           },

//         ],
//       },
//     ],
//   },
// ];


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
      <SidebarNav :menu="menu" />
      <CSidebarFooter class="border-top d-none d-lg-flex">
        <CSidebarToggler @click="sidebar.toggleUnfoldable()" />
      </CSidebarFooter>
    </CSidebar>
</template>
