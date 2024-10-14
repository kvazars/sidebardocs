<script>
import Editor from "@/components/Editor.vue";
import { RouterView } from "vue-router";
export default {
  components: {
    Editor
  },
  data() {
    return {
      dataBlock: [{
        "id": "uqvFlzwn4H",
        "type": "paragraph",
        "data": {
          "text": "ewf"
        }
      },
      {
        "id": "zRhbMyXyfh",
        "type": "paragraph",
        "data": {
          "text": "wefwef"
        }
      },
      {
        "id": "8oPZtnSaDA",
        "type": "paragraph",
        "data": {
          "text": "wefwefwe"
        }
      },
      {
        "id": "_dPTGC-2dC",
        "type": "image",
        "data": {
          "caption": "",
          "withBorder": false,
          "withBackground": false,
          "stretched": false,
          "file": {
            "url": "http://localhost:8000/public/contentImages/VmbQj6R78KqFUEnN5yvqOj4j7tE4JUgW8jfO2Wtl.jpg"
          }
        }
      },
      {
        "id": "ruK35tRzGR",
        "type": "list",
        "data": {
          "style": "unordered",
          "items": [
            "wqd",
            "qwd"
          ]
        }
      },
      {
        "id": "RXIyHESczT",
        "type": "paragraph",
        "data": {
          "text": "qwd"
        }
      }],
    }
  },
  mounted() {

  },
  methods: {
    save(data) {
      console.log(data);
    },
    saveEditor() {
      this.$refs.Editor.saveData();
    },


  },
}
</script>

<template>
  <!-- <div>
    <button @click="saveEditor">save Editor</button>
    <Editor :save="save" ref="Editor" :dataBlock="dataBlock" />
  </div> -->
  <!-- <el-config-provider :size="size" :z-index="zIndex">
    <app />
  </el-config-provider> -->
  <RouterView />
</template>