<template>
    <CModal :visible="visible" @close="close" size="lg">
        <CModalHeader>
            <CModalTitle>Импорт содержимого</CModalTitle>
        </CModalHeader>
        <CModalBody>
            <div class="alert" :class="hasCurrentResource ? 'alert-info' : 'alert-primary'">
                <i class="bi me-2" :class="hasCurrentResource ? 'bi-info-circle' : 'bi-file-earmark-plus'"></i>
                <template v-if="hasCurrentResource">
                    Содержимое будет <strong>вставлено в текущий документ</strong> (в конец).
                    Документ при этом не сохраняется — вы сможете отредактировать и сохранить его сами.
                </template>
                <template v-else>
                    Будет <strong>создан новый документ</strong> с содержимым импортируемого файла.
                </template>
            </div>

            <div class="mb-3">
                <label class="form-label">Выберите ZIP файл</label>
                <input type="file" class="form-control" accept=".zip" @change="handleFileSelect"
                    :disabled="importing" />
                <div class="form-text">
                    Поддерживаются только ZIP архивы, экспортированные из системы
                </div>
            </div>

            <!-- Информация о файле -->
            <div v-if="fileInfo" class="alert alert-info">
                <div><strong>Название:</strong> {{ fileInfo.name }}</div>
                <div><strong>Размер:</strong> {{ formatFileSize(fileInfo.size) }}</div>
                <div v-if="previewData">
                    <strong>Название ресурса:</strong> {{ previewData?.resource?.name || '—' }}
                    <span class="ms-3">
                        <strong>Блоков:</strong> {{ previewData?.resource?.data?.length || 0 }}
                    </span>
                    <span v-if="previewData?.files" class="ms-3">
                        <strong>Файлов:</strong> {{ Object.keys(previewData.files).length }}
                    </span>
                    <span v-if="previewData?.has_tests" class="ms-3">
                        <strong>Тестов:</strong> {{ previewData?.tests?.length || 0 }}
                    </span>
                </div>
                <div v-if="previewData?.exported_at" class="mt-1">
                    <strong>Экспортирован:</strong> {{ formatDate(previewData.exported_at) }}
                </div>
            </div>

            <!-- Опции импорта -->
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="importTests" v-model="importTests"
                        :disabled="importing || !previewData?.has_tests" />
                    <label class="form-check-label" for="importTests">
                        Импортировать тесты
                        <span v-if="previewData?.has_tests" class="text-muted">
                            ({{ previewData?.tests?.length || 0 }} шт.)
                        </span>
                        <span v-else class="text-muted">(тестов нет)</span>
                    </label>
                </div>
                <div v-if="!hasCurrentResource" class="form-text text-info">
                    <i class="bi bi-info-circle me-1"></i>
                    Новый документ будет создан сразу с содержимым импортируемого файла
                    в текущей папке, после чего откроется для редактирования.
                </div>
            </div>

            <!-- Ошибки -->
            <div v-if="error" class="alert alert-danger">
                {{ error }}
            </div>
        </CModalBody>
        <CModalFooter>
            <CButton color="secondary" @click="close" :disabled="importing">Отмена</CButton>
            <CButton color="primary" @click="importResource" :disabled="!selectedFile || importing">
                <span v-if="importing">
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Импорт...
                </span>
                <span v-else>
                    <i class="bi bi-upload me-1"></i>{{ hasCurrentResource ? 'Вставить в документ' : 'Создать документ из файла' }}
                </span>
            </CButton>
        </CModalFooter>
    </CModal>
</template>

<script>
import JSZip from 'jszip';

