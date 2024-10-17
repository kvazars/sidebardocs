<template>

    <div class="vsm--wrapper">

        <SidebarMenuScroll>
            <div class="v-sidebar-menu position-relative">
                <ul class="vsm--menu">
                    <SidebarMenuItem v-for="item in computedMenu" :key="item.id" :item="item">
                        <template #dropdown-icon="{ isOpen }" >
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
        console.log(this.computedMenu);
        
    },
    methods: {
        transformItems(items) {
            
            let it = items.map((item) => {
                console.log(item);
                if(item.type=='folder'&&item.child.length==0){
                    item.child=[{}];
                }
                
                return {
                    ...item,
                    ...(item.child && { child: this.transformItems(item.child) }),
                }
            })
            return it;
        },

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
        case "createFolder":
            router.push({ name: 'NewFolder', params: { parent: store.id } })
            break;
        case "editFolder":
            router.push({ name: 'EditFolder', params: { id: store.id } })
            break;
        case "createFile":
            router.push({ name: 'CreateFile', params: {parent: store.id}})
            break;
        default:
            break;
    }
}
initSidebar(props, emits)


</script>
