<script setup>
import { ref } from "vue";
import { RouterLink } from "vue-router";

import SidebarNav from "@/components/SidebarNav.vue";
import { useSidebarStore } from "@/stores/sidebar.js";

const props = defineProps(["menu", "datasend", "getMenu", "showToast"]);
const sidebar = useSidebarStore();
const folderTitle = defineModel();
const visibleModalFolder = ref(false);

function save() {
	let form = new FormData();
	form.append("name", folderTitle.value);
	form.append("parent", 0);
	props
		.datasend("folder", "POST", form)
		.then((res) => {
			props.getMenu();
			folderTitle.value = "";
			visibleModalFolder.value = false;
			props.showToast(res.success, res.message);
		})
		.catch((error) => {
			console.log(error);
		});
}
</script>

<template>
	<CSidebar
		class="border-end"
		colorScheme="light"
		position="fixed"
		:unfoldable="sidebar.unfoldable"
		:visible="sidebar.visible"
		@visible-change="(value) => sidebar.toggleVisible(value)"
	>
		<CSidebarHeader class="border-bottom">
			<RouterLink custom to="/" v-slot="{ href, navigate }">
				<CSidebarBrand
					v-bind="$attrs"
					as="a"
					:href="href"
					@click="navigate"
				>
				</CSidebarBrand>
			</RouterLink>
			<CCloseButton
				class="d-lg-none"
				dark
				@click="sidebar.toggleVisible()"
			/>
		</CSidebarHeader>
		<SidebarNav
			:menu="props.menu"
			:datasend="props.datasend"
			:getMenu="props.getMenu"
      :showToast="props.showToast"
		/>

		<button
			class="btn btn btn-light p-0"
			@click="
				() => {
					visibleModalFolder = true;
				}
			"
		>
			<i class="fa fa-plus-circle fs-4" aria-hidden="true"></i>
		</button>
		<CSidebarFooter class="border-top d-none d-lg-flex">
			<CSidebarToggler @click="sidebar.toggleUnfoldable()" />
		</CSidebarFooter>
	</CSidebar>

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
</template>
