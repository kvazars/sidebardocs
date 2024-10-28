<template>
    <CCard class="my-4">
        <CCardHeader>
            <CNav class="justify-content-center">
                <CNavItem>
                    <CNavLink href="#" @click="() => { role = 'user' }" active> Пользователи </CNavLink>
                </CNavItem>
                <CNavItem>
                    <CNavLink href="#" @click="() => { role = 'ceo' }">Создатели</CNavLink>
                </CNavItem>
                <CNavItem>
                    <CNavLink href="#" @click="() => { role = 'admin' }">Администраторы</CNavLink>
                </CNavItem>
            </CNav>
        </CCardHeader>
        <CCardBody>
            <h4 class="text-center">Управление{{ role == 'user' ? ' пользователями' : (role == 'admin' ?
                ' администраторами' : ' создателями') }}</h4>
            <div class="w-100 row">
                <div class="col-lg-6">
                    <p class="fw-bold text-center">Список {{ role == 'user' ? 'пользователей' : (role == 'admin' ?
                        'администраторов' : 'создателей') }}</p>
                    <CAccordion v-if="role == 'user'">
                        <CAccordionItem :item-key="1">
                            <CAccordionHeader> 301 </CAccordionHeader>
                            <CAccordionBody>
                                <CListGroup flush>
                                    <CListGroupItem class="d-flex flex-row justify-content-between">
                                        <span>Cras justo odio</span>
                                        <div>
                                            <i class="fa fa-times text-danger"></i>
                                        </div>
                                    </CListGroupItem>
                                    <CListGroupItem class="d-flex flex-row justify-content-between">
                                        <span>Cras justo odio</span>
                                        <div>
                                            <i class="fa fa-times text-danger"></i>
                                        </div>
                                    </CListGroupItem>
                                    <CListGroupItem class="d-flex flex-row justify-content-between">
                                        <span>Cras justo odio</span>
                                        <div>
                                            <i class="fa fa-times text-danger"></i>
                                        </div>
                                    </CListGroupItem>
                                </CListGroup>
                            </CAccordionBody>
                        </CAccordionItem>
                        <CAccordionItem :item-key="2">
                            <CAccordionHeader> 322 </CAccordionHeader>
                            <CAccordionBody>
                                <CListGroup flush>
                                    <CListGroupItem class="d-flex flex-row justify-content-between">
                                        <span>Cras justo odio</span>
                                        <div>
                                            <i class="fa fa-times text-danger"></i>
                                        </div>
                                    </CListGroupItem>
                                    <CListGroupItem class="d-flex flex-row justify-content-between">
                                        <span>Cras justo odio</span>
                                        <div>
                                            <i class="fa fa-times text-danger"></i>
                                        </div>
                                    </CListGroupItem>
                                    <CListGroupItem class="d-flex flex-row justify-content-between">
                                        <span>Cras justo odio</span>
                                        <div>
                                            <i class="fa fa-times text-danger"></i>
                                        </div>
                                    </CListGroupItem>
                                </CListGroup>
                            </CAccordionBody>
                        </CAccordionItem>
                    </CAccordion>
                    <CListGroup v-else>
                        <CListGroupItem class="d-flex flex-row justify-content-between">
                            <span>Cras justo odio</span>
                            <div>
                                <i class="fa fa-times text-danger"></i>
                            </div>
                        </CListGroupItem>
                        <CListGroupItem class="d-flex flex-row justify-content-between">
                            <span>Cras justo odio</span>
                            <div>
                                <i class="fa fa-times text-danger"></i>
                            </div>
                        </CListGroupItem>
                        <CListGroupItem class="d-flex flex-row justify-content-between">
                            <span>Cras justo odio</span>
                            <div>
                                <i class="fa fa-times text-danger"></i>
                            </div>
                        </CListGroupItem>
                    </CListGroup>
                </div>
                <div class="col-lg-6">
                    <div v-if="role == 'user'">
                        <p class="fw-bold text-center">Создание группы</p>
                        <CForm>
                            <CFormInput class="mb-2" type="text" label="Название"
                                placeholder="Введите название группы" />
                            <CFormInput class="mb-2" type="text" label="Описание"
                                placeholder="Введите описание группы" />
                            <div class="text-center">
                                <CButton color="primary">Создать</CButton>
                            </div>
                        </CForm>
                    </div>
                    <div v-if="role == 'user'">
                        <p class="fw-bold text-center mt-4">Редактирование группы</p>
                        <CForm>

                            <CFormInput class="mb-2" type="text" label="Название"
                                placeholder="Введите название группы" />
                            <CFormInput class="mb-2" type="text" label="Описание"
                                placeholder="Введите описание группы" />
                            <div class="text-center">
                                <CButton color="primary">Создать</CButton>
                            </div>
                        </CForm>
                    </div>

                    <p class="fw-bold text-center mt-4">Создание пользователя</p>
