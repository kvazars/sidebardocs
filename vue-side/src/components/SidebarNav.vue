<template>

    <div class="vsm--wrapper">
        <sidebar-menu-scroll>
            <div class="v-sidebar-menu position-relative">
                <ul class="vsm--menu">
                    <SidebarMenuItem v-for="item in computedMenu" :key="item.id" :item="item"
                        :active-show="activeShow" @update-active-show="updateActiveShow">
                       
                        <template #dropdown-icon="{ isOpen }" >
                            <slot name="dropdown-icon" v-bind="{ isOpen }" >
                                <span class="vsm--arrow_default" />
                            </slot>
                        </template>
                    </SidebarMenuItem>
                </ul>
            </div>
        </sidebar-menu-scroll>
    </div>
</template>

<script>
export default {
    compatConfig: { MODE: 3 },
    methods: {
        onItemClicks(id){
            alert(id);
        }
    },
}
</script>

<script setup>
import {
    ref,
    watch,
    getCurrentInstance,
    onMounted,
    onUnmounted,
    computed,
} from 'vue'
import { initSidebar } from 'vue-sidebar-menu/src/use/useSidebar'
import SidebarMenuItem from 'vue-sidebar-menu/src/components/SidebarMenuItem.vue'///'./SidebarNavItem.vue'
import SidebarMenuScroll from 'vue-sidebar-menu/src/components/SidebarMenuScroll.vue'

const props = defineProps({
    menu: {
        type: Array,
        required: true,
    },
    collapsed: {
        type: Boolean,
        default: false,
    },
    width: {
        type: String,
        default: '290px',
    },
    widthCollapsed: {
        type: String,
        default: '65px',
    },
    showChild: {
        type: Boolean,
        default: false,
    },
    showOneChild: {
        type: [Boolean, String],
        default: false,
        validator(value) {
            if (typeof value === 'string') {
                return ['deep'].includes(value)
            } else {
                return typeof value === 'boolean'
            }
        },
    },

    relative: {
        type: Boolean,
        default: false,
    },
    hideToggle: {
        type: Boolean,
        default: false,
    },
    disableHover: {
        type: Boolean,
        default: false,
    },
    linkComponentName: {
        type: String,
        default: undefined,
    },
})

const emits = defineEmits({
    'item-click'(event, item) {
        console.log(item);
        return !!(event && item)
       
    },
    'update:collapsed'(collapsed) {
        return !!(typeof collapsed === 'boolean')
    },
})

const {
    getSidebarRef: sidebarMenuRef,
    getIsCollapsed: isCollapsed,
    updateIsCollapsed,
    unsetMobileItem,
    updateCurrentRoute,
} = initSidebar(props, emits)

const activeShow = ref(undefined)

const computedMenu = computed(() => {
    let id = 0
    function transformItems(items) {
        function randomId() {
            return `${Date.now() + '' + id++}`
        }
        let it= items.map((item) => {
            return {
                id: randomId(),
                ...item,
                ...(item.child && { child: transformItems(item.child) }),
            }
        })
        // console.log(it);
        return it;
        
    }
    return transformItems(props.menu)
})

const sidebarWidth = computed(() => {
    return isCollapsed.value ? props.widthCollapsed : props.width
})

const sidebarClass = computed(() => {
    return [
        'v-sidebar-menu',
        !isCollapsed.value ? 'vsm_expanded' : 'vsm_collapsed',
        
        props.relative && 'vsm_relative',
    ]
})

const updateActiveShow = (id) => {
    activeShow.value = id
}

const onToggleClick = () => {
    unsetMobileItem()
    updateIsCollapsed(!isCollapsed.value)
    emits('update:collapsed', isCollapsed.value)
}

watch(
    () => props.collapsed,
    (currentCollapsed) => {
        unsetMobileItem()
        updateIsCollapsed(currentCollapsed)
    }
)

const router = getCurrentInstance().appContext.config.globalProperties.$router
if (!router) {
    onMounted(() => {
        updateCurrentRoute()
        window.addEventListener('hashchange', updateCurrentRoute)
    })
    onUnmounted(() => {
        window.removeEventListener('hashchange', updateCurrentRoute)
    })
}

defineExpose({
    onRouteChange: updateCurrentRoute,
})
</script>

<style lang="scss">
//@import 'vue-sidebar-menu';

</style>