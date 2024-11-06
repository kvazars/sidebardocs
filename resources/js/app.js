import { createApp } from "vue";
import { createPinia } from "pinia";

import App from "./App.vue";
import router from "./router/router";

import CoreuiVue from "@coreui/vue";
import "font-awesome/css/font-awesome.min.css";
import "bootstrap/dist/js/bootstrap.bundle";
import { createVCodeBlock } from "@wdns/vue-code-block";
import "vue3-toastify/dist/index.css";
import VueFilesPreview from 'vue-files-preview'
import 'vue-files-preview/lib/style.css'

const VCodeBlock = createVCodeBlock({});

const app = createApp(App);
app.use(createPinia());
app.use(VueFilesPreview)
app.use(router);
app.use(CoreuiVue);
app.use(VCodeBlock);

app.mount("#app");
