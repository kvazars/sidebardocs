<template>

    <div class="vsm--wrapper">
        <SidebarMenuScroll>
            <div class="v-sidebar-menu position-relative">
                <ul class="vsm--menu">
                    <SidebarMenuItem v-for="item in computedMenu" :key="item.id" :item="item"
                        @click="folderButton(item.id)">
                        <template #dropdown-icon="{ isOpen }">
                            <button class="btn btn-primary" @click="(folderButton(item.id))">...</button>

                            <slot name="dropdown-icon" v-bind="{ isOpen }">
                                <span class="vsm--arrow_default " />
                            </slot>
                        </template>
                    </SidebarMenuItem>
                </ul>
            </div>
        </SidebarMenuScroll>
    </div>
</template>

<script>

import { initSidebar } from 'vue-sidebar-menu/src/use/useSidebar'
import SidebarMenuItem from 'vue-sidebar-menu/src/components/SidebarMenuItem.vue'
import SidebarMenuScroll from 'vue-sidebar-menu/src/components/SidebarMenuScroll.vue'
export default {
    components: [SidebarMenuItem, SidebarMenuScroll],
    props: ["menu"],
    data() {
        return {
            computedMenu: [],
            idOpen: null,
        }
    },
    mounted() {
        this.computedMenu = this.transformItems(this.menu);
    },
    methods: {
        transformItems(items) {
            let it = items.map((item) => {
                return {
                    ...item,
                    ...(item.child && { child: this.transformItems(item.child) }),
                }
            })
            return it;
        },
        folderButton(id) {
            console.log(id);

        }
    }
}
</script>

<script setup>
const props = defineProps({
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
        this.idOpen = item.id;
        return !!(event && item)
    },
    'update:collapsed'(collapsed) {
        return !!(typeof collapsed === 'boolean')
    },
})

initSidebar(props, emits)



</script>
