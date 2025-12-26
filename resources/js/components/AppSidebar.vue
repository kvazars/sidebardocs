<script>
import SidebarNav from "../components/SidebarNav.vue";
import { useSidebarStore } from "../stores/sidebar.js";
import ContextMenu from "../components/ContextMenu.vue";
import { useAuthIdStore } from "../stores/authId";

export default {
    components: { SidebarNav, ContextMenu },
    props: [
        "menu",
        "datasend",
        "getMenu",
        "showToast",
        "catchError",
        "server",
        "about",
    ],

    data() {
        return {
            folderTitle: "",
            auths: useAuthIdStore(),
            contextMenuActionsFolder: [
                {
                    label: "Переименовать",
                    action: "editFolder",
                    icon: "pencil",
                },
                {
                    label: "Создать дочернюю папку",
                    action: "newFolder",
                    icon: "folder2  -open",
                },
                {
                    label: "Переместить ниже",
                    action: "updoc",
                    icon: "arrow-bar-down",
                },
                { label: "Выше", action: "downdoc", icon: "arrow-bar-up" },
                {
                    label: "Создать ресурс",
                    action: "newFile",
                    icon: "file-text",
                },
                {
                    label: "Удалить папку",
                    action: "deleteFolder",
                    icon: "trash",
                },
            ],
            contextMenuActionsFile: [
                { label: "Редактировать", action: "editFile", icon: "pencil" },
                {
                    label: "Изменить папку",
                    action: "changeFolder",
                    icon: "folder",
                },
                { label: "Ниже", action: "updoc", icon: "arrow-bar-down" },
                { label: "Выше", action: "downdoc", icon: "arrow-bar-up" },
                { label: "Удалить", action: "deleteFile", icon: "trash" },
            ],
            visibleModalFolder: false,
            visibleModalChangeFolder: false,
            sidebar: useSidebarStore(),

            showMenu: false,
            folderId: null,
            treeId: null,
            treeUserId: null,
            treeType: null,
            treeName: null,
            folderParent: null,
            menuX: null,
            menuY: null,
            logo: null,
            selectedFileId: null,
            selectedFileName: null,
            selectedNewFolderId: null,
            selectedFileCurrentFolderId: null,
            folderOptions: [],
            expandedFolders: new Set(),
        };
    },
    mounted() {
        if (this.about) this.logo = this.server + "/" + this.about.logo;
    },
    watch: {
        menu: {
            handler() {
                this.prepareFolderOptions();
            },
            deep: true,
        },
    },
    methods: {
        closeContextMenu() {
            this.showMenu = false;
        },
        save() {
            let form = { name: this.folderTitle };

            if (this.folderParent) {
                form.tree_id = this.folderParent;
            } else {
                form.id = this.folderId;
            }

            this.datasend("folder", "POST", form)
                .then((res) => {
                    if (res.success) {
                        this.getMenu();
                        this.folderTitle = "";
                        this.folderId = "";
                        this.folderParent = "";
                        this.visibleModalFolder = false;
                        this.showToast(res.message, res.success);
                    } else if (res.errors) {
                        this.catchError(res.errors);
                    }
                })
                .catch((error) => {
                    console.log(error);
                });
        },

        // Подготовка списка папок для выбора
        prepareFolderOptions() {
            this.folderOptions = this.extractAllFoldersFromStructure(this.menu);
        },

        // Универсальный метод извлечения ВСЕХ папок из ЛЮБОЙ структуры
        extractAllFoldersFromStructure(data, level = 0, path = "") {
            const folders = [];

            if (!data) return folders;

            // Если это массив
            if (Array.isArray(data)) {
                data.forEach((item, index) => {
                    const newPath = `${path}[${index}]`;
                    const childFolders = this.extractAllFoldersFromStructure(
                        item,
                        level,
                        newPath
                    );
                    folders.push(...childFolders);
                });
            }
            // Если это объект
            else if (typeof data === "object" && data !== null) {
                // Проверяем, является ли этот объект папкой
                const isFolder = this.isFolderObject(data);
                if (isFolder) {
                    folders.push({
                        id: data.id,
                        name:
                            "  ".repeat(level) +
                            (data.name || data.title || `Папка ${data.id}`),
                        originalName:
                            data.name || data.title || `Папка ${data.id}`,
                        level: level,
                        path: path,
                        rawData: data, // Сохраняем исходные данные для отладки
                    });
                }

                // Рекурсивно обходим все свойства объекта
                for (const key in data) {
                    if (data.hasOwnProperty(key) && key !== "rawData") {
                        const newPath = `${path}.${key}`;
                        const childFolders =
                            this.extractAllFoldersFromStructure(
                                data[key],
                                isFolder ? level + 1 : level,
                                newPath
                            );
                        folders.push(...childFolders);
                    }
                }
            }

            return folders;
        },

        // Определение, является ли объект папкой
        isFolderObject(obj) {
            if (!obj || typeof obj !== "object") return false;

            // Критерии для определения папки:
            // 1. Есть id и (name или title)
            // 2. type === 'folder' ИЛИ нет type (но есть children/items)
            const hasId = obj.id !== undefined && obj.id !== null;
            const hasName = obj.name !== undefined || obj.title !== undefined;
            const isFolderType = obj.type === "folder";
            const hasChildren =
                obj.children !== undefined || obj.items !== undefined;

            return hasId && (isFolderType || (hasName && hasChildren));
        },

        // Универсальный метод поиска родительской папки файла
        findParentFolderForFile(fileId, data, currentParent = null, path = "") {
            if (!data) return null;

            // Если это массив
            if (Array.isArray(data)) {
                for (let i = 0; i < data.length; i++) {
                    const item = data[i];
                    const newPath = `${path}[${i}]`;
                    const result = this.findParentFolderForFile(
                        fileId,
                        item,
                        currentParent,
                        newPath
                    );
                    if (result !== null) {
                        return result;
                    }
                }
            }
            // Если это объект
            else if (typeof data === "object" && data !== null) {
                // Проверяем, является ли этот объект нашим файлом
                const isTargetFile =
                    data.id === fileId &&
                    (data.type === "file" ||
                        data.type === "document" ||
                        data.resource_type === "file" ||
                        !this.isFolderObject(data));

                if (isTargetFile) {
                    return currentParent;
                }

                // Определяем, является ли текущий объект папкой
                const isFolder = this.isFolderObject(data);
                const newParent = isFolder ? data.id : currentParent;

                // Рекурсивно ищем в дочерних свойствах
                for (const key in data) {
                    if (
                        data.hasOwnProperty(key) &&
                        key !== "id" &&
                        key !== "rawData"
                    ) {
                        const newPath = `${path}.${key}`;
                        const result = this.findParentFolderForFile(
                            fileId,
                            data[key],
                            newParent,
                            newPath
                        );
                        if (result !== null) {
                            return result;
                        }
                    }
                }
            }

            return null;
        },

        // Альтернативный метод: поиск через глубокое клонирование и анализ
        findParentFolderDeep(fileId) {
            const menuCopy = JSON.parse(JSON.stringify(this.menu));
            const findParentRecursive = (data, parentId = null) => {
                if (!data) return null;

                if (Array.isArray(data)) {
                    for (const item of data) {
                        const result = findParentRecursive(item, parentId);
                        if (result !== null) return result;
                    }
                } else if (typeof data === "object") {
                    const isFile =
                        data.id === fileId &&
                        (data.type === "file" ||
                            data.type === "document" ||
                            data.resource_type === "file" ||
                            (!data.type && !this.isFolderObject(data)));

                    if (isFile) {
                        return parentId;
                    }

                    const isFolder = this.isFolderObject(data);
                    const newParentId = isFolder ? data.id : parentId;

                    for (const key in data) {
                        if (
                            key !== "id" &&
                            key !== "type" &&
                            key !== "name" &&
                            key !== "title"
                        ) {
                            const result = findParentRecursive(
                                data[key],
                                newParentId
                            );
                            if (result !== null) return result;
                        }
                    }
                }

                return null;
            };

            const result = findParentRecursive(menuCopy);
            return result;
        },

        // Улучшенный метод поиска с полной отладкой
        findFileParentWithDebug(fileId) {
            const method1Result = this.findParentFolderForFile(
                fileId,
                this.menu
            );

            const method2Result = this.findParentFolderDeep(fileId);
            const fileData = this.findFileData(fileId);

            this.analyzeMenuStructure();

            if (fileData && fileData.tree_id) {
                return fileData.tree_id;
            }

            return method2Result !== null ? method2Result : method1Result;
        },

        // Поиск данных файла в структуре
        findFileData(fileId) {
            const findRecursive = (data) => {
                if (!data) return null;

                if (Array.isArray(data)) {
                    for (const item of data) {
                        const result = findRecursive(item);
                        if (result) return result;
                    }
                } else if (typeof data === "object") {
                    if (data.id === fileId) {
                        return data;
                    }

                    for (const key in data) {
                        if (data.hasOwnProperty(key)) {
                            const result = findRecursive(data[key]);
                            if (result) return result;
                        }
                    }
                }

                return null;
            };

            return findRecursive(this.menu);
        },

        // Анализ структуры меню для понимания
        analyzeMenuStructure() {
            const stats = {
                totalItems: 0,
                folders: 0,
                files: 0,
                unknown: 0,
                maxDepth: 0,
            };

            const analyze = (data, depth = 0, parentType = null) => {
                if (!data) return;

                stats.totalItems++;
                stats.maxDepth = Math.max(stats.maxDepth, depth);

                if (Array.isArray(data)) {
                    data.forEach((item) => analyze(item, depth, parentType));
                } else if (typeof data === "object") {
                    // Определяем тип элемента
                    if (this.isFolderObject(data)) {
                        stats.folders++;
                    } else if (
                        data.id &&
                        (data.type === "file" || data.type === "document")
                    ) {
                        stats.files++;
                    } else if (data.id) {
                        stats.unknown++;
                    }

                    // Рекурсивно анализируем свойства
                    for (const key in data) {
                        if (data.hasOwnProperty(key)) {
                            analyze(
                                data[key],
                                depth + 1,
                                data.type || parentType
                            );
                        }
                    }
                }
            };

            analyze(this.menu);

            return stats;
        },

        // Переключение видимости подпапок
        toggleFolderExpansion(folderId) {
            if (this.expandedFolders.has(folderId)) {
                this.expandedFolders.delete(folderId);
            } else {
                this.expandedFolders.add(folderId);
            }
        },

        // Выбор папки
        selectFolder(folderId) {
            this.selectedNewFolderId = folderId;
        },

        // Сохранение изменения папки
        saveFolderChange() {
            if (!this.selectedFileId || !this.selectedNewFolderId) {
                this.showToast("Выберите папку", "warning");
                return;
            }

            // Если не удалось определить текущую папку, все равно разрешаем перемещение
            // но с предупреждением
            if (this.selectedNewFolderId === this.selectedFileCurrentFolderId) {
                this.showToast("Файл уже находится в этой папке", "warning");
                return;
            }

            const form = {
                tree_id: this.selectedNewFolderId,
            };

            // Информация для подтверждения
            let confirmationMessage = `Переместить файл "${this.selectedFileName}"?`;

            if (this.selectedFileCurrentFolderId !== null) {
                const currentFolder = this.folderOptions.find(
                    (f) => f.id === this.selectedFileCurrentFolderId
                );
                const newFolder = this.folderOptions.find(
                    (f) => f.id === this.selectedNewFolderId
                );

                if (currentFolder && newFolder) {
                    confirmationMessage = `Переместить файл "${this.selectedFileName}" из папки "${currentFolder.originalName}" в папку "${newFolder.originalName}"?`;
                }
            }

            if (!confirm(confirmationMessage)) {
                return;
            }

            // console.log("Отправляем запрос на изменение папки:", {
            //     fileId: this.selectedFileId,
            //     newFolderId: this.selectedNewFolderId,
            //     currentFolderId: this.selectedFileCurrentFolderId,
            // });

            this.datasend(
                `file/${this.selectedFileId}/change-folder`,
                "POST",
                form
            )
                .then((res) => {
                    if (res.success) {
                        this.getMenu();
                        this.visibleModalChangeFolder = false;
                        this.selectedFileId = null;
                        this.selectedNewFolderId = null;
                        this.selectedFileCurrentFolderId = null;
                        this.showToast(res.message, res.success);
                        this.closeContextMenu();
                    } else if (res.errors) {
                        this.catchError(res.errors);
                    }
                })
                .catch((error) => {
                    console.log(error);
                    this.showToast("Ошибка при изменении папки", "warning");
                });
        },

        // Открытие модального окна для изменения папки
        changeFolder(id, name) {
            this.selectedFileId = id;
            this.selectedFileName = name;
            this.selectedNewFolderId = null;
            this.selectedFileCurrentFolderId = null;
            this.visibleModalChangeFolder = true;

            this.folderOptions = this.extractAllFoldersFromStructure(this.menu);

            this.selectedFileCurrentFolderId = this.findFileParentWithDebug(id);

            if (this.selectedFileCurrentFolderId !== null) {
                const currentFolder = this.folderOptions.find(
                    (f) => f.id === this.selectedFileCurrentFolderId
                );
            }
        },

        newFolder(id) {
            this.visibleModalFolder = true;
            this.folderTitle = "";
            this.folderParent = id;
            this.folderId = null;
        },
        newFile(id) {
            this.$router.push({ name: "CreateFile", params: { parent: id } });
        },
        updoc(id) {
            this.datasend("doc/up/" + id, "POST", {})
                .then((res) => {
                    this.showToast(res.message, res.success);
                    this.getMenu();
                })
                .catch((error) => console.log(error));
        },
        downdoc(id) {
            this.datasend("doc/down/" + id, "POST", {})
                .then((res) => {
                    this.showToast(res.message, res.success);
                    this.getMenu();
                })
                .catch((error) => console.log(error));
        },
        deleteFolder(id) {
            if (confirm("Вы уверены?")) {
                this.datasend("folder/" + id, "DELETE", {})
                    .then((res) => {
                        this.showToast(res.message, res.success);
                        this.getMenu();
                    })
                    .catch((error) => console.log(error));
            }
        },
        showContextMenu(event, item) {
            this.treeId = item.id;
            this.treeType = item.type;
            this.treeName = item.name;
            this.treeUserId = item.user_id;
            this.showMenu = true;
            this.menuX = event.clientX;
            this.menuY = event.clientY;
            if (item.type === "file") {
                if (item.tree_id) {
                    this.selectedFileCurrentFolderId = item.tree_id;
                }
            }
        },
        editFolder(id, name) {
            this.visibleModalFolder = true;
            this.folderTitle = name;
            this.folderId = id;
            this.folderParent = "";
        },
        handleActionClick(action) {
            this.closeContextMenu();

            if (action == "editFolder") {
                this.editFolder(this.treeId, this.treeName);
            } else if (action == "newFolder") {
                this.newFolder(this.treeId);
            } else if (action == "newFile") {
                this.newFile(this.treeId);
            } else if (action == "deleteFolder") {
                this.deleteFolder(this.treeId);
            } else if (action == "updoc") {
                this.updoc(this.treeId);
            } else if (action == "downdoc") {
                this.downdoc(this.treeId);
            } else if (action == "editFile") {
                this.$router.push({
                    name: "EditFile",
                    params: { id: this.treeId },
                });
            } else if (action == "changeFolder") {
                this.changeFolder(this.treeId, this.treeName);
            } else if (action == "deleteFile") {
                if (confirm("Вы уверены?")) {
                    this.datasend("resourcedel/" + this.treeId, "DELETE", {})
                        .then((res) => {
                            if (res.success) {
                                this.getMenu();
                                this.showToast(res.message, res.success);
                                this.$router.push({ name: "Home" });
                            }
                        })
                        .catch((error) => console.log(error));
                }
            }
        },
        addFirstLevel() {
            this.visibleModalFolder = true;
            this.folderTitle = "";
            this.folderId = null;
            this.folderParent = "new";
        },
    },
};
</script>

