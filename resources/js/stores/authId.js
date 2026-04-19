import { defineStore } from "pinia";

export const useAuthIdStore = defineStore("authId", {
    state: () => {
        return {
            id: null,
            name: null,
            role: null,
            token: localStorage.getItem("token"),
            isBootstrapping: false,
        };
    },
    getters: {
        isAuthenticated: (state) => Boolean(state.token && state.id),
        hasToken: (state) => Boolean(state.token),
    },
    actions: {
        changeUser(id, name = null, role = null, token = null) {
            this.id = id;
            this.name = name;
            this.role = role;
            if (token !== null) {
                this.token = token;
            }
        },
        setToken(token) {
            this.token = token;
        },
        setBootstrapping(isBootstrapping) {
            this.isBootstrapping = isBootstrapping;
        },
        clearUser() {
            this.id = null;
            this.name = null;
            this.role = null;
            this.token = null;
            this.isBootstrapping = false;
        },
    },
});
