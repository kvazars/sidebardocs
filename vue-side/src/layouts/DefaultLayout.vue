
<script>
import { CContainer } from '@coreui/vue'
import  AppFooter  from '@/components/AppFooter.vue'
import  AppHeader  from '@/components/AppHeader.vue'
import AppSidebar  from '@/components/AppSidebar.vue'
// import { data } from 'autoprefixer';
import { useUserDataStore } from '../stores/userData';

const api = 'http://localhost:8000/api/'
// const store = useUserDataStore();
export default {
  components:{AppFooter, AppHeader, AppSidebar},
  data() {
    return {
      menu: [],
      store: useUserDataStore(),
    }
  },
  mounted() {
    this.getMenu();
  },
  methods: {
    async datasend(path, method = "POST", data = {}) {
      // const api = 'http://localhost:8000/api/'
      // const store = useUserDataStore();
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
        myHeaders.append('Content-Type', 'application/json');
        requestOptions.body = JSON.stringify(object);
      }

      let response = await fetch(api + path, requestOptions);
      if (response.status == 403) {
        this.store.changeToken(null);
      }
      return await response.json();
    },
    getMenu() {
      this.datasend('folder', 'GET', {}).then((res) => {
        let menus = res;
        // console.log(menus);
        // let r = JSON.parse(a);

        function menucreate(i = 0) {
          let rrr = [];
          menus.forEach((e) => {
            if (e.tree_id == i) {
             if(e.type=='file'){
              e.href = '/files/'+e.id;
            }
              e.title = e.name;
              e.icon = e.type=='folder'?'fa fa-folder':'fa fa-file';
              e.child = menucreate(e.id);
              rrr.push(e);

            }
          });

          //rrr.sort((f, s) => f.tree_id - s.tree_id);
          //console.log(rrr);
          return rrr;
        }


        this.menu = menucreate();
        //co//nsole.log(menu);

        // sidebar = useSidebarStore();

        // this.sidebar = useSidebarStore();
        // console.log(menu12);

      }).catch((error) => {
        console.log(error);
      });
    }
  }
}
</script>

<template>
  <div>
    <AppSidebar :menu v-if="menu.length > 0" />

    <div class="wrapper d-flex flex-column min-vh-100">
      <AppHeader />
      <div class="body flex-grow-1">
        <CContainer class="px-4" lg>
          <router-view :datasend :key="$route.fullPath" />
        </CContainer>
      </div>
      <AppFooter />
    </div>
  </div>

</template>
