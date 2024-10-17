<template>
  <div>

    <div id="file" class="print-container p-4">
      <h1 class="text-center" v-html="pagetitle"></h1>
      <hr>
      <div v-for="val in fileData" :key="val">
        <p v-if="val.type == 'paragraph'" v-html="val.data.text" class="my-4"></p>
        <div v-if="val.type == 'code'" class="my-4">
          <VCodeBlock :code="val.data.code" highlightjs label="Пример кода" :lang="val.data.language" theme="monokai" />
        </div>
        <div v-if="val.type == 'image'" class="editorImageBlock text-center my-4"
          :class="{ 'editorImageBlockStretched': val.data.stretched, 'editorImageBlockBorder': val.data.withBorder, 'editorImageBlockBackground': val.data.withBackground }">

          <img :src="val.data.file.url" :alt="val.data.caption" class="editorImage text-center" :style="''" />
          <p v-html="val.data.caption" class="text-center fst-italic"></p>

        </div>
        <div v-if="val.type == 'attaches'" class="my-4">
          <i class="fa fa-file"></i> <a :href="val.data.file.url" download> {{ val.data.title }}</a>

        </div>

        <div class="headerBlock text-center my-4" v-if="val.type == 'header'">
          <h2 v-if="val.data.level == 2" v-html="val.data.text"></h2>
          <h3 v-if="val.data.level == 3" v-html="val.data.text"></h3>
          <h4 v-if="val.data.level == 4" v-html="val.data.text"></h4>
        </div>

        <div class="tableBlock my-4" v-if="val.type == 'table'">
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


        <ul class="my-4" v-if="val.type == 'list' && val.data.style == 'unordered'">
          <li v-for="item in val.data.items" :key="item">{{ item }}</li>
        </ul>
        <ol class="my-4" v-if="val.type == 'list' && val.data.style != 'unordered'">
          <li v-for="item in val.data.items" :key="item">{{ item }}</li>
        </ol>
        <div class="text-center my-4" v-if="val.type == 'embed'">
          <iframe :src="val.data.embed" :width="val.data.width" :height="val.data.height"></iframe>
        </div>
        <div v-if="val.type == 'quote'" class="my-4">
          <figure class="card card-body" :class="{ 'text-center': val.data.alignment == 'center' }">
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
            <button class="dropdown-item" @click="html2doc">Экспорт <i class="fa fa-file-word-o"
                aria-hidden="true"></i></button>

          </li>
          <li>
            <ExportToPdf filename="document">
              <button class="dropdown-item" href="#">Экспорт <i class="fa fa-file-pdf-o"
                  aria-hidden="true"></i></button>
            </ExportToPdf>
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
  methods: {
    b64toBlob(b64Data, contentType, sliceSize) {
         contentType = contentType || '';
         sliceSize = sliceSize || 1024;
         function charCodeFromCharacter(c) {
            return c.charCodeAt(0);
         }
         let byteCharacters = atob(b64Data);
         let byteArrays = [];
         for (let offset = 0; offset < byteCharacters.length; offset += sliceSize) {
          let slice = byteCharacters.slice(offset, offset + sliceSize);
          let byteNumbers = Array.prototype.map.call(
               slice, charCodeFromCharacter);
               let byteArray = new Uint8Array(byteNumbers);
            byteArrays.push(byteArray);
         }
         let blob = new Blob(byteArrays, { type: contentType });
         return blob;
      },
    pdfDownload(){
      let pdfText = document.querySelector("#file").innerHTML;
      let pdfTextEncoded = btoa(unescape(encodeURIComponent(pdfText)));
      let blob = this.b64toBlob(pdfTextEncoded, "application/pdf");
      let blobUrl = URL.createObjectURL(blob);
            console.log("url: " + blobUrl)
         textContainer.innerText = pdfText;
         pdfContainer.innerHTML =
            '<object id="pdf"' +
               'width="400" height="240" type="application/pdf"' +
               'src="' + blobUrl+ '">' +
               '<span>PDF plugin is not available.</span>' +
            '</object>';
    },
    html2doc() {
      let els = document.querySelector("#file").innerHTML;


      let html = `<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
  <head>
  <meta charset='utf-8'>
  <title>Export HTML To Doc</title>
  </head>
  <body>
   ${els}
  </body>
 </html>`;
      var blob = new Blob(['\ufeff', html], {
        type: 'application/msword'
      });
      let url = 'data:application/vnd.ms-word;charset=utf-8,' + encodeURIComponent(html);

      let filename = this.pagetitle + '.doc';
      var downloadLink = document.createElement("a");
      document.body.appendChild(downloadLink);
      if (navigator.msSaveOrOpenBlob) {
        navigator.msSaveOrOpenBlob(blob, filename);
      } else {
        downloadLink.href = url;
        downloadLink.download = filename;
        downloadLink.click();
      }
      document.body.removeChild(downloadLink);
    }
  },
  props: ['id', 'datasend'],
  mounted() {
    this.datasend("resource/" + this.id, "GET", {}).then(res => {

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