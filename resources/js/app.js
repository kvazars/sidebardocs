import { createApp } from "vue";
import { createPinia } from "pinia";

import App from "./App.vue";
import router from "./router/router";

import CoreuiVue from "@coreui/vue";
// import 'bootstrap/dist/css/bootstrap.min.css'
import "bootstrap/dist/js/bootstrap.bundle.min.js";

import { createVCodeBlock } from "@wdns/vue-code-block";
import "vue3-toastify/dist/index.css";
import "bootstrap-icons/font/bootstrap-icons.css";

const VCodeBlock = createVCodeBlock({});

const app = createApp(App);
app.use(createPinia());
app.use(router);
app.use(CoreuiVue);
app.use(VCodeBlock);

app.mount("#app");