<<<<<<< HEAD
=======
                    <CForm>
                        <CFormInput class="mb-2" type="text" label="Имя" placeholder="Введите имя пользователя" />
                        <CFormInput class="mb-2" type="text" label="Логин" placeholder="Введите логин пользователя" />
                        <CFormInput class="mb-2" type="password" label="Пароль"
                            placeholder="Введите пароль пользователя" />
                        <CFormSelect v-if="role == 'user'" class="mb-2" label="Присваивание группы">
                            <option disabled selected>Выбор группы</option>
                            <option value="1">401</option>
                            <option value="2">402</option>
                            <option value="3">403</option>
                        </CFormSelect>
                        <div class="text-center">
                            <CButton color="primary">Создать</CButton>
                        </div>
                    </CForm>
                    <p class="fw-bold text-center mt-4">
                        Редактирование пользователя
                    </p>
>>>>>>> 31830d8f652831910a80f044f42c003ad9911264
                    <CForm>
                        <CFormInput class="mb-2" type="text" label="Имя" placeholder="Введите имя пользователя" />
                        <CFormInput class="mb-2" type="text" label="Логин" placeholder="Введите логин пользователя" />
                        <CFormInput class="mb-2" type="password" label="Пароль"
                            placeholder="Введите пароль пользователя" />
                        <CFormSelect v-if="role == 'user'" class="mb-2" label="Присваивание группы">
                            <option disabled selected>Выбор группы</option>
                            <option value="1">401</option>
                            <option value="2">402</option>
                            <option value="3">403</option>
                        </CFormSelect>
                        <div class="text-center">
<<<<<<< HEAD
                            <CButton color="primary">Создать</CButton>
                        </div>
                    </CForm>
                    <p class="fw-bold text-center mt-4">
                        Редактирование пользователя
                    </p>
                    <CForm>
                        <CFormInput class="mb-2" type="text" label="Имя" placeholder="Введите имя пользователя" />
                        <CFormInput class="mb-2" type="text" label="Логин" placeholder="Введите логин пользователя" />
                        <CFormInput class="mb-2" type="password" label="Пароль"
                            placeholder="Введите пароль пользователя" />
                        <CFormSelect v-if="role == 'user'" class="mb-2" label="Присваивание группы">
                            <option disabled selected>Выбор группы</option>
                            <option value="1">401</option>
                            <option value="2">402</option>
                            <option value="3">403</option>
                        </CFormSelect>
                        <div class="text-center">
=======
>>>>>>> 31830d8f652831910a80f044f42c003ad9911264
                            <CButton color="primary">Редактировать</CButton>
                        </div>
                    </CForm>
                </div>
            </div>
        </CCardBody>
    </CCard>
</template>

<script>
export default {
<<<<<<< HEAD
    data() {
        return {
            role: 'user',
=======
    props: ['datasend'],
    data() {
        return {
            role: 'user',
            userList: null,
        }
    },
    mounted() {

    },
    methods: {
        getList() {
            this.datasend().then().catch();
>>>>>>> 31830d8f652831910a80f044f42c003ad9911264
        }
    },
};
</script>
