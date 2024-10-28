<template>
    <CCard class="my-4">
        <CCardHeader>
            <CNav class="justify-content-center">
                <CNavItem>
                    <CNavLink href="#" active>
                        Пользователи
                    </CNavLink>
                </CNavItem>
                <CNavItem>
                    <CNavLink href="#">Создатели</CNavLink>
                </CNavItem>
                <CNavItem>
                    <CNavLink href="#">Администраторы</CNavLink>
                </CNavItem>
            </CNav>
        </CCardHeader>
        <CCardBody>
            <h4 class="text-center">Управление пользователями</h4>
            <div class="w-100 row">
                <div class="col-lg-6">
                    <p class="fw-bold text-center">Список пользователей</p>
                    <CListGroup>
                        <CListGroupItem>101</CListGroupItem>
                        <CListGroupItem>402</CListGroupItem>
                        <CListGroupItem>307</CListGroupItem>
                    </CListGroup>
                </div>
                <div class="col-lg-6">
                    <p class="fw-bold text-center">Создание пользователя</p>
                    <CForm>
                        <CFormInput class="mb-2" type="text" label="Имя" placeholder="Введите имя пользователя" />
                        <CFormInput class="mb-2" type="text" label="Логин" placeholder="Введите логин пользователя" />
                        <CFormInput class="mb-2" type="password" label="Пароль"
                            placeholder="Введите пароль пользователя" />
                        <CButton color="primary">Создать</CButton>
                    </CForm>
                </div>
            </div>
        </CCardBody>
    </CCard>
</template>

<script>
export default {

}
</script>