<script>
import AppFooter from "@/components/AppFooter.vue";
import AppHeader from "@/components/AppHeader.vue";
import AppSidebar from "@/components/AppSidebar.vue";
// import { data } from 'autoprefixer';
import { useUserDataStore } from "../stores/userData";
import { toast } from "vue3-toastify";

// const store = useUserDataStore();
export default {
	components: { AppFooter, AppHeader, AppSidebar },
	data() {
		return {
			menu: [],
			store: useUserDataStore(),
			api: "http://localhost:8000/api/",
		};
	},
	mounted() {
		this.getMenu();
	},
	methods: {
		async datasend(path, method = "POST", data = {}) {
			const myHeaders = new Headers();
			if (this.store.token) {
				myHeaders.append("Authorization", `Bearer ${this.store.token}`);
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
				this.store.changeToken(null);
			}
			return await response.json();
		},
		catchError(error) {
			this.showToast(false, error)
		},
		getMenu() {
			this.datasend("folder", "GET", {})
				.then((res) => {
					let menus = res;

					// let r = JSON.parse(a);
					function menucreateparent() {
						let rrr = [];
						menus.forEach((e) => {
							if (e.tree_id == null) {
								e.title = e.name;
								e.icon = "fa fa-folder";

								e.child = menucreate(e.id);
								rrr.push(e);
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
					multiple: false,
					autoClose: 3000,
				});
			}
		},
	},
};
</script>

<template>
	<div class=" vh-100">
		<template v-if="menu.length > 0">
			<AppSidebar :catchError="catchError" :showToast="showToast" :menu="menu" :datasend="datasend" :getMenu="getMenu" />
		</template>

		<div class="wrapper d-flex flex-column">
			<AppHeader />
			<div class="body flex-grow-1">
				<CContainer class="px-4">
					<router-view :catchError="catchError" :datasend="datasend" :api="api" :getMenu="getMenu" :showToast="showToast"
						:key="$route.fullPath" />
				</CContainer>
			</div>
			<AppFooter />
		</div>
	</div>
</template>
