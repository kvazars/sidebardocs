<template>
    <div>
        <!-- <div id="fileParser"></div>     -->
        <div v-for="val in fileData">
            <p v-if="val.type == 'paragraph'" v-html="val.data.text"></p>
            <div v-if="val.type == 'code'">
                <VCodeBlock :code="val.data.code" highlightjs label="Пример кода" :lang="val.data.language"
                    theme="monokai" />
            </div>
            <img v-if="val.type == 'image'" :src="val.data.file.url" :alt="val.data.caption">
            <p v-if="val.type == 'image'" v-html="val.data.caption"></p>
            <h2 v-if="val.type == 'header' && val.data.level == 2" v-html="val.data.text"></h2>
            <h3 v-if="val.type == 'header' && val.data.level == 3" v-html="val.data.text"></h3>
            <h4 v-if="val.type == 'header' && val.data.level == 4" v-html="val.data.text"></h4>
            <table class="table table-bordered" :class="!val.data.stretched?'table-nonfluid':''" v-if="val.type == 'table'">
                <tbody>
                <tr v-for="(tRow, num) in val.data.content" :key="num">
                    <th v-if="num == 0 && val.data.withHeadings" v-for="tHeader in tRow">{{ tHeader }}</th>
                    <td v-else v-for="tText in tRow">{{ tText }}</td>
                </tr>
            </tbody>
            </table>
            <template>
                <div>
                </div>
            </template>
            <ul v-if="val.type == 'list' && val.data.style == 'unordered'">
                <li v-for="item in val.data.items">{{ item }}</li>
            </ul>
            <ol v-if="val.type == 'list' && val.data.style != 'unordered'">
                <li v-for="item in val.data.items">{{ item }}</li>
            </ol>
            <iframe v-if="val.type == 'embed'" :src="val.data.embed" :width="val.data.width" :height="val.data.height"></iframe>
            <!-- embed: ({ data }) => {
                switch (data.service) {
                  case "vimeo":
                    return `<iframe src="${data.embed}" height="${data.height}" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>`;
                  case "youtube":
                    return `<iframe width="${data.width}" height="${data.height}" src="${data.embed}" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
                  default:
                    throw new Error(
                      "Only Youtube and Vime Embeds are supported right now."
                    );
                }
              }, -->
            <!-- quote: ({ data }) => {
                return `<blockquote>${data.text}</blockquote> - ${data.caption}`;
              }, -->
            <!-- return `<h${data.level}>${data.text}</h${data.level}>`; -->

        </div>
    </div>

</template>

<script>
export default {
    mounted() {
        // //this.fileParser();
    },
    methods: {
        fileParser() {
            this.fileData.forEach(el => {
                let child;
                switch (el.type) {
                    case 'image':
                        child = document.createElement('img')
                        child.src = el.data.file.url;
                        break;
                    case 'paragraph':
                        child = document.createElement('p')
                        child.textContent = el.data.text;
                        break;
                    case 'list':
                        child = document.createElement(el.data.style == 'unordered' ? 'ul' : 'ol')
                        el.data.items.forEach(li => {
                            let li1 = document.createElement('li');
                            li1.textContent = li;
                            child.appendChild(li1);
                        });

                        break;
                    case 'header':
                        child = document.createElement(el.data.level == 2 ? 'h2' : el.data.level == 3 ? 'h3' : 'h4')
                        child.textContent = el.data.text;

                        break;
                    case 'table':
                        child = document.createElement('table');
                        el.data.content.forEach((element, n) => {
                            let tr = document.createElement("tr");
                            element.forEach((element1, num) => {
                                let td = document.createElement(n == 0 && el.data.withHeadings ? "th" : "td");
                                td.textContent = element1;
                                tr.appendChild(td);
                            })
                            child.appendChild(tr)
                        })
                        break;
                    case 'code':
                        child = document.createElement('VCodeBlock')
                        // child.props
                        // child = document.createElement('pre')
                        // let code1 = document.createElement('code')
                        // code1.textContent = el.data.code;
                        // child.appendChild(code1)
                        break;
                    // case 'embed':
                    // child = document.createElement('p')
                    // break;
                    default:
                        child = document.createElement('span');
                }
                document.querySelector('#fileParser').append(child)
            });
        }
    },
    data() {
        return {
            fileData: [{
                "id": "_dPTGC-2dC",
                "type": "image",
                "data": {
                    "caption": "123123",
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
            },
            {
                "type": "header",
                "data": {
                    "text": "Why Telegram is the best messenger",
                    "level": 4
                }
            },
            {
                "type": "table",
                "data": {
                    "withHeadings": true,
                    "stretched": false,
                    "content": [["Kine", "Pigs", "Chicken"], ["1 pcs", "3 pcs", "12 pcs"], ["100$", "200$", "150$"]]
                }
            },
            {
                "id": "UidmH8dcer",
                "type": "code",
                "data": {
                    "code": "<?php\nfunction removeSpace(string $str): string {\n    return str_replace(' ', '', $str);\n}\n?>",
                    "language": "php"
                }
            },
            {
                "id": "UidmH8dcer",
                "type": "code",
                "data": {
                    "code": "child = document.createElement('table');\nel.data.content.forEach((element, n) => {\nlet tr = document.createElement('tr');\nelement.forEach((element1, num) => {\nlet td = document.createElement(n == 0 && el.data.withHeadings ? 'th' : 'td');\ntd.textContent = element1;\ntr.appendChild(td);\n})\nchild.appendChild(tr)\n}) ",
                    "language": "javascript"
                }
            },
            {
                "type": "embed",
                "data": {
                    "service": "coub",
                    "source": "https://vk.com/video-223871583_456239712",
                    "embed": "https://vk.com/video_ext.php?oid=-223871583&id=456239712&hash=74a34e322c9352f3",
                    "width": 580,
                    "height": 320,
                    "caption": "My Life"
                }
            }],
        }
    },
}
</script>