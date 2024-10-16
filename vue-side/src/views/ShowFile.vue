<template>
  <div>
  
    <div id="file" class="print-container p-4">
      <h1 class="text-center" v-html="pagetitle"></h1>
      <hr>
      <div v-for="val in fileData" :key="val">
        <p v-if="val.type == 'paragraph'" v-html="val.data.text"></p>
        <div v-if="val.type == 'code'">
          <VCodeBlock :code="val.data.code" highlightjs label="Пример кода" :lang="val.data.language" theme="monokai" />
        </div>
        <div v-if="val.type == 'image'" class="editorImageBlock text-center"
          :class="{ 'editorImageBlockStretched': val.data.stretched, 'editorImageBlockBorder': val.data.withBorder, 'editorImageBlockBackground': val.data.withBackground }">
          <img :src="val.data.file.url" :alt="val.data.caption" class="editorImage text-center" :style="''" />
          <p v-html="val.data.caption" class="text-center fst-italic"></p>

        </div>

        <div class="headerBlock text-center" v-if="val.type == 'header'">
          <h2 v-if="val.data.level == 2" v-html="val.data.text"></h2>
          <h3 v-if="val.data.level == 3" v-html="val.data.text"></h3>
          <h4 v-if="val.data.level == 4" v-html="val.data.text"></h4>
        </div>

        <div class="tableBlock" v-if="val.type == 'table'">
          <table class="table table-bordered" style="margin: auto;"
            :class="!val.data.stretched ? 'table-nonfluid' : ''">
            <tbody>
              <tr v-for="(tRow, num) in val.data.content" :key="num">

                <th v-if="num == 0 && val.data.withHeadings" v-for="tHeader in tRow" :key="tHeader">{{ tHeader }}</th>
                <td v-else v-for="tText in tRow" :key="tText">{{ tText }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <template>
          <div></div>
        </template>
        <ul v-if="val.type == 'list' && val.data.style == 'unordered'">
          <li v-for="item in val.data.items" :key="item">{{ item }}</li>
        </ul>
        <ol v-if="val.type == 'list' && val.data.style != 'unordered'">
          <li v-for="item in val.data.items" :key="item">{{ item }}</li>
        </ol>
        <div class="text-center" v-if="val.type == 'embed'">
          <iframe :src="val.data.embed" :width="val.data.width" :height="val.data.height"></iframe>
        </div>
        <div v-if="val.type == 'quote'">
          <figure>
            <blockquote class="blockquote">
              <p v-html="val.data.text"></p>
            </blockquote>
            <figcaption class="blockquote-footer" v-html="val.data.caption"></figcaption>
          </figure>
        </div>
      </div>
    </div>
    <div class="position-fixed squared">
      <div class="dropdown">
        <button class="btn btn-primary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="fa fa-cog"></i>
        </button>
        <ul class="dropdown-menu">
          <li>
            <router-link class="dropdown-item" :to="{ name: 'EditFile', params: { id: id } }">Редактировать <i
                class="fa fa-pencil-square-o" aria-hidden="true"></i></router-link>
          </li>
          <li>
            <hr class="dropdown-divider" />
          </li>
          <li>
            <ExportToWord element="file" filename="document">
              <button class="dropdown-item">Экспорт <i class="fa fa-file-word-o" aria-hidden="true"></i></button>
            </ExportToWord>
          </li>
          <li>
            <ExportToPdf filename="document">
              <button class="dropdown-item" href="#">Экспорт <i class="fa fa-file-pdf-o"
                  aria-hidden="true"></i></button>
            </ExportToPdf>
          </li>
          <li>
            <hr class="dropdown-divider" />
          </li>
          <li>
            <a class="dropdown-item" href="#">Удалить <i class="fa fa-trash-o" aria-hidden="true"></i></a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
import { ExportToWord, ExportToPdf } from 'vue-doc-exporter';

export default {
  components: { ExportToWord, ExportToPdf },

  props: ['id', 'datasend'],
  mounted() {
    this.datasend("resource/"+this.id,"GET",{}).then(res=>{

      this.pagetitle = res.name;
      this.fileData = JSON.parse(res.content.data).blocks;
    }).catch();
  },

  data() {
    return {
      pagetitle: null,
      fileData: [],
    }
  },
}
</script>