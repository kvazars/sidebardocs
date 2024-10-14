<template>
    <Menus />
    <el-container class="layout-container-demo" style="height: 100%">
        <el-aside>
            <el-scrollbar>
                <el-menu>
                    <!--  :default-openeds="['1', '3']" -->
                    <template v-for="(val, key) in readyMenu" :key="key">
                        <el-sub-menu :index="'0-' + key" v-if="val.type == 'folder'">
                            <template #title>
                                <el-icon>
                                    <message />
                                </el-icon><span>{{ val.title }}</span>
                            </template>

                            <template v-for="(val1, key1) in val.child" :key="key1">
                                <el-sub-menu :index="'1-' + key1" v-if="val1.type == 'folder'">
                                    <template #title>
                                        <el-icon>
                                            <message />
                                        </el-icon><span>{{ val1.title }}</span>
                                    </template>

                                    <template v-for="(val2, key2) in val1.child" :key="key2">
                                        <el-sub-menu :index="'2-' + key2" v-if="val2.type == 'folder'">
                                            <template #title>
                                                <el-icon>
                                                    <message />
                                                </el-icon><span>{{ val2.title }}</span>
                                            </template>
                                        </el-sub-menu>
                                        <el-menu-item :index="'22-' + key2" v-if="val2.type == 'document'"><el-icon>
                                                <setting />
                                            </el-icon><span>{{ val2.title }}</span></el-menu-item>

                                    </template>


                                </el-sub-menu>
                                <el-menu-item :index="'11-' + key1" v-if="val1.type == 'document'"><el-icon>
                                        <setting />
                                    </el-icon><span>{{ val1.title }}</span></el-menu-item>

                            </template>


                        </el-sub-menu>
                        <el-menu-item :index="'00' + key" v-if="val.type == 'document'"><el-icon>
                                <setting />
                            </el-icon><span>{{ val.title }}</span></el-menu-item>

                    </template>

                </el-menu>
            </el-scrollbar>
        </el-aside>

        <el-container>
            <el-header style="text-align: right; font-size: 12px">
                <div class="toolbar">
                    <el-dropdown>
                        <el-icon style="margin-right: 8px; margin-top: 1px">
                            <setting />
                        </el-icon>
                        <template #dropdown>
                            <el-dropdown-menu>
                                <el-dropdown-item>View</el-dropdown-item>
                                <el-dropdown-item>Add</el-dropdown-item>
                                <el-dropdown-item>Delete</el-dropdown-item>
                            </el-dropdown-menu>
                        </template>
                    </el-dropdown>
                    <span>Tom</span>
                </div>
            </el-header>

            <el-main>
                <el-scrollbar>
                    <div>
                        <button @click="saveEditor">save Editor</button>
                        <Editor :save="save" ref="Editor" :dataBlock="dataBlock1" />
                    </div>
                </el-scrollbar>
            </el-main>
        </el-container>
    </el-container>
</template>

<script>
import { ref } from "vue";
import { Menu as IconMenu, Message, Setting } from "@element-plus/icons-vue";
import Menus from "@/components/Menu.vue";
import Editor from "@/components/Editor.vue";

