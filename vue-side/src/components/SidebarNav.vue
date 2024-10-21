<template>
	<div class="vsm--wrapper">
		<SidebarMenuScroll>
			<div class="v-sidebar-menu">
				<ul class="vsm--menu">
					<SidebarMenuItem :editFolder="editFolder" v-for="item in menu" :key="item.id" :item="item">
						<template #dropdown-icon="{ isOpen }">
							<button class="btn" type="button" @click="showContextMenu($event)">
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


		<CModal :visible="visibleModalFolder" @close="() => {
			visibleModalFolder = false;
		}
			" aria-labelledby="FolderLabel">
			<CModalHeader>
				<CModalTitle id="FolderLabel">Новая папка</CModalTitle>
			</CModalHeader>
			<CModalBody>
				<div class="w-100 d-flex flex-column gap-4">
					<div class="w-100 d-flex flex-column gap-2">
						<CFormInput v-model="folderTitle" name="folderName" type="text" placeholder="Новое имя папки" />
					</div>
				</div>
			</CModalBody>
			<CModalFooter>
				<CButton color="secondary" @click="() => {
					visibleModalFolder = false;
				}
					">
					Отмена
				</CButton>
				<CButton color="primary" @click="() => {
					save();
				}
					">Сохранить</CButton>
			</CModalFooter>
		</CModal>
		<!-- <ContextMenu
        v-if="showMenu"
        :actions="contextMenuActions"
        @action-clicked="handleActionClick"
        :x="menuX"
        :y="menuY"
    />
	<div class="overlay" @click="closeContextMenu" v-if="showMenu" />
	 -->
	</div>
</template>

<script>
import { useSidebarIdStore } from "../stores/sidebarId";
import { initSidebar } from "@/tree_menu/src/use/useSidebar";
import SidebarMenuItem from "@/tree_menu/src/components/SidebarMenuItem.vue";
import SidebarMenuScroll from "@/tree_menu/src/components/SidebarMenuScroll.vue";
import "@/tree_menu/dist/vue-sidebar-menu.css";
import { defineEmits } from "vue";
// import { useRouter } from "vue-router";
// import { ref } from "vue";
export default {
	components: { SidebarMenuItem, SidebarMenuScroll },
	props: ["menu",
		"collapsed",
		"datasend",
		"getMenu",
		"showToast"],
	setup(props) {

		const emits = defineEmits({
			"item-click"(event, item) {
				console.log(1);

				// item.disabled = true;
				const store = useSidebarIdStore();
				store.changeId(item.id, item.name);
				return !!(event && item);
			},
			"update:collapsed"(collapsed) {
				return !!(typeof collapsed === "boolean");
			},
		});
		initSidebar(props, emits);
	},
	data() {
		return {
			folderTitle: null,
			folderId: null,
			folderParent: null,
			// folder: {},
			visibleModalFolder: false,
		}
	},
	methods: {
		editFolder(id, name) {
			this.visibleModalFolder = true;
			this.folderId = id;
			this.folderTitle = name;
			this.folderParent = "";
			// this.folder.value.folderParent = null;
			// console.log(folder.value);

		}
	},
}
// const folderTitle = defineModel();
// const folderTitle = ref('');
// const folderId = ref();
// const folderParent = ref();
// const folder = ref({id:null, folderTitle: null, folderParent: ''});

// const showMenu = ref(false);
// const menuX = ref(0);
// const menuY = ref(0);
// const contextMenuActions = ref([
//   { label: 'Редактировать', action: 'editFolder' },
//   { label: 'Создать папку', action: 'newFolder' },
//   { label: 'Создать ресурс', action: 'newFile' },
//   { label: 'Удалить папку', action: 'deleteFolder' },
// ]);

// const showContextMenu = (event) => {
//   event.preventDefault();
//   showMenu.value = true;
//   menuX.value = event.clientX;
//   menuY.value = event.clientY;
// };

// const closeContextMenu = () => {
//   showMenu.value = false;
// };

// function handleActionClick(action){
// 	closeContextMenu();
// 	if(action=='editFolder'){
// 		editFolder();
// 	} 
// 	else if(action == 'newFolder'){
// 		newFolder();
// 	}
// 	else if(action == 'newFile'){
// 		newFile();
// 	}
// 	else if(action == 'deleteFolder'){
// 		deleteFolder();
// 	}

// }
// const props = defineProps([
// 	"menu",
// 	"collapsed",
// 	"datasend",
// 	"getMenu",
// 	"showToast",
// ]);
// const store = useSidebarIdStore();

// const emits = defineEmits({
// 	"item-click"(event, item) {
// 		console.log(1);

// 		// item.disabled = true;
// 		const store = useSidebarIdStore();
// 		store.changeId(item.id, item.name);
// 		return !!(event && item);
// 	},
// 	"update:collapsed"(collapsed) {
// 		return !!(typeof collapsed === "boolean");
// 	},
// });
// const router = useRouter();
// const visibleModalFolder = ref(false);
// function editFolder(id, name) {
// 	visibleModalFolder.value = true;
// 	folder.value.id = id;
// 	folder.value.folderTitle = name;
// 	folder.value.folderParent = null;
// 	console.log(folder.value);

// }

// function newFolder() {
// 	visibleModalFolder.value = true;
// 	folderTitle.value = "";
// 	folderParent.value = store.id;
// 	folderId.value = "";
// }

// function deleteFolder() {
// 	props
// 		.datasend("folder/" + store.id, "DELETE", {})
// 		.then((res) => {
// 			props.showToast(res.success, res.message);
// 			props.getMenu();
// 		})
// 		.catch((error) => console.log(error));
// }
// function newFile() {
// 	router.push({ name: "CreateFile", params: { parent: store.id } });
// }

// function save() {
// 	let form = new FormData();
// 	form.append("name", folderTitle.value);
// 	if (folderParent.value) {
// 		form.append("tree_id", folderParent.value);
// 	} else {
// 		form.append("id", folderId.value);
// 	}
// 	props
// 		.datasend("folder", "POST", form)
// 		.then((res) => {
// 			if (res.success) {
// 				props.getMenu();
// 				folderTitle.value = "";
// 				folderId.value = "";
// 				folderParent.value = "";
// 				visibleModalFolder.value = false;
// 				props.showToast(res.success, res.message);
// 			}
// 		})
// 		.catch((error) => {
// 			console.log(error);
// 		});
// }

</script>