export default {
    props: {
        visible: {
            type: Boolean,
            default: false
        },
        datasend: {
            type: Function,
            required: true
        },
        showToast: {
            type: Function,
            required: true
        },
        getMenu: {
            type: Function,
            required: true
        },
        // tree_id редактируемого документа (если он уже сохранён)
        resourceId: {
            type: [String, Number],
            default: null
        },
        // tree_id родительской папки (для создания нового документа)
        parentFolderId: {
            type: [String, Number],
            default: null
        },
        server: {
            type: String,
            default: ''
        },
        editor: {
            type: Object,
            default: null
        }
    },
    data() {
        return {
            selectedFile: null,
            previewData: null,
            fileInfo: null,
            importTests: true,
            importing: false,
            error: null,
        };
    },
    computed: {
        hasCurrentResource() {
            return !!this.resourceId;
        },
    },
    watch: {
        visible(val) {
            if (val) {
                this.selectedFile = null;
                this.previewData = null;
                this.fileInfo = null;
                this.importTests = true;
                this.error = null;
            }
        }
    },
    methods: {
        handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (!file.name.toLowerCase().endsWith('.zip')) {
                this.selectedFile = null;
                this.fileInfo = null;
                this.previewData = null;
                this.error = 'Поддерживаются только ZIP архивы, экспортированные из системы.';
                return;
            }

            this.selectedFile = file;
            this.fileInfo = {
                name: file.name,
                size: file.size,
            };
            this.error = null;

            this.readZipFile(file);
        },
        async readZipFile(file) {
            try {
                const zip = await JSZip.loadAsync(file);

                const jsonFile = zip.file('resource.json');
                if (!jsonFile) {
                    throw new Error('В ZIP архиве не найден файл resource.json');
                }

                const jsonContent = await jsonFile.async('string');
                const data = JSON.parse(jsonContent);

                if (!data.version || !data.resource) {
                    throw new Error('Неверный формат файла resource.json');
                }

                this.previewData = data;

            } catch (error) {
                console.error('Read ZIP error:', error);
                this.error = 'Не удалось прочитать ZIP архив: ' + error.message;
            }
        },
        // Полный URL для файла, восстановленного на сервере
        resolveFileUrl(url) {
            if (!url || typeof url !== 'string') return url;
            if (/^(https?:)?\/\//i.test(url) || url.startsWith('data:')) return url;
            const base = (this.server || window.location.origin).replace(/\/+$/, '');
            return base + (url.startsWith('/') ? url : '/' + url);
        },
        // Подставляем абсолютные URL в блоки перед вставкой в редактор
        prepareBlocksForEditor(blocks) {
            if (!Array.isArray(blocks)) return [];

            return blocks.map((block) => {
                const prepared = JSON.parse(JSON.stringify(block));

                if (prepared.type === 'image' && prepared.data?.file?.url) {
                    prepared.data.file.url = this.resolveFileUrl(prepared.data.file.url);
                }
                if (prepared.type === 'attaches' && prepared.data?.file?.url) {
                    prepared.data.file.url = this.resolveFileUrl(prepared.data.file.url);
                }
                if (prepared.type === 'gallery' && Array.isArray(prepared.data?.files)) {
                    prepared.data.files = prepared.data.files.map((f) => ({
                        ...f,
                        url: this.resolveFileUrl(f.url),
                    }));
                }

                return prepared;
            });
        },
        async importResource() {
            if (!this.selectedFile) return;

            this.importing = true;
            this.error = null;

            const formData = new FormData();
            formData.append('file', this.selectedFile);
            formData.append('include_tests', this.importTests ? '1' : '0');

            try {
                if (this.hasCurrentResource) {
                    // Существующий документ: вставляем блоки в редактор,
                    // ничего не сохраняем
                    formData.append('tree_id', this.resourceId);

                    const result = await this.datasend(
                        'resource/import-into',
                        'POST',
                        formData,
                        true
                    );

                    if (!result.success) {
                        throw new Error(result.message || 'Ошибка импорта');
                    }

                    const blocks = this.prepareBlocksForEditor(result.blocks);

                    // Вставляем блоки в конец редактора
                    if (this.editor && blocks.length) {
                        const currentContent = await this.editor.save();
                        const currentBlocks = Array.isArray(currentContent.blocks)
                            ? currentContent.blocks
                            : [];

                        await this.editor.render({
                            time: Date.now(),
                            version: currentContent.version || '2.30.6',
                            blocks: [...currentBlocks, ...blocks],
                        });
                    }

                    const testsMsg = result.tests_imported > 0
                        ? ` и ${result.tests_imported} тестов`
                        : '';
                    this.showToast(`Содержимое вставлено в документ${testsMsg}`, 'success');
                } else {
                    // Новый документ: создаём ресурс сразу с содержимым файла
                    // в текущей папке (parentFolderId), с тестами — если включены
                    if (this.parentFolderId) {
                        formData.append('parent_id', this.parentFolderId);
                    }

                    const result = await this.datasend(
                        'resource/import',
                        'POST',
                        formData,
                        true
                    );

                    if (!result.success) {
                        throw new Error(result.message || 'Ошибка импорта');
                    }

                    const testsMsg = result.tests_imported > 0
                        ? ` с ${result.tests_imported} тестами`
                        : '';
                    this.showToast(`Документ «${result.name}» создан${testsMsg}`, 'success');

                    await this.getMenu();

                    // Переходим к редактированию созданного документа
                    if (result.tree_id) {
                        this.$router.push({
                            name: 'EditFile',
                            params: { id: result.tree_id },
                        });
                        return;
                    }
                }

                this.close();
            } catch (error) {
                console.error('Import error:', error);
                this.error = error.message || 'Ошибка при импорте';
                this.showToast(this.error, 'danger');
            } finally {
                this.importing = false;
            }
        },
        close() {
            this.$emit('close');
        },
        formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        },
        formatDate(date) {
            if (!date) return '—';
            return new Date(date).toLocaleString('ru-RU');
        },
    },
};
</script>
