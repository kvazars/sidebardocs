<template>
	<div class="vsm--wrapper">
		<SidebarMenuScroll>
			<div class="v-sidebar-menu position-relative">
				<ul class="vsm--menu">
					<SidebarMenuItem
						v-for="item in menu"
						:key="item.id"
						:item="item"
					>
						<template #dropdown-icon="{ isOpen }">
							<button
								class="btn"
								data-bs-toggle="dropdown"
								aria-expanded="false"
								type="button"
							>
								...
							</button>
							<ul class="dropdown-menu">
								<li>
									<button
										class="dropdown-item"
										@click="editFolder()"
									>
										Редактировать
									</button>
								</li>
								<li>
									<button
										class="dropdown-item"
										@click="newFolder()"
									>
										Создать папку
									</button>
								</li>
								<li>
									<button
										class="dropdown-item"
										@click="newFile()"
									>
										Создать ресурс
									</button>
								</li>
								<li>
									<button
										class="dropdown-item"
										@click="deleteFolder()"
									>
										Удалить папку
									</button>
								</li>
							</ul>
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

const folderTitle = defineModel();
const folderId = ref();
const folderParent = ref();


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
		console.log(item);

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
