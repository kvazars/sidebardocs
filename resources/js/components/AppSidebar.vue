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
            visibleSearchModal: false, // Новое модальное окно для поиска
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

            // Данные для поиска
            searchQuery: "",
            searchResults: [],
            isSearching: false,
            searchError: null,
            searchFilters: {
                type: "all", // all, folders, files
            },
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

        // Методы для поиска
        openSearchModal() {
            this.visibleSearchModal = true;
            this.searchQuery = "";
            this.searchResults = [];
            this.searchError = null;

            // Фокус на поле поиска после открытия
            this.$nextTick(() => {
                const searchInput = this.$refs.searchInput;
                if (searchInput) {
                    searchInput.focus();
                }
            });
        },

        closeSearchModal() {
            this.visibleSearchModal = false;
            this.searchQuery = "";
            this.searchResults = [];
            this.searchError = null;
        },

        // Обработчик поиска (вы будете сами обрабатывать)
        handleSearch() {
            if (!this.searchQuery || this.searchQuery.length < 2) {
                this.searchError = "Введите минимум 2 символа";
                return;
            }

            this.isSearching = true;
            this.searchError = null;

            // Здесь вы будете обрабатывать поиск самостоятельно
            // Например, эмитить событие или вызывать props метод
            this.$emit('search', {
                query: this.searchQuery,
                filters: this.searchFilters
            });

            // Для демонстрации пока просто эмулируем окончание поиска
            setTimeout(() => {
                this.isSearching = false;
            }, 500);
        },

        // Очистка поиска
        clearSearch() {
            this.searchQuery = "";
            this.searchResults = [];
            this.searchError = null;
        },

        // Переход к результату
        navigateToResult(result) {
            this.closeSearchModal();

            if (result.type === 'folder') {
                // Раскрываем папку в дереве
                this.expandFolderPath(result.id);
                // Подсвечиваем элемент
                this.highlightElement(`folder-${result.id}`);
            } else if (result.type === 'file') {
                this.$router.push({
                    name: "EditFile",
                    params: { id: result.id },
                });
            }

            this.showToast(`Открыт: ${result.name}`, "success");
        },

        // Вспомогательные методы для навигации
        expandFolderPath(folderId) {
            const findAndExpandPath = (items, targetId, parents = []) => {
                if (!items) return false;

                for (const item of items) {
                    if (item.id === targetId) {
                        parents.forEach(p => this.expandedFolders.add(p));
                        return true;
                    }

                    if (item.children || item.items) {
                        const children = item.children || item.items;
                        if (findAndExpandPath(children, targetId, [...parents, item.id])) {
                            return true;
                        }
                    }
                }
                return false;
            };

            if (this.menu && Array.isArray(this.menu)) {
                findAndExpandPath(this.menu, folderId);
            }
        },

        highlightElement(elementId) {
            this.$nextTick(() => {
                const element = document.getElementById(elementId);
                if (element) {
                    element.classList.add('search-highlight');
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    setTimeout(() => {
                        element.classList.remove('search-highlight');
                    }, 2000);
                }
            });
        },

        // Фильтрация результатов по типу
        setFilter(type) {
            this.searchFilters.type = type;
            if (this.searchQuery.length >= 2) {
                this.handleSearch();
            }
        },

        // Остальные методы (save, prepareFolderOptions, и т.д.) остаются без изменений
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
                        this.showToast(res.message, "success");
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
            else if (typeof data === "object" && data !== null) {
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
                        rawData: data,
                    });
                }

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

            const hasId = obj.id !== undefined && obj.id !== null;
            const hasName = obj.name !== undefined || obj.title !== undefined;
            const isFolderType = obj.type === "folder";
            const hasChildren =
                obj.children !== undefined || obj.items !== undefined;

            return hasId && (isFolderType || (hasName && hasChildren));
        },

        findParentFolderForFile(fileId, data, currentParent = null, path = "") {
            if (!data) return null;

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
            else if (typeof data === "object" && data !== null) {
                const isTargetFile =
                    data.id === fileId &&
                    (data.type === "file" ||
                        data.type === "document" ||
                        data.resource_type === "file" ||
                        !this.isFolderObject(data));

                if (isTargetFile) {
                    return currentParent;
                }

                const isFolder = this.isFolderObject(data);
                const newParent = isFolder ? data.id : currentParent;

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

        toggleFolderExpansion(folderId) {
            if (this.expandedFolders.has(folderId)) {
                this.expandedFolders.delete(folderId);
            } else {
                this.expandedFolders.add(folderId);
            }
        },

        selectFolder(folderId) {
            this.selectedNewFolderId = folderId;
        },

        saveFolderChange() {
            if (!this.selectedFileId || !this.selectedNewFolderId) {
                this.showToast("Выберите папку", "warning");
                return;
            }

            if (this.selectedNewFolderId === this.selectedFileCurrentFolderId) {
                this.showToast("Файл уже находится в этой папке", "warning");
                return;
            }

            const form = {
                tree_id: this.selectedNewFolderId,
            };

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
                    this.showToast(res.message, "success");
                    this.getMenu();
                })
                .catch((error) => console.log(error));
        },
        downdoc(id) {
            this.datasend("doc/down/" + id, "POST", {})
                .then((res) => {
                    this.showToast(res.message, "success");
                    this.getMenu();
                })
                .catch((error) => console.log(error));
        },
        deleteFolder(id) {
            if (confirm("Вы уверены?")) {
                this.datasend("folder/" + id, "DELETE", {})
                    .then((res) => {
                        this.showToast(res.message, "success");
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
                                this.showToast(res.message, "success");
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
    <CSidebar class="border-end" colorScheme="light" position="fixed" :unfoldable="sidebar.unfoldable"
        :visible="sidebar.visible" @visible-change="(value) => sidebar.toggleVisible(value)">
        <CSidebarHeader class="border-bottom">
            <RouterLink custom to="/" v-slot="{ href, navigate }">
                <CSidebarBrand v-bind="$attrs" class="w-100" as="a" :href="href" @click="navigate">
                    <img class="w-100 object-fit-contain" :src="logo" alt="" v-if="logo" />
                </CSidebarBrand>
            </RouterLink>
            <!-- <CCloseButton class="d-lg-none11" dark @click="sidebar.toggleVisible()" /> -->
        </CSidebarHeader>

        <div class="sidebar-search p-2 border-bottom">
            <div class="input-group d-flex justify-content-end">

                <button class="btn btn-outline-secondary" type="button" @click="openSearchModal">
                    <i class="bi bi-search"></i>
                </button>
                <button class="btn btn-outline-secondary" type="button" @click="addFirstLevel"
                    v-if="auths.role == 'ceo' || auths.role == 'admin'">
                    <i class="bi bi-plus"></i>
                </button>

            </div>
        </div>

        <SidebarNav :showContextMenu="showContextMenu" :menu="menu" />


        <CSidebarFooter class="border-top d-none d-lg-flex">
            <CSidebarToggler @click="sidebar.toggleUnfoldable()" />
        </CSidebarFooter>

        <!-- Модальное окно для создания/редактирования папки -->
        <CModal :visible="visibleModalFolder" @close="
            () => {
                visibleModalFolder = false;
            }
        " aria-labelledby="FolderLabel">
            <CModalHeader>
                <CModalTitle id="FolderLabel">
                    {{ folderId ? "Редактирование папки" : "Новая папка" }}
                </CModalTitle>
            </CModalHeader>
            <CModalBody>
                <div class="w-100 d-flex flex-column gap-4">
                    <div class="w-100 d-flex flex-column gap-2">
                        <CFormInput v-model="folderTitle" v-on:keyup.enter="
                            () => {
                                save();
                            }
                        " name="folderName" type="text" placeholder="Новое имя папки" />
                    </div>
                </div>
            </CModalBody>
            <CModalFooter>
                <CButton color="secondary" @click="
                    () => {
                        visibleModalFolder = false;
                    }
                ">
                    Отмена
                </CButton>
                <CButton color="primary" @click="
                    () => {
                        save();
                    }
                ">Сохранить</CButton>
            </CModalFooter>
        </CModal>

        <!-- Модальное окно для изменения родительской папки файла -->
        <CModal :visible="visibleModalChangeFolder" @close="
            () => {
                visibleModalChangeFolder = false;
                selectedNewFolderId = null;
                selectedFileCurrentFolderId = null;
            }
        " aria-labelledby="ChangeFolderLabel" size="lg">
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
                        <div class="folder-list border rounded p-3" style="max-height: 300px; overflow-y: auto">
                            <div v-for="folder in folderOptions" :key="folder.id"
                                class="folder-item py-2 px-3 border-bottom cursor-pointer" :class="{
                                    'bg-primary text-white':
                                        selectedNewFolderId === folder.id,
                                    'disabled-item':
                                        folder.id ===
                                        selectedFileCurrentFolderId,
                                }" @click="
                                    folder.id !== selectedFileCurrentFolderId &&
                                    selectFolder(folder.id)
                                    " :style="{
                                    paddingLeft:
                                        folder.level * 20 +
                                        10 +
                                        'px !important',
                                }" :title="folder.id === selectedFileCurrentFolderId
                                        ? 'Файл уже находится в этой папке'
                                        : ''
                                    ">
                                <div class="d-flex align-items-center">
                                    <i class="bi me-2" :class="folder.id ===
                                            selectedFileCurrentFolderId
                                            ? 'bi-folder-check text-success'
                                            : 'bi-folder'
                                        "></i>
                                    {{ folder.name }}
                                    <span v-if="
                                        folder.id ===
                                        selectedFileCurrentFolderId
                                    " class="badge bg-secondary ms-2">текущая</span>
                                    <small v-else-if="folder.level > 0" class="text-muted ms-2">(уровень {{ folder.level
                                        }})</small>
                                </div>
                            </div>
                            <div v-if="folderOptions.length === 0" class="text-muted text-center py-3">
                                Нет доступных папок
                            </div>
                        </div>
                    </div>

                    <div class="selected-folder-info alert alert-success" v-if="selectedNewFolderId">
                        <strong>Выбрана папка:</strong>
                        {{
                            folderOptions.find(
                                (f) => f.id === selectedNewFolderId
                            )?.originalName
                        }}
                    </div>

                    <div class="alert alert-warning" v-if="
                        selectedNewFolderId === selectedFileCurrentFolderId
                    ">
                        <small>Выбрана текущая папка файла. Выберите другую папку
                            для перемещения.</small>
                    </div>
                </div>
            </CModalBody>
            <CModalFooter>
                <CButton color="secondary" @click="
                    () => {
                        visibleModalChangeFolder = false;
                        selectedNewFolderId = null;
                        selectedFileCurrentFolderId = null;
                    }
                ">
                    Отмена
                </CButton>
                <CButton color="primary" @click="saveFolderChange" :disabled="!selectedNewFolderId ||
                    selectedNewFolderId === selectedFileCurrentFolderId
                    ">
                    Сохранить
                </CButton>
            </CModalFooter>
        </CModal>

        <!-- Модальное окно поиска -->
        <CModal :visible="visibleSearchModal" @close="closeSearchModal" aria-labelledby="SearchModalLabel" size="lg"
            backdrop="static">
            <CModalHeader>
                <CModalTitle id="SearchModalLabel">
                    <i class="bi bi-search me-2"></i>
                    Поиск по объектам
                </CModalTitle>
            </CModalHeader>
            <CModalBody>
                <div class="search-container">
                    <!-- Поле ввода поиска -->
                    <div class="search-input-wrapper mb-4">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input ref="searchInput" type="text" class="form-control border-start-0 border-end-0"
                                placeholder="Введите текст для поиска (минимум 2 символа)..." v-model="searchQuery"
                                @keyup.enter="handleSearch" />
                            <button class="btn btn-primary" type="button" @click="handleSearch"
                                :disabled="isSearching || searchQuery.length < 2">
                                <span v-if="isSearching">
                                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    Поиск...
                                </span>
                                <span v-else>
                                    <i class="bi bi-arrow-right me-2"></i>
                                    Найти
                                </span>
                            </button>
                        </div>
                        <small class="text-muted mt-2 d-block">
                            <i class="bi bi-info-circle me-1"></i>
                            Минимум 2 символа. Поиск осуществляется по названиям и содержимому.
                        </small>
                    </div>

                    <!-- Фильтры поиска -->
                    <div class="search-filters mb-4" v-if="searchQuery.length >= 2">
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="searchFilter" id="filterAll" value="all"
                                v-model="searchFilters.type" @change="handleSearch" />
                            <label class="btn btn-outline-secondary" for="filterAll">
                                <i class="bi bi-grid-3x3-gap-fill me-2"></i>
                                Все
                            </label>

                            <input type="radio" class="btn-check" name="searchFilter" id="filterFolders" value="folders"
                                v-model="searchFilters.type" @change="handleSearch" />
                            <label class="btn btn-outline-secondary" for="filterFolders">
                                <i class="bi bi-folder me-2"></i>
                                Папки
                            </label>

                            <input type="radio" class="btn-check" name="searchFilter" id="filterFiles" value="files"
                                v-model="searchFilters.type" @change="handleSearch" />
                            <label class="btn btn-outline-secondary" for="filterFiles">
                                <i class="bi bi-file-text me-2"></i>
                                Файлы
                            </label>
                        </div>
                    </div>

                    <!-- Состояния поиска -->
                    <div v-if="searchError" class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        {{ searchError }}
                    </div>

                    <div v-else-if="isSearching" class="text-center py-5">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Поиск...</span>
                        </div>
                        <p class="text-muted">Выполняется поиск...</p>
                    </div>

                    <div v-else-if="searchQuery.length >= 2 && searchResults.length === 0" class="text-center py-5">
                        <i class="bi bi-search-heart display-1 text-muted mb-3"></i>
                        <h5>Ничего не найдено</h5>
                        <p class="text-muted">
                            Попробуйте изменить поисковый запрос или фильтры
                        </p>
                    </div>

                    <!-- Результаты поиска -->
                    <div v-else-if="searchResults.length > 0" class="search-results">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">
                                Найдено: {{ searchResults.length }}
                            </h6>
                            <button class="btn btn-sm btn-outline-secondary" @click="clearSearch">
                                <i class="bi bi-x-circle me-1"></i>
                                Очистить
                            </button>
                        </div>

                        <div class="list-group">
                            <div v-for="result in searchResults" :key="result.id"
                                class="list-group-item list-group-item-action search-result-item"
                                @click="navigateToResult(result)">
                                <div class="d-flex align-items-center">
                                    <!-- Иконка в зависимости от типа -->
                                    <div class="result-icon me-3">
                                        <i class="bi" :class="{
                                            'bi-folder text-warning': result.type === 'folder',
                                            'bi-file-text text-primary': result.type === 'file',
                                            'bi-file-earmark-text text-info': result.type === 'document',
                                            'bi-file-earmark text-secondary': !result.type
                                        }" style="font-size: 1.5rem;"></i>
                                    </div>

                                    <!-- Информация о результате -->
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center">
                                            <h6 class="mb-0 me-2">{{ result.name || result.title }}</h6>
                                            <span class="badge" :class="{
                                                'bg-warning': result.type === 'folder',
                                                'bg-primary': result.type === 'file',
                                                'bg-info': result.type === 'document',
                                                'bg-secondary': !result.type
                                            }">
                                                {{ result.type || 'объект' }}
                                            </span>
                                        </div>

                                        <!-- Путь к объекту -->
                                        <div class="result-path small text-muted mt-1" v-if="result.path">
                                            <i class="bi bi-folder me-1"></i>
                                            {{ result.path }}
                                        </div>

                                        <!-- Дополнительная информация -->
                                        <div class="result-meta small text-muted mt-1">
                                            <span v-if="result.created_at" class="me-3">
                                                <i class="bi bi-calendar me-1"></i>
                                                {{ new Date(result.created_at).toLocaleDateString() }}
                                            </span>
                                            <span v-if="result.size">
                                                <i class="bi bi-hdd me-1"></i>
                                                {{ result.size }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Стрелка для перехода -->
                                    <div class="result-action ms-3">
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Подсказка при пустом поиске -->
                    <div v-else-if="!searchQuery" class="search-hint text-center py-5">
                        <i class="bi bi-arrow-up display-4 text-muted mb-3"></i>
                        <p class="text-muted">
                            Введите текст для поиска в поле выше
                        </p>
                        <small class="text-muted">
                            Можно искать по названиям файлов, папок и их содержимому
                        </small>
                    </div>
                </div>
            </CModalBody>
            <CModalFooter>
                <CButton color="secondary" @click="closeSearchModal">
                    Закрыть
                </CButton>
            </CModalFooter>
        </CModal>

        <ContextMenu v-if="showMenu && auths.id && auths.role != 'user'" :actions="treeType == 'folder'
                ? contextMenuActionsFolder
                : contextMenuActionsFile
            " @action-clicked="handleActionClick" :x="menuX" :y="menuY" :treeUserId="treeUserId" />

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

/* Стили для поиска */
.sidebar-search .input-group-text,
.sidebar-search .form-control,
.sidebar-search .btn {
    background-color: transparent;
    border-color: #dee2e6;
}

.sidebar-search .form-control {
    cursor: pointer;
}

.sidebar-search .form-control:focus {
    box-shadow: none;
    border-color: #dee2e6;
}

.search-result-item {
    cursor: pointer;
    transition: all 0.2s;
}

.search-result-item:hover {
    background-color: #f8f9fa;
    transform: translateX(5px);
}

.search-highlight {
    background-color: #fff3cd;
    transition: background-color 1s;
}

/* Анимации */
.search-result-item {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Для модального окна поиска */
:deep(.modal-content) {
    border: none;
    border-radius: 1rem;
}

:deep(.modal-header) {
    border-bottom: 1px solid #dee2e6;
    background-color: #f8f9fa;
    border-radius: 1rem 1rem 0 0;
}

:deep(.modal-footer) {
    border-top: 1px solid #dee2e6;
    background-color: #f8f9fa;
    border-radius: 0 0 1rem 1rem;
}
</style>