<template>
    <CSidebar
        class="border-end"
        colorScheme="light"
        position="fixed"
        :unfoldable="sidebar.unfoldable"
        :visible="sidebar.visible"
        @visible-change="(value) => sidebar.toggleVisible(value)"
    >
        <CSidebarHeader class="border-bottom">
            <RouterLink custom to="/" v-slot="{ href, navigate }">
                <CSidebarBrand
                    v-bind="$attrs"
                    class="w-100"
                    as="a"
                    :href="href"
                    @click="navigate"
                >
                    <img
                        class="w-100 object-fit-contain"
                        :src="logo"
                        alt=""
                        v-if="logo"
                    />
                </CSidebarBrand>
            </RouterLink>
            <CCloseButton
                class="d-lg-none"
                dark
                @click="sidebar.toggleVisible()"
            />
        </CSidebarHeader>
        <SidebarNav :showContextMenu="showContextMenu" :menu="menu" />

        <button
            class="btn btn btn-light p-0"
            @click="addFirstLevel"
            v-if="auths.role == 'ceo' || auths.role == 'admin'"
        >
            <i class="bi bi-plus fs-4" aria-hidden="true"></i>
        </button>
        <CSidebarFooter class="border-top d-none d-lg-flex">
            <CSidebarToggler @click="sidebar.toggleUnfoldable()" />
        </CSidebarFooter>

        <!-- Модальное окно для создания/редактирования папки -->
        <CModal
            :visible="visibleModalFolder"
            @close="
                () => {
                    visibleModalFolder = false;
                }
            "
            aria-labelledby="FolderLabel"
        >
            <CModalHeader>
                <CModalTitle id="FolderLabel">
                    {{ folderId ? "Редактирование папки" : "Новая папка" }}
                </CModalTitle>
            </CModalHeader>
            <CModalBody>
                <div class="w-100 d-flex flex-column gap-4">
                    <div class="w-100 d-flex flex-column gap-2">
                        <CFormInput
                            v-model="folderTitle"
                            v-on:keyup.enter="
                                () => {
                                    save();
                                }
                            "
                            name="folderName"
                            type="text"
                            placeholder="Новое имя папки"
                        />
                    </div>
                </div>
            </CModalBody>
            <CModalFooter>
                <CButton
                    color="secondary"
                    @click="
                        () => {
                            visibleModalFolder = false;
                        }
                    "
                >
                    Отмена
                </CButton>
                <CButton
                    color="primary"
                    @click="
                        () => {
                            save();
                        }
                    "
                    >Сохранить</CButton
                >
            </CModalFooter>
        </CModal>

        <!-- Модальное окно для изменения родительской папки файла -->
        <CModal
            :visible="visibleModalChangeFolder"
            @close="
                () => {
                    visibleModalChangeFolder = false;
                    selectedNewFolderId = null;
                    selectedFileCurrentFolderId = null;
                }
            "
            aria-labelledby="ChangeFolderLabel"
            size="lg"
        >
            <CModalHeader>
                <CModalTitle id="ChangeFolderLabel">
                    Изменение папки для файла
                </CModalTitle>
            </CModalHeader>
            <CModalBody>
                <div class="w-100 d-flex flex-column gap-4">
                    <div class="alert alert-info">
                        <strong>Файл:</strong> {{ selectedFileName }}
                        <div v-if="selectedFileCurrentFolderId !== null">
                            <small class="text-muted">
                                Текущая папка:
                                {{
                                    folderOptions.find(
                                        (f) =>
                                            f.id === selectedFileCurrentFolderId
                                    )?.originalName || "неизвестно"
                                }}
                            </small>
                        </div>
                        <div v-else>
                            <small class="text-warning">
                                Текущая папка не определена. Вы можете выбрать
                                любую папку для перемещения.
                            </small>
                        </div>
                    </div>

                    <div class="folder-selector">
                        <h6>Выберите новую папку:</h6>
                        <div
                            class="folder-list border rounded p-3"
                            style="max-height: 300px; overflow-y: auto"
                        >
                            <div
                                v-for="folder in folderOptions"
                                :key="folder.id"
                                class="folder-item py-2 px-3 border-bottom cursor-pointer"
                                :class="{
                                    'bg-primary text-white':
                                        selectedNewFolderId === folder.id,
                                    'disabled-item':
                                        folder.id ===
                                        selectedFileCurrentFolderId,
                                }"
                                @click="
                                    folder.id !== selectedFileCurrentFolderId &&
                                        selectFolder(folder.id)
                                "
                                :style="{
                                    paddingLeft:
                                        folder.level * 20 +
                                        10 +
                                        'px !important',
                                }"
                                :title="
                                    folder.id === selectedFileCurrentFolderId
                                        ? 'Файл уже находится в этой папке'
                                        : ''
                                "
                            >
                                <div class="d-flex align-items-center">
                                    <i
                                        class="bi me-2"
                                        :class="
                                            folder.id ===
                                            selectedFileCurrentFolderId
                                                ? 'bi-folder-check text-success'
                                                : 'bi-folder'
                                        "
                                    ></i>
                                    {{ folder.name }}
                                    <span
                                        v-if="
                                            folder.id ===
                                            selectedFileCurrentFolderId
                                        "
                                        class="badge bg-secondary ms-2"
                                        >текущая</span
                                    >
                                    <small
                                        v-else-if="folder.level > 0"
                                        class="text-muted ms-2"
                                        >(уровень {{ folder.level }})</small
                                    >
                                </div>
                            </div>
                            <div
                                v-if="folderOptions.length === 0"
                                class="text-muted text-center py-3"
                            >
                                Нет доступных папок
                            </div>
                        </div>
                    </div>

                    <div
                        class="selected-folder-info alert alert-success"
                        v-if="selectedNewFolderId"
                    >
                        <strong>Выбрана папка:</strong>
                        {{
                            folderOptions.find(
                                (f) => f.id === selectedNewFolderId
                            )?.originalName
                        }}
                    </div>

                    <div
                        class="alert alert-warning"
                        v-if="
                            selectedNewFolderId === selectedFileCurrentFolderId
                        "
                    >
                        <small
                            >Выбрана текущая папка файла. Выберите другую папку
                            для перемещения.</small
                        >
                    </div>
                </div>
            </CModalBody>
            <CModalFooter>
                <CButton
                    color="secondary"
                    @click="
                        () => {
                            visibleModalChangeFolder = false;
                            selectedNewFolderId = null;
                            selectedFileCurrentFolderId = null;
                        }
                    "
                >
                    Отмена
                </CButton>
                <CButton
                    color="primary"
                    @click="saveFolderChange"
                    :disabled="
                        !selectedNewFolderId ||
                        selectedNewFolderId === selectedFileCurrentFolderId
                    "
                >
                    Сохранить
                </CButton>
            </CModalFooter>
        </CModal>

        <ContextMenu
            v-if="showMenu && auths.id && auths.role != 'user'"
            :actions="
                treeType == 'folder'
                    ? contextMenuActionsFolder
                    : contextMenuActionsFile
            "
            @action-clicked="handleActionClick"
            :x="menuX"
            :y="menuY"
            :treeUserId="treeUserId"
        />

        <div class="overlay" @click="closeContextMenu" v-if="showMenu" />
    </CSidebar>
</template>

<style scoped>
.folder-item {
    cursor: pointer;
    transition: background-color 0.2s;
}
.folder-item:hover:not(.disabled-item) {
    background-color: #7c7e80;
}
.cursor-pointer {
    cursor: pointer;
}
.folder-list {
    min-height: 150px;
}
.disabled-item {
    opacity: 0.6;
    cursor: not-allowed !important;
    background-color: #f8f9fa;
}
</style>
