<template>

    <div class="vsm--wrapper">

        <SidebarMenuScroll>
            <div class="v-sidebar-menu position-relative">
                <ul class="vsm--menu">
                    <SidebarMenuItem v-for="item in computedMenu" :key="item.id" :item="item">
                        <template #dropdown-icon="{ isOpen }">
                            <button class="btn" data-bs-toggle="dropdown" aria-expanded="false"
                                type="button">...</button>
                            <ul class="dropdown-menu">
                                <li><button class="dropdown-item"
                                        @click="folderButton('editFolder')">Редактировать</button></li>
                                <li><button class="dropdown-item" @click="folderButton('createFolder')">Создать
                                        папку</button></li>
                                <li><button class="dropdown-item" @click="folderButton('createFile')">Создать
                                        ресурс</button></li>
                            </ul>
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
import { useSidebarIdStore } from "../stores/sidebarId";
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
            store: useSidebarIdStore(),
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
        omg() {
            alert();
        }

    }
}
</script>

<script setup>
import { useRouter } from "vue-router";

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
        // console.log(item);

        const store = useSidebarIdStore();
        store.changeId(item.id);

        if (store.folder) {

            store.changeFolder(false, null);
        }
        // this.idOpen = item.id;
        return !!(event && item)
    },
    'update:collapsed'(collapsed) {
        return !!(typeof collapsed === 'boolean')
    },


})
const router = useRouter();
function folderButton(param) {
    const store = useSidebarIdStore();
    store.changeFolder(true, param);

    switch (store.param) {
        case "editFolder":
            // this.$router.push({ name: 'EditorEdit', params: { id: store.id } });
            // this.omg();
            alert(store.id);
            router.push({ name: 'EditorEdit', params: { id: store.id } })
            break;

        default:
            break;
    }
}
initSidebar(props, emits)


</script>
