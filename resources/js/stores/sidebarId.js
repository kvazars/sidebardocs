import { defineStore } from "pinia";

export const useSidebarIdStore = defineStore("sidebarId", {
	state: () => {
		return {
			id: null,
			folder: false,
			name: null,
		};
	},
	actions: {
		changeId(id, name = "") {
			this.id = id;
			this.name = name;
		},
	},
});
