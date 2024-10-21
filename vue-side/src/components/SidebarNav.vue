<template>
	<div class="vsm--wrapper">
		<SidebarMenuScroll>
			<div class="v-sidebar-menu">
				<ul class="vsm--menu">
					<SidebarMenuItem
						v-for="item in menu"
						:key="item.id"
						:item="item"
					>
						<template #dropdown-icon="{ isOpen }">
							<button
								class="btn"
							
								type="button" @click="showContextMenu($event)"
							>
								...
							</button>							
							<slot name="dropdown-icon" v-bind="{ isOpen }">
								<span class="vsm--arrow_default" />
							</slot>
						</template>
					</SidebarMenuItem>
				</ul>
			</div>
		</SidebarMenuScroll>

		<CModal
			:visible="visibleModalFolder"
			@close="
				() => {
					visibleModalFolder = false;
				}
			"
			aria-labelledby="FolderLabel"
		>
			<CModalHeader>
				<CModalTitle id="FolderLabel">Новая папка</CModalTitle>
			</CModalHeader>
			<CModalBody>
				<div class="w-100 d-flex flex-column gap-4">
					<div class="w-100 d-flex flex-column gap-2">
						<CFormInput
							v-model="folderTitle"
							name="folderName"
							type="text"
							placeholder="Новое имя папки"
						/>
					</div>
				</div>
			</CModalBody>
			<CModalFooter>
				<CButton
					color="secondary"
					@click="
						() => {
							visibleModalFolder = false;
						}
					"
				>
					Отмена
				</CButton>
				<CButton
					color="primary"
					@click="
						() => {
							save();
						}
					"
					>Сохранить</CButton
				>
			</CModalFooter>
		</CModal>
		<ContextMenu
        v-if="showMenu"
        :actions="contextMenuActions"
        @action-clicked="handleActionClick"
        :x="menuX"
        :y="menuY"
    />
	<div class="overlay" @click="closeContextMenu" v-if="showMenu" />
	
	</div>
</template>

<script setup>
import { useSidebarIdStore } from "../stores/sidebarId";
import { initSidebar } from "vue-sidebar-menu/src/use/useSidebar";
import SidebarMenuItem from "vue-sidebar-menu/src/components/SidebarMenuItem.vue";
import SidebarMenuScroll from "vue-sidebar-menu/src/components/SidebarMenuScroll.vue";
import "vue-sidebar-menu/dist/vue-sidebar-menu.css";
import { useRouter } from "vue-router";
import { ref } from "vue";
import ContextMenu from '@/components/ContextMenu.vue';

const folderTitle = defineModel();
const folderId = ref();
const folderParent = ref();

const showMenu = ref(false);
const menuX = ref(0);
const menuY = ref(0);
const contextMenuActions = ref([
  { label: 'Редактировать', action: 'editFolder' },
  { label: 'Создать папку', action: 'newFolder' },
  { label: 'Создать ресурс', action: 'newFile' },
  { label: 'Удалить папку', action: 'deleteFolder' },
]);

const showContextMenu = (event) => {
  event.preventDefault();
  showMenu.value = true;
  menuX.value = event.clientX;
  menuY.value = event.clientY;
};

const closeContextMenu = () => {
  showMenu.value = false;
};

function handleActionClick(action){
	closeContextMenu();
	if(action=='editFolder'){
		editFolder();
	} 
	else if(action == 'newFolder'){
		newFolder();
	}
	else if(action == 'newFile'){
		newFile();
	}
	else if(action == 'deleteFolder'){
		deleteFolder();
	}

}
const props = defineProps([
	"menu",
	"collapsed",
	"datasend",
	"getMenu",
	"showToast",
]);
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
const router = useRouter();

const visibleModalFolder = ref(false);
function editFolder() {
	visibleModalFolder.value = true;
	folderTitle.value = store.name;
	folderId.value = store.id;
	folderParent.value = "";
}

function newFolder() {
	visibleModalFolder.value = true;
	folderTitle.value = "";
	folderParent.value = store.id;
	folderId.value = "";
}

function deleteFolder() {
	props
		.datasend("folder/" + store.id, "DELETE", {})
		.then((res) => {
			props.showToast(res.success, res.message);
			props.getMenu();
		})
		.catch((error) => console.log(error));
}
function newFile() {
	router.push({ name: "CreateFile", params: { parent: store.id } });
}

function save() {
	let form = new FormData();
	form.append("name", folderTitle.value);
	if (folderParent.value) {
		form.append("tree_id", folderParent.value);
	} else {
		form.append("id", folderId.value);
	}
	props
		.datasend("folder", "POST", form)
		.then((res) => {
			if (res.success) {
				props.getMenu();
				folderTitle.value = "";
				folderId.value = "";
				folderParent.value = "";
				visibleModalFolder.value = false;
				props.showToast(res.success, res.message);
			}
		})
		.catch((error) => {
			console.log(error);
		});
}

initSidebar(props, emits);
</script>
