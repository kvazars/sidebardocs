<script>
import AppFooter from "@/components/AppFooter.vue";
import AppHeader from "@/components/AppHeader.vue";
import AppSidebar from "@/components/AppSidebar.vue";
import AuthWindow from '@/components/AuthWindow.vue';
// import { data } from 'autoprefixer';
// import { useUserDataStore } from "../stores/userData";
import { toast } from "vue3-toastify";
// const store = useUserDataStore();
export default {
	components: { AppFooter, AppHeader, AppSidebar, AuthWindow },
	data() {
		return {
			menu: [],
			api: "http://localhost:8000/api/",
			server: "http://localhost:8000",
			openWindow: false,
		};
	},
	mounted() {
		this.getMenu();
	},
	methods: {
		openWindowFunction() {
			this.openWindow = !this.openWindow;
		},
		async datasend(path, method = "POST", data = {}) {
			const myHeaders = new Headers();
			if (localStorage.getItem('token')) {
				myHeaders.append("Authorization", `Bearer ${localStorage.getItem('token')}`);
			}
			myHeaders.append("Accept", "application/json");
			const requestOptions = {
				method: method,
				headers: myHeaders,
			};
			if (method != "GET") {
				requestOptions.body = data;
			}
			if (method == "PUT") {
				let object = {};
				data.forEach(function (value, key) {
					object[key] = value;
				});
				myHeaders.append("Content-Type", "application/json");
				requestOptions.body = JSON.stringify(object);
			}

			let response = await fetch(this.api + path, requestOptions);
			if (response.status == 403) {
				localStorage.removeItem('token')
			}
			return await response.json();
		},
		catchError(error) {
			// console.log(error, Object.keys.length);
			for (let index = 0; index < Object.keys(error).length; index++) {
				Object.values(error)[index].forEach((element) => {
					this.showToast(false, element);
				});
			}
		},
		getMenu() {
			this.datasend(localStorage.getItem('token') ? "userFolder" : 'folder', "GET", {})
				.then((res) => {
					let menus = res;
					console.log(menus);
					

					// let r = JSON.parse(a);
					function menucreateparent() {
						let rrr = [];
						menus.forEach((e) => {
							//console.log(e);
							// let uNames = [];
							// if (typeof $) {
								
							// }
							if (e.tree_id == null) {
								e.title = e.name;
								e.icon = "fa fa-folder";
								
								e.child = menucreate(e.id);
								rrr.push(e);
								e.tree_id = 0;
							
							}
						});
						return rrr;
					}

					function menucreate(i = 0) {
						let rrr = [];
						menus.forEach((e) => {
							if (e.tree_id == i) {
								if (e.type == "file") {
									e.href = "/files/" + e.id;
								}
								e.title = e.name;
								e.icon =
									e.type == "folder"
										? "fa fa-folder"
										: "fa fa-file"; 
								e.child = menucreate(e.id);
								rrr.push(e);
							}
						});

						return rrr;
					}

					this.menu = menucreateparent();
					this.menu = this.transformItems(this.menu);
				})
				.catch((error) => {
					console.log(error);
				});
		},
		transformItems(items) {
			let it = items.map((item) => {
				if (item.type == "folder" && item.child.length == 0) {
					item.child = [{}];
				}

				return {
					...item,
					...(item.child && {
						child: this.transformItems(item.child),
					}),
				};
			});
			return it;
		},
		showToast(success, message) {
			if (success) {
				toast.success(message, {
					theme: "colored",
					transition: toast.TRANSITIONS.ZOOM,
					position: toast.POSITION.BOTTOM_RIGHT,
					multiple: false,
					autoClose: 3000,
				});
			} else {
				toast.error(message, {
					theme: "colored",
					transition: toast.TRANSITIONS.ZOOM,
					position: toast.POSITION.BOTTOM_RIGHT,
					// multiple: false,
					autoClose: 3000,
				});
			}
		},
	},
};
</script>

<template>
	<div class="vh-100 position-relative">
		<AppSidebar v-if="menu.length > 0" :catchError="catchError" :showToast="showToast" :menu="menu"
			:datasend="datasend" :getMenu="getMenu" />
		<div class="wrapper d-flex flex-column">
			<AppHeader :openWindowFunction />
			<div class="body flex-grow-1">
				<CContainer class="px-4">
					<router-view :server="server" :catchError="catchError" :datasend="datasend" :api="api"
						:getMenu="getMenu" :showToast="showToast" :key="$route.fullPath" />
				</CContainer>
			</div>
			<AppFooter />
		</div>
		<AuthWindow :openWindow :openWindowFunction :api :datasend :catchError :getMenu />
	</div>
</template>
