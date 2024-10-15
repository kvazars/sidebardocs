import { defineStore } from 'pinia'

export const useSidebarIdStore = defineStore('sidebarId', {
  state: () => {
    return {
      id: null,
      folder: false,
      param: null,
    }
  },
  getters: {
    getId() {
      return
    },
  },
  actions: {
    changeId(id) {
      this.id = id
    },
    changeFolder(folder = true, param) {
      this.folder = folder;
      this.param = param;
    },
  
  },
})
