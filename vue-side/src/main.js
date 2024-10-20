import { createApp } from "vue";
import { createPinia } from "pinia";

import App from "./App.vue";
import router from "./router";

import CoreuiVue from "@coreui/vue";
import VueSidebarMenu from "vue-sidebar-menu";
import "vue-sidebar-menu/dist/vue-sidebar-menu.css";
import "font-awesome/css/font-awesome.min.css";
import "bootstrap/dist/js/bootstrap.bundle";
import { createVCodeBlock } from "@wdns/vue-code-block";

const VCodeBlock = createVCodeBlock({});

const app = createApp(App);
app.use(createPinia());
app.use(router);
app.use(CoreuiVue);
app.use(VueSidebarMenu);
app.use(VCodeBlock);

app.mount("#app");
