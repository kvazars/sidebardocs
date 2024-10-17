<script>
import { CContainer } from '@coreui/vue'
import AppFooter from '@/components/AppFooter.vue'
import AppHeader from '@/components/AppHeader.vue'
import AppSidebar from '@/components/AppSidebar.vue'
// import { data } from 'autoprefixer';
import { useUserDataStore } from '../stores/userData';

const api = 'http://localhost:8000/api/'
// const store = useUserDataStore();
export default {
  components: { AppFooter, AppHeader, AppSidebar },
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

        // let r = JSON.parse(a);
        function menucreateparent() {
          let rrr = [];
          menus.forEach((e) => {
            if (e.tree_id == null) {
              e.title = e.name;
              e.icon = 'fa fa-folder';

              e.child = menucreate(e.id);
              rrr.push(e);
            }
          })
          return rrr;
        }

        function menucreate(i = 0) {

          let rrr = [];
          menus.forEach((e) => {


            if (e.tree_id == i) {
              if (e.type == 'file') {
                e.href = '/files/' + e.id;
              }
              e.title = e.name;
              e.icon = e.type == 'folder' ? 'fa fa-folder' : 'fa fa-file';

              e.child = menucreate(e.id);
              rrr.push(e);

            }
          });

          //rrr.sort((f, s) => f.tree_id - s.tree_id);
          //console.log(rrr);
          return rrr;
        }





        this.menu = menucreateparent();
        console.log(this.menu);
        this.menu = this.transformItems(this.menu);
        console.log(this.menu);

      }).catch((error) => {
        console.log(error);
      });
    },
    transformItems(items) {
      let it = items.map((item) => {
        if (item.type == 'folder' && item.child.length == 0) {
          item.child = [{}];
        }

        return {
          ...item,
          ...(item.child && { child: this.transformItems(item.child) }),
        }
      })
      return it;
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
          <router-view :datasend :getMenu :key="$route.fullPath" />
        </CContainer>
      </div>
      <AppFooter />
    </div>
  </div>

</template>
