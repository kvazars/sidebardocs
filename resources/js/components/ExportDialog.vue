<template>
    <CModal :visible="visible" @close="close" size="lg">
        <CModalHeader>
            <CModalTitle>Экспорт ресурса</CModalTitle>
        </CModalHeader>
        <CModalBody>
            <div class="mb-4">
                <h6>Выберите, что включить в экспорт:</h6>
            </div>

            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Будут экспортированы все файлы (изображения, вложения) в ZIP архиве
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="includeTests" v-model="includeTests"
                    :disabled="!actualHasTests || exporting || testsLoading" />
                <label class="form-check-label" for="includeTests">
                    Включить тесты
                    <span v-if="testsLoading" class="text-muted">(загрузка...)</span>
                    <span v-else-if="actualHasTests" class="text-muted">({{ actualTestsCount }} тестов)</span>
                    <span v-else class="text-muted">(тестов нет)</span>
                </label>
            </div>

            <div v-if="!testsLoading && !actualHasTests" class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                У этого ресурса нет тестов.
            </div>

            <div v-if="includeTests && actualHasTests" class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Будут экспортированы все {{ actualTestsCount }} тестов с вопросами и ответами.
            </div>

            <div v-if="error" class="alert alert-danger">
                {{ error }}
            </div>
        </CModalBody>
        <CModalFooter>
            <CButton color="secondary" @click="close" :disabled="exporting">Отмена</CButton>
            <CButton color="primary" @click="exportResource" :disabled="exporting">
                <span v-if="exporting">
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Подготовка...
                </span>
                <span v-else>
                    <i class="bi bi-download me-1"></i>Экспортировать (ZIP)
                </span>
            </CButton>
        </CModalFooter>
    </CModal>
</template>

<script>
export default {
    props: {
        visible: {
            type: Boolean,
            default: false
        },
        resourceId: {
            type: [String, Number],
            required: true
        },
        resourceName: {
            type: String,
            default: ''
        },
        datasend: {
            type: Function,
            required: true
        },
        showToast: {
            type: Function,
            required: true
        },
        apiBase: {
            type: String,
            default: ''
        }
    },
    data() {
        return {
            includeTests: false,
            exporting: false,
            error: null,
            localHasTests: false,
            localTestsCount: 0,
            testsLoading: false,
        };
    },
    computed: {
        actualHasTests() {
            return this.localHasTests;
        },
        actualTestsCount() {
            return this.localTestsCount;
        },
    },
    watch: {
        visible(val) {
            if (val) {
                this.includeTests = false;
                this.error = null;
                this.localHasTests = false;
                this.localTestsCount = 0;
                this.fetchTests();
            }
        }
    },
    methods: {
        // Загружаем список тестов ресурса при открытии диалога
        async fetchTests() {
            if (!this.resourceId) {
                this.localHasTests = false;
                this.localTestsCount = 0;
                return;
            }

            this.testsLoading = true;
            try {
                const response = await this.datasend(
                    `tests/${this.resourceId}/get`,
                    'GET',
                    {}
                );
                const tests = Array.isArray(response?.data) ? response.data : [];
                this.localHasTests = tests.length > 0;
                this.localTestsCount = tests.length;
            } catch (error) {
                // Тестов нет или ошибка - скрываем опцию
                this.localHasTests = false;
                this.localTestsCount = 0;
            } finally {
                this.testsLoading = false;
            }
        },
        async exportResource() {
            this.exporting = true;
            this.error = null;

            try {
                const token = localStorage.getItem('token');
                if (!token) {
                    throw new Error('Необходимо авторизоваться');
                }

                // Передаем include_tests как 1 или 0
                const includeTestsParam = this.includeTests ? '1' : '0';

                const baseUrl = this.apiBase || window.location.origin;
                const url = `${baseUrl}/export-resource/${this.resourceId}?include_tests=${includeTestsParam}`;

                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/zip',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    let errorMessage = 'Ошибка при экспорте';
                    try {
                        const text = await response.text();
                        const json = JSON.parse(text);
                        errorMessage = json.message || errorMessage;
                    } catch (e) {
                        errorMessage = response.statusText || errorMessage;
                    }
                    throw new Error(errorMessage);
                }

                const contentType = response.headers.get('Content-Type');
                if (!contentType || !contentType.includes('application/zip')) {
                    const text = await response.text();
                    try {
                        const json = JSON.parse(text);
                        throw new Error(json.message || 'Сервер вернул неверный формат');
                    } catch (e) {
                        throw new Error('Сервер вернул неверный формат файла');
                    }
                }

                const blob = await response.blob();

                if (blob.size === 0) {
                    throw new Error('Получен пустой ZIP архив');
                }

                // Используем название ресурса для имени файла
                let fileName = `resource_${this.resourceId}_${new Date().toISOString().slice(0, 10)}.zip`;
                if (this.resourceName) {
                    // Очищаем название от спецсимволов
                    const cleanName = this.resourceName.replace(/[^a-zA-Zа-яА-Я0-9]/g, '_');
                    fileName = `${cleanName}_${new Date().toISOString().slice(0, 10)}.zip`;
                }

                const downloadUrl = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = downloadUrl;
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                setTimeout(() => {
                    window.URL.revokeObjectURL(downloadUrl);
                }, 1000);

                const testsMsg = this.includeTests && this.actualTestsCount > 0
                    ? ` с ${this.actualTestsCount} тестами`
                    : '';
                this.showToast(`Ресурс успешно экспортирован${testsMsg}`, 'success');
                this.close();

            } catch (error) {
                console.error('Export error:', error);
                this.error = error.message || 'Ошибка при экспорте ресурса';
                this.showToast(this.error, 'danger');
            } finally {
                this.exporting = false;
            }
        },
        close() {
            this.$emit('close');
        },
    },
};
</script>
