<template>
    <div class="vsm--wrapper">
        <SidebarMenuScroll>
            <div class="v-sidebar-menu">
                <ul class="vsm--menu">
                    <SidebarMenuItem
                        :showContextMenu="showContextMenu"
                        @click="getBreadcrumbs"
                        v-for="item in menu"
                        :key="item.id"
                        :item="item"
                    >
                        <template #dropdown-icon="{ isOpen }">
                            <slot name="dropdown-icon" v-bind="{ isOpen }">
                                <span class="vsm--arrow_default" />
                            </slot>
                        </template>
                    </SidebarMenuItem>
                </ul>
            </div>
        </SidebarMenuScroll>
    </div>
</template>

<script>
import { useSidebarIdStore } from "../stores/sidebarId";
import { initSidebar } from "vue-sidebar-menu/src/use/useSidebar";
import SidebarMenuItem from "vue-sidebar-menu/src/components/SidebarMenuItem.vue";
import SidebarMenuScroll from "vue-sidebar-menu/src/components/SidebarMenuScroll.vue";
import "vue-sidebar-menu/src/scss/vue-sidebar-menu.scss";

export default {
    name: "SidebarNav",
    components: {
        SidebarMenuItem,
        SidebarMenuScroll,
    },
    props: ["menu", "collapsed", "showContextMenu", "getBreadcrumbs"],
    emits: ["item-click", "update:collapsed"],
    created() {
        initSidebar(this.$props, (eventName, ...args) => {
            if (eventName === "item-click") {
                const item = args[1];
                if (item) {
                    useSidebarIdStore().changeId(item.id, item.name);
                }
            }
            this.$emit(eventName, ...args);
        });
    },
};
</script>
