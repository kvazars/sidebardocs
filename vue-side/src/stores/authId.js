import { defineStore } from "pinia";

export const useAuthIdStore = defineStore("authId", {
	state: () => {
		return {
			id: null,
			name: null,
			role: null,
			token: null,
		};
	},
	actions: {
		changeUser(id, name = null, role = null, token = null) {
			this.id = id;
			this.name = name;
			this.role = role;
			if (token) this.token = token;
		},
	},
});
