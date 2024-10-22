<script>

import SidebarNav from "@/components/SidebarNav.vue";
import { useSidebarStore } from "@/stores/sidebar.js";
import ContextMenu from '@/components/ContextMenu.vue';

export default {
	components: { SidebarNav, ContextMenu },
	props: ["menu", "datasend", "getMenu", "showToast"],
	data() {
		return {
			folderTitle: null,
			visibleModalFolder: false,
			sidebar: useSidebarStore(),
			contextMenuActions: ([
				{ label: 'Редактировать', action: 'editFolder' },
				{ label: 'Создать папку', action: 'newFolder' },
				{ label: 'Создать ресурс', action: 'newFile' },
				{ label: 'Удалить папку', action: 'deleteFolder' },
			]),
			showMenu: false,
			visibleModalFolder: false,
			folderId: null,
			folderParent: null,
			menuX: null,
			menuY: null,
		}
	},
	methods: {
		closeContextMenu() {
			this.showMenu = false;
		},
		save() {
			let form = new FormData();
			form.append("name", folderTitle);
			if (folderParent) {
				form.append("tree_id", folderParent);
			} else {
				form.append("id", folderId);
			}
			this
				.datasend("folder", "POST", form)
				.then((res) => {
					if (res.success) {
						this.getMenu();
						this.folderTitle = "";
						this.folderId = "";
						this.folderParent = "";
						this.visibleModalFolder = false;
						this.showToast(res.success, res.message);
					}
				})
				.catch((error) => {
					console.log(error);
				});
		},
		newFolder() {
			this.visibleModalFolder = true;
			this.folderTitle = "";
			this.folderParent = store.id;
			this.folderId = "";
		},
		newFile() {
			this.$router.push({ name: "CreateFile", params: { parent: store.id } });
		},
		deleteFolder() {
			this.datasend("folder/" + store.id, "DELETE", {})
				.then((res) => {
					this.showToast(res.success, res.message);
					this.getMenu();
				})
				.catch((error) => console.log(error));
		},
		showContextMenu(event, item) {
			this.treeId = item.id;
			this.treeName = item.name;
			this.showMenu = true;
			this.menuX = event.clientX;
			this.menuY = event.clientY;
		},
		editFolder(name, id) {
			this.visibleModalFolder = true;
			this.folderTitle = name;
			this.folderId = id;
			this.folderParent = "";
		},
		handleActionClick(action) {
			this.closeContextMenu();
			if (action == 'editFolder') {
				editFolder(this.treeId, this.treeName);
			}
			else if (action == 'newFolder') {
				newFolder();
			}
			else if (action == 'newFile') {
				newFile();
			}
			else if (action == 'deleteFolder') {
				deleteFolder();
			}

		}
	},

}




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

		<button class="btn btn btn-light p-0" @click="() => {
		visibleModalFolder = true;
	}
		">
			<i class="fa fa-plus-circle fs-4" aria-hidden="true"></i>
		</button>
		<CSidebarFooter class="border-top d-none d-lg-flex">
			<CSidebarToggler @click="sidebar.toggleUnfoldable()" />
		</CSidebarFooter>
	</CSidebar>

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
	<ContextMenu v-if="showMenu" :actions="contextMenuActions" @action-clicked="handleActionClick" :x="menuX"
		:y="menuY" />

	<div class="overlay" @click="closeContextMenu" v-if="showMenu" />
</template>
