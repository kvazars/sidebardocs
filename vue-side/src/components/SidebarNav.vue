<template>
	<div class="vsm--wrapper">
		<SidebarMenuScroll>
			<div class="v-sidebar-menu">
				<ul class="vsm--menu">
					<SidebarMenuItem
						:showContextMenu="showContextMenu"
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

<script setup>
import { useSidebarIdStore } from "../stores/sidebarId";
import { initSidebar } from "@/tree_menu/src/use/useSidebar";
import SidebarMenuItem from "@/tree_menu/src/components/SidebarMenuItem.vue";
import SidebarMenuScroll from "@/tree_menu/src/components/SidebarMenuScroll.vue";
import "@/tree_menu/src/scss/vue-sidebar-menu.scss";

const props = defineProps(["menu", "collapsed", "showContextMenu"]);
const store = useSidebarIdStore();
const emits = defineEmits({
	"item-click"(event, item) {
		const store = useSidebarIdStore();
		store.changeId(item.id, item.name);
		return !!(event && item);
	},
	"update:collapsed"(collapsed) {
		return !!(typeof collapsed === "boolean");
	},
});

initSidebar(props, emits);
</script>
