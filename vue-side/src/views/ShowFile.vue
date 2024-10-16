<template>
  <div>
    <div id="file" class="print-container p-4">

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
          <figure >
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
  props: ['id'],
  mounted() {
    // //this.fileParser();
  },

  data() {
    return {

      fileData: [
        {
          id: '_dPTGC-2dC',
          type: 'image',
          data: {
            caption: '123123',
            withBorder: true,
            withBackground: false,
            stretched: true,
            file: {
              url: 'http://localhost:8000/contentImages/bw3MxhLzjmx9YQjqttnbDzQGtNlhUUngtKgiJYEu.jpg',
            },
          },
        },
        {
          id: 'ruK35tRzGR',
          type: 'list',
          data: {
            style: 'unordered',
            items: ['wqd', 'qwd'],
          },
        },
        {
          id: 'RXIyHESczT',
          type: 'paragraph',
          data: {
            text: 'qwd',
          },
        },
        {
          type: 'header',
          data: {
            text: 'Why Telegram is the best messenger',
            level: 4,
          },
        },
        {
          type: 'table',
          data: {
            withHeadings: true,
            stretched: false,
            content: [
              ['Kine', 'Pigs', 'Chicken'],
              ['1 pcs', '3 pcs', '12 pcs'],
              ['100$', '200$', '150$'],
            ],
          },
        },
        {
          id: 'UidmH8dcer',
          type: 'code',
          data: {
            code: "<?php\nfunction removeSpace(string $str): string {\n    return str_replace(' ', '', $str);\n}\n?>",
            language: 'php',
          },
        },
        {
          id: 'UidmH8dcer',
          type: 'code',
          data: {
            code: "child = document.createElement('table');\nel.data.content.forEach((element, n) => {\nlet tr = document.createElement('tr');\nelement.forEach((element1, num) => {\nlet td = document.createElement(n == 0 && el.data.withHeadings ? 'th' : 'td');\ntd.textContent = element1;\ntr.appendChild(td);\n})\nchild.appendChild(tr)\n}) ",
            language: 'javascript',
          },
        },
        {
          type: 'embed',
          data: {
            service: 'coub',
            source: 'https://vk.com/video-223871583_456239712',
            embed: 'https://vk.com/video_ext.php?oid=-223871583&id=456239712&hash=74a34e322c9352f3',
            width: 580,
            height: 320,
            caption: 'My Life',
          },
        },
      ],
    }
  },
}
</script>