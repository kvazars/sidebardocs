<template>

    <div class="vsm--wrapper">
        <sidebar-menu-scroll>
            <div class="v-sidebar-menu position-relative">
                <ul class="vsm--menu">
                    <sidebar-menu-item v-for="item in computedMenu" :key="item.id" :item="item"
                        :active-show="activeShow" @update-active-show="updateActiveShow">
                        <template #dropdown-icon="{ isOpen }">
                            <button class="btn btn-primary" @click="folderButton()">...</button>

                            <slot name="dropdown-icon" v-bind="{ isOpen }">
                                <span class="vsm--arrow_default " />
                            </slot>
                        </template>
                    </sidebar-menu-item>
                </ul>
            </div>
        </sidebar-menu-scroll>
    </div>
</template>


<script setup>
import {
    ref,
    computed,
} from 'vue'
// import { router } from 'vue-router';

import { initSidebar } from 'vue-sidebar-menu/src/use/useSidebar'
import SidebarMenuItem from 'vue-sidebar-menu/src/components/SidebarMenuItem.vue'
import SidebarMenuScroll from 'vue-sidebar-menu/src/components/SidebarMenuScroll.vue'

const props = defineProps({
    idOpen1: null,
    menu: {
        type: Array,
        required: true,
    },
    collapsed: {
        type: Boolean,
        default: false,
    },

})

const emits = defineEmits({
    'item-click'(event, item) {
        // this.idOpen1 = item.id;
        return !!(event && item)
    },
    'update:collapsed'(collapsed) {
        return !!(typeof collapsed === 'boolean')
    },
})

const {

} = initSidebar(props, emits)

const activeShow = ref(undefined)

const computedMenu = computed(() => {
    function transformItems(items) {
        let it = items.map((item) => {
            return {
                ...item,
                ...(item.child && { child: transformItems(item.child) }),
            }
        })
        return it;

    }
    return transformItems(props.menu)
})



const updateActiveShow = (id) => {
    activeShow.value = id;
}

function folderButton() {


}


</script>
