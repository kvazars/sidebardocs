import { defineStore } from "pinia";

export const useUserDataStore = defineStore("userData", {
	state: () => {
		return {
			token: null,
		};
	},
	actions: {
		changeToken(token) {
			this.token = token;
		},
	},
});