export default {
    components: {
        Editor, Menus
    },
    mounted() {
        this.openmenu(JSON.stringify(this.menu[0]));
        //   console.log();
    },
    methods: {
        change() {
            this.dataBlock1 = this.dataBlock2;
        },
        save(data) {
            console.log(data);
        },
        saveEditor() {
            this.$refs.Editor.saveData();
        },
        openmenu(a) {
            let r = JSON.parse(a);
            r = Object.values(r);

            function menucreate(i = 0) {
                let rrr = [];
                r.forEach((e) => {
                    if (e.parentid == i) {
                        e.child = menucreate(e.id);

                        // console.log(e);

                        rrr.push(e);
                    }
                });

                return rrr.sort((f, s) => f.position - s.position);

            }
            this.readyMenu = menucreate();

            console.log(this.readyMenu);
        }
    },
    data() {
        return {
            readyMenu: null,
            menu: [{
                "0": {
                    "id": 29,
                    "title": "Успеваемость",
                    "position": "1",
                    "icon": "edit-3",
                    "parentid": "70",
                    "type": "document",
                    "component": "academicperformance",
                    "created_at": "2020-09-14T13:50:06.000000Z",
                    "updated_at": "2020-11-24T13:15:22.000000Z",
                },
                "1": {
                    "id": 30,
                    "title": "Предварительная успеваемость студентов",
                    "position": null,
                    "icon": "award",
                    "parentid": "80",
                    "type": "folder",
                    "component": "preliminarygrades",
                    "created_at": "2020-09-14T13:50:46.000000Z",
                    "updated_at": "2021-01-18T05:33:39.000000Z",
                    "prop": null
                },
                "2": {
                    "id": 31,
                    "title": "Посещение за месяц",
                    "position": null,
                    "icon": "book-open",
                    "parentid": "80",
                    "type": "folder",
                    "component": "monthlyvisit",
                    "created_at": "2020-09-14T13:53:00.000000Z",
                    "updated_at": "2021-01-18T07:19:29.000000Z",
                    "prop": null
                },
                "3": {
                    "id": 32,
                    "title": "Посещение в период",
                    "position": null,
                    "icon": "server",
                    "parentid": "80",
                    "type": "folder",
                    "component": "visitduringtheperiod",
                    "created_at": "2020-09-14T13:55:01.000000Z",
                    "updated_at": "2021-01-20T09:31:48.000000Z",
                    "prop": null
                },
                "4": {
                    "id": 33,
                    "title": "Информация о студентах",
                    "position": null,
                    "icon": "phone-call",
                    "parentid": "80",
                    "type": "folder",
                    "component": "informationaboutstudents",
                    "created_at": "2020-09-14T13:55:58.000000Z",
                    "updated_at": "2021-01-19T02:25:32.000000Z",
                    "prop": null
                },
                "5": {
                    "id": 35,
                    "title": "Графическое представление успеваемости",
                    "position": null,
                    "icon": "list",
                    "parentid": "80",
                    "type": "folder",
                    "component": "graphicalpresentationofprogress",
                    "created_at": "2020-09-14T13:56:45.000000Z",
                    "updated_at": "2021-02-05T01:14:01.000000Z",
                    "prop": null
                },
                "6": {
                    "id": 36,
                    "title": "Достижения студентов",
                    "position": null,
                    "icon": "airplay",
                    "parentid": "80",
                    "type": "folder",
                    "component": "achievementsofstudents",
                    "created_at": "2020-09-14T13:57:35.000000Z",
                    "updated_at": "2021-01-20T07:47:06.000000Z",
                    "prop": null
                },
                "7": {
                    "id": 37,
                    "title": "Достижение студентов в период",
                    "position": null,
                    "icon": "anchor",
                    "parentid": "80",
                    "type": "folder",
                    "component": "achievementsofstudentsduringtheperiod",
                    "created_at": "2020-09-14T13:58:32.000000Z",
                    "updated_at": "2021-01-20T08:15:35.000000Z",
                    "prop": null
                },
                "8": {
                    "id": 38,
                    "title": "Отчет классного руководителя",
                    "position": null,
                    "icon": "crosshair",
                    "parentid": "80",
                    "type": "folder",
                    "component": "hometeacherreport",
                    "created_at": "2020-09-14T13:59:34.000000Z",
                    "updated_at": "2021-01-21T02:33:51.000000Z",
                    "prop": null
                },
                "9": {
                    "id": 39,
                    "title": "Список группы с паспортными данными",
                    "position": null,
                    "icon": "wifi",
                    "parentid": "80",
                    "type": "folder",
                    "component": "grouplistwithpassportdata",
                    "created_at": "2020-09-14T14:00:18.000000Z",
                    "updated_at": "2021-01-19T07:10:57.000000Z",
                    "prop": null
                },
                "10": {
                    "id": 40,
                    "title": "Отчет по нарушениям",
                    "position": null,
                    "icon": "battery-charging",
                    "parentid": "80",
                    "type": "folder",
                    "component": "violationreport",
                    "created_at": "2020-09-14T14:00:50.000000Z",
                    "updated_at": "2021-01-20T08:33:06.000000Z",
                    "prop": null
                },
                "11": {
                    "id": 41,
                    "title": "Профиль студента",
                    "position": null,
                    "icon": "tool",
                    "parentid": "80",
                    "type": "folder",
                    "component": "studentprofile",
                    "created_at": "2020-09-14T14:01:25.000000Z",
                    "updated_at": "2021-01-19T02:53:58.000000Z",
                    "prop": null
                },
                "12": {
                    "id": 42,
                    "title": "Классное руководство",
                    "position": "2",
                    "icon": "loader",
                    "parentid": "0",
                    "type": "folder",
                    "component": null,
                    "created_at": "2020-09-14T14:02:50.000000Z",
                    "updated_at": "2020-11-27T04:12:13.000000Z",
                    "prop": null
                },
                "13": {
                    "id": 53,
                    "title": "Отчёты преподавателя",
                    "position": "4",
                    "icon": "download-cloud",
                    "parentid": "0",
                    "type": "folder",
                    "component": null,
                    "created_at": "2020-09-14T15:28:10.000000Z",
                    "updated_at": "2020-11-27T04:12:35.000000Z",
                    "prop": null
                },
                "14": {
                    "id": 54,
                    "title": "Средняя успеваемость",
                    "position": null,
                    "icon": "triangle",
                    "parentid": "53",
                    "type": "folder",
                    "component": "averageacademicperformance",
                    "created_at": "2020-09-14T15:28:50.000000Z",
                    "updated_at": "2021-01-12T05:46:44.000000Z",
                    "prop": null
                },
                "15": {
                    "id": 55,
                    "title": "Отчет преподавателя по успеваемости и вычитке",
                    "position": "3",
                    "icon": "pen-tool",
                    "parentid": "53",
                    "type": "folder",
                    "component": "instructorsreportonprogressandproofreading",
                    "created_at": "2020-09-14T15:30:22.000000Z",
                    "updated_at": "2020-11-24T06:27:05.000000Z",
                    "prop": null
                },
                "16": {
                    "id": 56,
                    "title": "Базовое расписание",
                    "position": "1",
                    "icon": "clipboard",
                    "parentid": "53",
                    "type": "folder",
                    "component": "basicschedule",
                    "created_at": "2020-09-14T15:30:59.000000Z",
                    "updated_at": "2020-11-24T03:39:13.000000Z",
                    "prop": null
                },
                "17": {
                    "id": 57,
                    "title": "Прочитанные часы",
                    "position": "2",
                    "icon": "film",
                    "parentid": "53",
                    "type": "folder",
                    "component": "readhours",
                    "created_at": "2020-09-14T15:31:30.000000Z",
                    "updated_at": "2020-11-24T04:17:32.000000Z",
                    "prop": null
                },
                "18": {
                    "id": 58,
                    "title": "Предварительная вычитка",
                    "position": null,
                    "icon": "life-buoy",
                    "parentid": "53",
                    "type": "folder",
                    "component": "preproofreading",
                    "created_at": "2020-09-14T15:32:25.000000Z",
                    "updated_at": "2021-01-12T03:29:08.000000Z",
                    "prop": null
                },
                "19": {
                    "id": 60,
                    "title": "Процент успеваемости",
                    "position": null,
                    "icon": "trello",
                    "parentid": "53",
                    "type": "folder",
                    "component": "percentageofprogress",
                    "created_at": "2020-09-14T15:33:46.000000Z",
                    "updated_at": "2021-01-12T05:13:22.000000Z",
                    "prop": null
                },
                "20": {
                    "id": 61,
                    "title": "Социальный паспорт группы",
                    "position": null,
                    "icon": "terminal",
                    "parentid": "80",
                    "type": "folder",
                    "component": "groupsocialpassport",
                    "created_at": "2020-09-14T15:39:32.000000Z",
                    "updated_at": "2021-01-25T09:24:06.000000Z",
                    "prop": null
                },
                "21": {
                    "id": 62,
                    "title": "Профессионалитет",
                    "position": "6",
                    "icon": "aperture",
                    "parentid": "0",
                    "type": "folder",
                    "component": null,
                    "created_at": "2020-09-14T15:39:50.000000Z",
                    "updated_at": "2020-11-26T04:05:41.000000Z",
                    "prop": null
                },
                "22": {
                    "id": 63,
                    "title": "Сводный отчет Профессионалитет за семестр",
                    "position": null,
                    "icon": "star",
                    "parentid": "62",
                    "type": "folder",
                    "component": "bbmsemestersummaryreport",
                    "created_at": "2020-09-14T15:40:46.000000Z",
                    "updated_at": "2021-01-25T08:48:14.000000Z",
                    "prop": null
                },
                "23": {
                    "id": 65,
                    "title": "Рейтинг Профессионалитет",
                    "position": null,
                    "icon": "target",
                    "parentid": "62",
                    "type": "folder",
                    "component": "bbmrating",
                    "created_at": "2020-09-14T15:42:00.000000Z",
                    "updated_at": "2021-02-05T02:46:24.000000Z",
                    "prop": null
                },
                "24": {
                    "id": 67,
                    "title": "Сводная ведомость по посещаемости профессионалитет",
                    "position": null,
                    "icon": "repeat",
                    "parentid": "80",
                    "type": "folder",
                    "component": "summarysheetofBBMattendance",
                    "created_at": "2020-09-14T15:43:29.000000Z",
                    "updated_at": "2021-01-25T07:32:37.000000Z",
                    "prop": null
                },
                "25": {
                    "id": 68,
                    "title": "Средняя успеваемость в разрезе лет",
                    "position": null,
                    "icon": "percent",
                    "parentid": "62",
                    "type": "folder",
                    "component": "averageacademicperformancebyyears",
                    "created_at": "2020-09-14T15:44:02.000000Z",
                    "updated_at": "2021-01-25T07:20:49.000000Z",
                    "prop": null
                },
                "26": {
                    "id": 69,
                    "title": "Итоговая успеваемость на стипендию",
                    "position": null,
                    "icon": "pocket",
                    "parentid": "62",
                    "type": "folder",
                    "component": "finalscholarshipperformance",
                    "created_at": "2020-09-14T15:44:51.000000Z",
                    "updated_at": "2021-01-25T07:03:07.000000Z",
                    "prop": null
                },
                "27": {
                    "id": 70,
                    "title": "Группы",
                    "position": "1",
                    "icon": "codesandbox",
                    "parentid": "0",
                    "type": "folder",
                    "component": null,
                    "created_at": "2020-11-24T07:12:13.000000Z",
                    "updated_at": "2020-11-24T07:12:13.000000Z",
                    "prop": null
                },
                "28": {
                    "id": 72,
                    "title": "Курсовые работы",
                    "position": "3",
                    "icon": "coffee",
                    "parentid": "70",
                    "type": "folder",
                    "component": "coursework",
                    "created_at": "2020-11-26T04:08:14.000000Z",
                    "updated_at": "2020-11-26T04:08:14.000000Z",
                    "prop": null
                },
                "29": {
                    "id": 73,
                    "title": "Дипломные работы",
                    "position": "4",
                    "icon": "command",
                    "parentid": "70",
                    "type": "folder",
                    "component": "diploms",
                    "created_at": "2020-11-26T04:09:54.000000Z",
                    "updated_at": "2020-11-26T04:09:54.000000Z",
                    "prop": null
                },
                "30": {
                    "id": 74,
                    "title": "Практика",
                    "position": "5",
                    "icon": "speaker",
                    "parentid": "70",
                    "type": "folder",
                    "component": "practicemanagement",
                    "created_at": "2020-11-26T04:11:44.000000Z",
                    "updated_at": "2020-11-26T04:11:44.000000Z",
                    "prop": null
                },
                "31": {
                    "id": 75,
                    "title": "Посещение группы",
                    "position": "1",
                    "icon": "map",
                    "parentid": "42",
                    "type": "folder",
                    "component": "groupvisitklass",
                    "created_at": "2020-11-27T04:17:40.000000Z",
                    "updated_at": "2020-11-27T04:20:36.000000Z",
                    "prop": null
                },
                "32": {
                    "id": 76,
                    "title": "Личные данные студентов",
                    "position": "2",
                    "icon": "twitter",
                    "parentid": "42",
                    "type": "folder",
                    "component": "personaldata",
                    "created_at": "2020-11-28T02:17:41.000000Z",
                    "updated_at": "2020-11-28T02:17:41.000000Z",
                    "prop": null
                },
                "33": {
                    "id": 77,
                    "title": "Расписание занятий",
                    "position": "0",
                    "icon": "calendar",
                    "parentid": "0",
                    "type": "document",
                    "component": "raspisanie",
                    "created_at": "2020-12-15T02:33:07.000000Z",
                    "updated_at": "2020-12-15T02:33:07.000000Z",
                    "prop": null
                },
                "34": {
                    "id": 78,
                    "title": "План-сетка",
                    "position": "1",
                    "icon": "layout",
                    "parentid": "79",
                    "type": "folder",
                    "component": "plansetka",
                    "created_at": "2020-12-16T05:40:53.000000Z",
                    "updated_at": "2020-12-16T05:41:44.000000Z",
                    "prop": null
                },
                "35": {
                    "id": 79,
                    "title": "Отчёты",
                    "position": "8",
                    "icon": "layers",
                    "parentid": "0",
                    "type": "folder",
                    "component": null,
                    "created_at": "2020-12-16T05:41:17.000000Z",
                    "updated_at": "2020-12-16T05:43:17.000000Z",
                    "prop": null
                },
                "36": {
                    "id": 80,
                    "title": "Отчёты классного руководителя",
                    "position": "5",
                    "icon": "tv",
                    "parentid": "0",
                    "type": "folder",
                    "component": null,
                    "created_at": "2020-12-16T10:49:46.000000Z",
                    "updated_at": "2020-12-16T10:50:11.000000Z",
                    "prop": null
                },
                "37": {
                    "id": 81,
                    "title": "Успеваемость студентов",
                    "position": null,
                    "icon": "dribbble",
                    "parentid": "80",
                    "type": "folder",
                    "component": "academicperformance3",
                    "created_at": "2020-12-16T10:52:02.000000Z",
                    "updated_at": "2020-12-16T10:58:16.000000Z",
                    "prop": null
                },
                "600": {
                    "id": 800,
                    "title": "Успеваемость студентов800",
                    "position": null,
                    "icon": "dribbble",
                    "parentid": "81",
                    "type": "folder",
                    "component": "academicperformance3",
                    "created_at": "2020-12-16T10:52:02.000000Z",
                    "updated_at": "2020-12-16T10:58:16.000000Z",
                    "prop": null
                },
                "38": {
                    "id": 82,
                    "title": "Управление",
                    "position": "3",
                    "icon": "monitor",
                    "parentid": "0",
                    "type": "folder",
                    "component": null,
                    "created_at": "2020-12-18T05:31:52.000000Z",
                    "updated_at": "2020-12-18T05:31:52.000000Z",
                    "prop": null
                },
                "39": {
                    "id": 83,
                    "title": "Мои достижения",
                    "position": null,
                    "icon": "shopping-bag",
                    "parentid": "82",
                    "type": "folder",
                    "component": "mydostig",
                    "created_at": "2020-12-18T05:35:41.000000Z",
                    "updated_at": "2020-12-18T05:35:41.000000Z",
                    "prop": null
                },
                "40": {
                    "id": 84,
                    "title": "Рейтинг студентов Профессионалитет",
                    "position": null,
                    "icon": "shuffle",
                    "parentid": "62",
                    "type": "folder",
                    "component": "reitstudbbm",
                    "created_at": "2021-01-11T09:07:56.000000Z",
                    "updated_at": "2021-01-11T09:07:56.000000Z",
                    "prop": null
                },
                "41": {
                    "id": 85,
                    "title": "Нарушения студентов",
                    "position": null,
                    "icon": "umbrella",
                    "parentid": "42",
                    "type": "folder",
                    "component": "violationstudents",
                    "created_at": "2021-01-12T07:30:56.000000Z",
                    "updated_at": "2021-01-12T07:30:56.000000Z",
                    "prop": null
                },
                "42": {
                    "id": 86,
                    "title": "Отчеты администратора",
                    "position": "10",
                    "icon": "printer",
                    "parentid": "0",
                    "type": "folder",
                    "component": null,
                    "created_at": "2021-01-12T11:43:00.000000Z",
                    "updated_at": "2021-01-12T11:43:00.000000Z",
                    "prop": null
                },
                "43": {
                    "id": 87,
                    "title": "Прочитанные часы преподавателями",
                    "position": null,
                    "icon": "rss",
                    "parentid": "86",
                    "type": "folder",
                    "component": "readhours_admin",
                    "created_at": "2021-01-12T11:45:21.000000Z",
                    "updated_at": "2021-01-12T11:45:21.000000Z",
                    "prop": null
                },
                "44": {
                    "id": 88,
                    "title": "Отчет по классным руководителям индивидуально",
                    "position": null,
                    "icon": "hexagon",
                    "parentid": "86",
                    "type": "folder",
                    "component": "reportklassrukind",
                    "created_at": "2021-01-21T10:28:56.000000Z",
                    "updated_at": "2021-01-21T10:28:56.000000Z",
                    "prop": null
                },
                "45": {
                    "id": 89,
                    "title": "Посещение-Оценки в период",
                    "position": null,
                    "icon": "paperclip",
                    "parentid": "86",
                    "type": "folder",
                    "component": "posuspevforperiod",
                    "created_at": "2021-01-22T02:42:57.000000Z",
                    "updated_at": "2021-01-22T02:42:57.000000Z",
                    "prop": null
                },
                "46": {
                    "id": 90,
                    "title": "Сводная ведомость промежуточной аттестации",
                    "position": null,
                    "icon": "instagram",
                    "parentid": "86",
                    "type": "folder",
                    "component": "svodvedprmatt",
                    "created_at": "2021-01-25T04:30:46.000000Z",
                    "updated_at": "2021-01-25T04:30:46.000000Z",
                    "prop": null
                },
                "47": {
                    "id": 91,
                    "title": "Отчет по классным руководителям",
                    "position": null,
                    "icon": "inbox",
                    "parentid": "86",
                    "type": "folder",
                    "component": "reportklassrukindall",
                    "created_at": "2021-01-25T05:09:56.000000Z",
                    "updated_at": "2021-01-25T05:09:56.000000Z",
                    "prop": null
                },
                "48": {
                    "id": 93,
                    "title": "Отчеты по преподавателям по успеваемости и вычитке",
                    "position": null,
                    "icon": "gitlab",
                    "parentid": "86",
                    "type": "folder",
                    "component": "instructorsreportonprogressandproofreadingadmin",
                    "created_at": "2021-01-25T06:26:01.000000Z",
                    "updated_at": "2021-01-25T06:26:01.000000Z",
                    "prop": null
                },
                "49": {
                    "id": 94,
                    "title": "Участия всех студентов в мероприятиях",
                    "position": null,
                    "icon": "wind",
                    "parentid": "86",
                    "type": "folder",
                    "component": "uchastrepotrall",
                    "created_at": "2021-01-25T10:51:17.000000Z",
                    "updated_at": "2021-01-25T10:51:17.000000Z",
                    "prop": null
                },
                "50": {
                    "id": 95,
                    "title": "Проектная деятельность преподавателя",
                    "position": null,
                    "icon": "figma",
                    "parentid": "82",
                    "type": "folder",
                    "component": "projectmanagement",
                    "created_at": "2021-01-28T05:19:23.000000Z",
                    "updated_at": "2021-01-28T05:19:23.000000Z",
                    "prop": null
                },
                "51": {
                    "id": 96,
                    "title": "Планирование",
                    "position": null,
                    "icon": "underline",
                    "parentid": "82",
                    "type": "folder",
                    "component": "planning",
                    "created_at": "2021-02-01T03:00:00.000000Z",
                    "updated_at": "2021-02-01T03:00:00.000000Z",
                    "prop": null
                },
                "52": {
                    "id": 97,
                    "title": "ГТО",
                    "position": null,
                    "icon": "meh",
                    "parentid": "82",
                    "type": "folder",
                    "component": "gto",
                    "created_at": "2021-02-04T11:19:17.000000Z",
                    "updated_at": "2021-02-04T11:19:17.000000Z",
                    "prop": null
                },
                "53": {
                    "id": 98,
                    "title": "Сводная ведомость вычитанных часов",
                    "position": null,
                    "icon": "circle",
                    "parentid": "86",
                    "type": "folder",
                    "component": "svodallreport",
                    "created_at": "2021-02-04T12:27:47.000000Z",
                    "updated_at": "2021-02-04T12:27:47.000000Z",
                    "prop": null
                },
                "54": {
                    "id": 99,
                    "title": "Средняя успеваемость преподавателей",
                    "position": null,
                    "icon": "menu",
                    "parentid": "86",
                    "type": "folder",
                    "component": "avgrepoduspev",
                    "created_at": "2021-02-05T02:02:04.000000Z",
                    "updated_at": "2021-02-05T02:02:04.000000Z",
                    "prop": null
                },
                "55": {
                    "id": 100,
                    "title": "Сводная информация по результатам посещаемости",
                    "position": null,
                    "icon": "pie-chart",
                    "parentid": "86",
                    "type": "folder",
                    "component": "svodinforesultpos",
                    "created_at": "2021-02-05T02:13:16.000000Z",
                    "updated_at": "2021-02-05T02:13:16.000000Z",
                    "prop": null
                },
                "56": {
                    "id": 101,
                    "title": "Аналитическая таблица по приёму",
                    "position": null,
                    "icon": "thermometer",
                    "parentid": "79",
                    "type": "folder",
                    "component": "analitable",
                    "created_at": "2021-02-05T03:23:49.000000Z",
                    "updated_at": "2021-02-05T03:24:13.000000Z",
                    "prop": null
                },
                "57": {
                    "id": 103,
                    "title": "КТП",
                    "position": null,
                    "icon": "book-open",
                    "parentid": "82",
                    "type": "folder",
                    "component": "ktp",
                    "created_at": "2022-08-16T04:53:22.000000Z",
                    "updated_at": "2022-08-16T04:53:22.000000Z",
                    "prop": null
                },
                "58": {
                    "id": 105,
                    "title": "Все отчёты администратора",
                    "position": "20",
                    "icon": "book",
                    "parentid": "86",
                    "type": "folder",
                    "component": "reportadmin",
                    "created_at": "2023-09-20T02:58:57.000000Z",
                    "updated_at": "2023-09-20T02:58:57.000000Z",
                    "prop": null
                },
                "59": {
                    "id": 108,
                    "title": "Сводная ведомость по семестрам",
                    "position": null,
                    "icon": "book",
                    "parentid": "80",
                    "type": "folder",
                    "component": "reportsem",
                    "created_at": "2024-01-12T02:34:50.000000Z",
                    "updated_at": "2024-01-12T08:39:55.000000Z",
                    "prop": null
                }
            }],
            tree: [
                {
                    course1: [
                        {
                            css: [
                                {
                                    name: "ABCDE",
                                    icon: "omg",
                                    parent_id: 2,
                                },
                            ],
                            html: [],
                        },
                    ],
                },
            ],
            dataBlock1: [
                {
                    id: "uqvFlzwn4H",
                    type: "paragraph",
                    data: {
                        text: "ewf",
                    },
                },
                {
                    id: "zRhbMyXyfh",
                    type: "paragraph",
                    data: {
                        text: "wefwef",
                    },
                },
                {
                    id: "8oPZtnSaDA",
                    type: "paragraph",
                    data: {
                        text: "wefwefwe",
                    },
                },
                {
                    id: "_dPTGC-2dC",
                    type: "image",
                    data: {
                        caption: "",
                        withBorder: false,
                        withBackground: false,
                        stretched: false,
                        file: {
                            url: "http://localhost:8000/public/contentImages/VmbQj6R78KqFUEnN5yvqOj4j7tE4JUgW8jfO2Wtl.jpg",
                        },
                    },
                },
                {
                    id: "ruK35tRzGR",
                    type: "list",
                    data: {
                        style: "unordered",
                        items: ["wqd", "qwd"],
                    },
                },
                {
                    id: "RXIyHESczT",
                    type: "paragraph",
                    data: {
                        text: "qwd",
                    },
                },
            ],
            dataBlock2: [
                {
                    id: "_dPTGC-2dC",
                    type: "image",
                    data: {
                        caption: "",
                        withBorder: false,
                        withBackground: false,
                        stretched: false,
                        file: {
                            url: "http://localhost:8000/public/contentImages/VmbQj6R78KqFUEnN5yvqOj4j7tE4JUgW8jfO2Wtl.jpg",
                        },
                    },
                },
            ],
        };
    },
};

