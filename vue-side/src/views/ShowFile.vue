<template>
	<div>
		<div id="file" class="print-container p-4">
			<h1 class="text-center" v-html="pagetitle"></h1>
			<hr />

			<div v-for="val in fileData" :key="val">
				<p
					v-if="val.type == 'paragraph'"
					v-html="val.data.text"
					class="my-4"
				></p>
				<div v-if="val.type == 'code'" class="my-4">
					<VCodeBlock
						:code="val.data.code"
						highlightjs
						label="Пример кода"
						:lang="val.data.language"
						theme="monokai"
					/>
				</div>
				<div
					v-if="val.type == 'image'"
					class="editorImageBlock text-center my-4"
					:class="{
						editorImageBlockStretched: val.data.stretched,
						editorImageBlockBorder: val.data.withBorder,
						editorImageBlockBackground: val.data.withBackground,
					}"
				>
					<DataImage
						:datasend="datasend"
						class="editorImage text-center"
						:src="val.data.file.url"
					/>

					<p
						v-html="val.data.caption"
						class="text-center fst-italic"
					></p>
				</div>

				<div v-if="val.type == 'attaches'" class="my-4">
					<DataFile
						:datasend="datasend"
						:src="val.data.file.url"
						:title="val.data.title"
					/>
				</div>

				<div
					class="headerBlock text-center my-4"
					v-if="val.type == 'header'"
				>
					<h2 v-if="val.data.level == 2" v-html="val.data.text"></h2>
					<h3 v-if="val.data.level == 3" v-html="val.data.text"></h3>
					<h4 v-if="val.data.level == 4" v-html="val.data.text"></h4>
				</div>

				<div class="tableBlock my-4" v-if="val.type == 'table'">
					<table
						class="table table-bordered"
						style="margin: auto"
						:class="!val.data.stretched ? 'table-nonfluid' : ''"
					>
						<tbody>
							<tr
								v-for="(tRow, num) in val.data.content"
								:key="num"
							>
								<!-- eslint-disable -->
								<th
									v-if="num == 0 && val.data.withHeadings"
									v-for="tHeader in tRow"
									:key="tHeader"
								>
									{{ tHeader }}
								</th>
								<!-- eslint-enable -->
								<td v-else v-for="tText in tRow" :key="tText">
									{{ tText }}
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<ul
					class="my-4"
					v-if="val.type == 'list' && val.data.style == 'unordered'"
				>
					<li v-for="item in val.data.items" :key="item">
						{{ item }}
					</li>
				</ul>
				<ol
					class="my-4"
					v-if="val.type == 'list' && val.data.style != 'unordered'"
				>
					<li v-for="item in val.data.items" :key="item">
						{{ item }}
					</li>
				</ol>
				<div class="text-center my-4" v-if="val.type == 'embed'">
					<iframe
						:src="val.data.embed"
						width="100%"
						height="500px"
					></iframe>
					<p
						v-html="val.data.caption"
						class="text-center fst-italic"
					></p>
				</div>
				<div v-if="val.type == 'quote'" class="my-4">
					<figure
						class="card card-body"
						:class="{
							'text-center': val.data.alignment == 'center',
						}"
					>
						<blockquote class="blockquote">
							<p v-html="val.data.text"></p>
						</blockquote>
						<figcaption
							class="blockquote-footer"
							v-html="val.data.caption"
						></figcaption>
					</figure>
				</div>
			</div>
		</div>
		<div class="position-fixed squared">
			<div class="dropdown">
				<button
					class="btn btn-primary"
					type="button"
					data-bs-toggle="dropdown"
					aria-expanded="false"
				>
					<i class="fa fa-cog"></i>
				</button>
				<ul class="dropdown-menu">
					<li v-if="content">
						<router-link
							class="dropdown-item"
							v-if="
								auths.id == content.tree.user_id ||
								auths.role == 'admin'
							"
							:to="{ name: 'EditFile', params: { id: id } }"
							>Редактировать
							<i
								class="fa fa-pencil-square-o"
								aria-hidden="true"
							></i
						></router-link>
					</li>
					<template v-if="content">
						<li
							v-if="
								auths.id == content.tree.user_id ||
								auths.role == 'admin'
							"
						>
							<hr class="dropdown-divider" />
						</li>
					</template>

					<li>
						<button class="dropdown-item" @click="html2doc">
							Экспорт
							<i class="fa fa-file-word-o" aria-hidden="true"></i>
						</button>
					</li>
					<li>
						<ExportToPdf
							:filename="pagetitle ? pagetitle : 'document'"
						>
							<button class="dropdown-item">
								Экспорт
								<i
									class="fa fa-file-pdf-o"
									aria-hidden="true"
								></i>
							</button>
						</ExportToPdf>
					</li>
				</ul>
			</div>
		</div>
	</div>
</template>

<script>
import jsPDF from "jspdf";
import { ExportToWord, ExportToPdf } from "vue-doc-exporter";
import DataImage from "@/components/DataImage.vue";
import DataFile from "@/components/DataFile.vue";
import { useAuthIdStore } from "../stores/authId";

export default {
	components: { ExportToWord, ExportToPdf, DataImage, DataFile },
	methods: {
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
			var blob = new Blob(["\ufeff", html], {
				type: "application/msword",
			});
			let url =
				"data:application/vnd.ms-word;charset=utf-8," +
				encodeURIComponent(html);

			let filename = this.pagetitle + ".doc";
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
		},
	},
	props: ["id", "datasend", "showToast"],
	mounted() {
		this.datasend("resource/" + this.id, "GET", {})
			.then((res) => {
				if (!res.content) {
					this.showToast(res.success, res.message);
					setTimeout(() => {
						this.$router.push({
							name: "NotFound",
						});
					}, 2000);
				} else {
					this.pagetitle = res.name;
					this.fileData = JSON.parse(res.content.data);
					this.content = res.content;
					if (document.querySelector(".sidebar.sidebar-fixed")) {
						document
							.querySelector(".sidebar.sidebar-fixed")
							.classList.remove("show");
					}
				}
			})
			.catch();
	},

	data() {
		return {
			pagetitle: null,
			auths: useAuthIdStore(),
			fileData: [],
			content: null,
		};
	},
};
</script>
