<script>
import SidebarNav from "@/components/SidebarNav.vue";
import { useSidebarStore } from "@/stores/sidebar.js";
import ContextMenu from "@/components/ContextMenu.vue";
import { useAuthIdStore } from "../stores/authId";


export default {
	components: { SidebarNav, ContextMenu },
	props: ["menu", "datasend", "getMenu", "showToast", "catchError"],
	data() {
		return {
			folderTitle: "",
			auths: useAuthIdStore(),
			contextMenuActionsFolder: [
				{ label: "Редактировать", action: "editFolder" },
				{ label: "Создать папку", action: "newFolder" },
				{ label: "Создать ресурс", action: "newFile" },
				{ label: "Удалить папку", action: "deleteFolder" },
			],
			contextMenuActionsFile: [
				{ label: "Редактировать", action: "editFile" },
				{ label: "Удалить", action: "deleteFile" },
			],
			visibleModalFolder: false,
			sidebar: useSidebarStore(),

			showMenu: false,
			visibleModalFolder: false,
			folderId: null,
			treeId: null,
			treeType: null,
			treeName: null,
			folderParent: null,
			menuX: null,
			menuY: null,
		};
	},
	methods: {
		closeContextMenu() {
			this.showMenu = false;
		},
		save() {
			let form = new FormData();
			form.append("name", this.folderTitle);

			if (this.folderParent) {
				form.append("tree_id", this.folderParent);
			} else {
				form.append("id", this.folderId);
			}
			this.datasend("folder", "POST", form)
				.then((res) => {
					if (res.success) {
						this.getMenu();
						this.folderTitle = "";
						this.folderId = "";
						this.folderParent = "";
						this.visibleModalFolder = false;
						this.showToast(res.success, res.message);
					} else if (res.errors) {
						this.catchError(res.errors);
					}
				})
				.catch((error) => {
					console.log(error);
				});
		},
		newFolder(id) {
			this.visibleModalFolder = true;
			this.folderTitle = "";
			this.folderParent = id;
			this.folderId = null;
		},
		newFile(id) {
			this.$router.push({ name: "CreateFile", params: { parent: id } });
		},
		deleteFolder(id) {
			this.datasend("folder/" + id, "DELETE", {})
				.then((res) => {
					this.showToast(res.success, res.message);
					this.getMenu();
				})
				.catch((error) => console.log(error));
		},
		showContextMenu(event, item) {
			this.treeId = item.id;
			this.treeType = item.type;
			this.treeName = item.name;
			this.showMenu = true;
			this.menuX = event.clientX;
			this.menuY = event.clientY;
		},
		editFolder(id, name) {
			this.visibleModalFolder = true;
			this.folderTitle = name;
			this.folderId = id;
			this.folderParent = "";
		},
		handleActionClick(action) {
			this.closeContextMenu();

			if (action == "editFolder") {
				this.editFolder(this.treeId, this.treeName);
			} else if (action == "newFolder") {
				this.newFolder(this.treeId);
			} else if (action == "newFile") {
				this.newFile(this.treeId);
			} else if (action == "deleteFolder") {
				this.deleteFolder(this.treeId);
			} else if (action == "editFile") {
				this.$router.push({
					name: "EditFile",
					params: { id: this.treeId },
				});
			} else if (action == "deleteFile") {
				this.datasend("resource/" + this.treeId, "DELETE", {})
					.then((res) => {
						if (res.success) {
							this.getMenu();
							this.showToast(res.success, res.message);
							this.$router.push({ name: "Home" });
						}
					})
					.catch((error) => console.log(error));
			}
		},
		addFirstLevel() {
			this.visibleModalFolder = true;
			this.folderTitle = "";
			this.folderId = null;
			this.folderParent = "new";
		},
	},
};
</script>

<template>
	<CSidebar class="border-end" colorScheme="light" position="fixed" :unfoldable="sidebar.unfoldable"
		:visible="sidebar.visible" @visible-change="(value) => sidebar.toggleVisible(value)">

		<CSidebarHeader class="border-bottom">
			<RouterLink custom to="/" v-slot="{ href, navigate }">
				<CSidebarBrand v-bind="$attrs" as="a" :href="href" @click="navigate">
				</CSidebarBrand>
			</RouterLink>
			<CCloseButton class="d-lg-none" dark @click="sidebar.toggleVisible()" />
		</CSidebarHeader>
		<SidebarNav :showContextMenu="showContextMenu" :menu="menu" />

		<button class="btn btn btn-light p-0" @click="addFirstLevel" v-if="auths.role=='ceo'">
			<i class="fa fa-plus-circle fs-4" aria-hidden="true"></i>
		</button>
		<CSidebarFooter class="border-top d-none d-lg-flex">
			<CSidebarToggler @click="sidebar.toggleUnfoldable()" />
		</CSidebarFooter>
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
		<ContextMenu v-if="showMenu && auths.id" :actions="treeType == 'folder'
		
			? contextMenuActionsFolder
			: contextMenuActionsFile
			" @action-clicked="handleActionClick" :x="menuX" :y="menuY" />

		<div class="overlay" @click="closeContextMenu" v-if="showMenu" />
	</CSidebar>
</template>