const item = {
    date: "2016-05-02",
    name: "Tom",
    address: "No. 189, Grove St, Los Angeles",
};
const tableData = ref(Array.from({ length: 20 }).fill(item));
</script>

<style scoped>
.layout-container-demo .el-header {
    position: relative;
    background-color: var(--el-color-primary-light-7);
    color: var(--el-text-color-primary);
}

.layout-container-demo .el-aside {
    color: var(--el-text-color-primary);
    background: var(--el-color-primary-light-8);
}

.layout-container-demo .el-menu {
    border-right: none;
}

.layout-container-demo .el-main {
    padding: 0;
}

.layout-container-demo .toolbar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    right: 20px;
}
</style>

<!-- 
<ul class="metismenu" id="menu">



    <template v-for="(p, i) in menu">
      <li :key="i" v-if="p.child.length">
        <a class="has-arrow" :class="{ disabled: !load }" href="#" aria-expanded="false"
          :aria-controls="'lgfu' + i">
          <vue-feather :type="p.icon"></vue-feather>
          <span class="nav-text">{{ p.title }}</span>
        </a>
        <ul aria-expanded="false">
          <li v-for="(e, key) in p.child" :key="key" :class="{ 'mm-active mm-active1': component == e.component }">
            <a :class="{ 'mm-active mm-active1': component == e.component }" href="#" @click="
          changecontentall(e.component, e.title, e.prop, p.title)
          " :data-content="e.component">
              <vue-feather :type="e.icon"></vue-feather>
              {{ e.title }}
            </a>
          </li>
        </ul>
      </li>
      <template v-else>
        <li class="nav-item" v-if="p.component != 'admin'" :class="{
          'mm-active mm-active1': component == p.component,
        }" :key="p.component">
          <a :class="{ 'mm-active nolink mm-active1': component == p.component }" href="#"
            @click="changecontentall(p.component, p.title, p.prop)" :data-content="p.component">
            <vue-feather :type="p.icon"></vue-feather>

            <span class="nav-text">{{ p.title }} </span>
          </a>
        </li>
      </template>
    </template>
  </ul> -->