<template>
	<CCard class="my-4">
		<CCardHeader>
			<CNav class="justify-content-center">
				<CNavItem>
					<CNavLink
						href="#"
						@click="
							() => {
								role = 'user';
							}
						"
						active
					>
						Пользователи
					</CNavLink>
				</CNavItem>
				<CNavItem>
					<CNavLink
						href="#"
						@click="
							() => {
								role = 'ceo';
							}
						"
						>Создатели</CNavLink
					>
				</CNavItem>
				<CNavItem>
					<CNavLink
						href="#"
						@click="
							() => {
								role = 'admin';
							}
						"
						>Администраторы</CNavLink
					>
				</CNavItem>
			</CNav>
		</CCardHeader>
		<CCardBody>
			<h4 class="text-center">
				Управление{{
					role == "user"
						? " пользователями"
						: role == "admin"
						? " администраторами"
						: " создателями"
				}}
			</h4>
			<div class="w-100 row">
				<div class="col-lg-6">
					<p class="fw-bold text-center">
						Список
						{{
							role == "user"
								? "пользователей"
								: role == "admin"
								? "администраторов"
								: "создателей"
						}}
					</p>
					<CButton
						@click="
							() => {
								cardName = 'createGroup';
							}
						"
						class="text-white mb-2"
						color="success"
						><i class="fa fa-plus"></i
					></CButton>
					<CAccordion v-if="role == 'user'">
						<CAccordionItem
							:item-key="index"
							v-for="(group, index) in groupList"
							:key="index"
						>
							<CAccordionHeader>{{
								group.name
							}}</CAccordionHeader>
							<CAccordionBody>
								<div
									class="d-flex flex-row justify-content-between mb-2"
								>
									<CButtonGroup
										><CButton
											@click="
												() => {
													cardName = 'editGroup';
												}
											"
											class=""
											color="primary"
											><i
												class="fa fa-pencil-square-o"
											></i
										></CButton>
										<CButton
											class="text-white"
											color="danger"
											><i
												class="fa fa-times"
											></i></CButton
									></CButtonGroup>
									<CButton
										class="text-white"
										color="success"
										@click="
											() => {
												cardName = 'createUser';
											}
										"
										><i class="fa fa-user-plus"></i
									></CButton>
								</div>
								<CListGroup flush v-if="group.users[0]">
									<CListGroupItem
										class="d-flex flex-row justify-content-between"
										v-for="(user, index) in group.users"
										:key="index"
									>
										<span>{{ user.login }}</span>
										<div
											class="d-flex flex-row align-items-center gap-2"
										>
											<i
												class="fa fa-pencil-square-o text-info"
												@click="
													() => {
														cardName =
															'editUser';
													}
												"
											></i>
											<i
												class="fa fa-times text-danger"
											></i>
										</div>
									</CListGroupItem>
								</CListGroup>
								<span v-else>Пользователи отсутствуют</span>
							</CAccordionBody>
						</CAccordionItem>
					</CAccordion>
					<CListGroup v-else>
						<CListGroupItem
							class="d-flex flex-row justify-content-between"
						>
							<span>Cras justo odio</span>
							<div>
								<i class="fa fa-times text-danger"></i>
							</div>
						</CListGroupItem>
						<CListGroupItem
							class="d-flex flex-row justify-content-between"
						>
							<span>Cras justo odio</span>
							<div>
								<i class="fa fa-times text-danger"></i>
							</div>
						</CListGroupItem>
						<CListGroupItem
							class="d-flex flex-row justify-content-between"
						>
							<span>Cras justo odio</span>
							<div>
								<i class="fa fa-times text-danger"></i>
							</div>
						</CListGroupItem>
					</CListGroup>
				</div>
				<div class="col-lg-6">
					<CCard v-if="role == 'user' && cardName == 'createGroup'">
						<CCardHeader class="fw-bold text-center"
							>Создание группы</CCardHeader
						>
						<CCardBody>
							<CForm>
								<CFormInput
									v-model="groupName"
									class="mb-2"
									type="text"
									label="Название"
									placeholder="Введите название группы"
								/>
								<div class="text-center">
									<CButton
										@click="createGroup"
										color="primary"
										>Создать</CButton
									>
								</div>
							</CForm>
						</CCardBody>
					</CCard>
					<CCard v-if="role == 'user' && cardName == 'editGroup'">
						<CCardHeader class="fw-bold text-center"
							>Редактирование группы</CCardHeader
						>
						<CCardBody>
							<CForm>
								<CFormInput
									v-model="groupName"
									class="mb-2"
									type="text"
									label="Название"
									placeholder="Введите название группы"
								/>
								<div class="text-center">
									<CButton @click="editGroup" color="primary"
										>Создать</CButton
									>
								</div>
							</CForm>
						</CCardBody>
					</CCard>
					<CCard v-if="cardName == 'createUser'">
						<CCardHeader class="fw-bold text-center"
							>Создание пользователя</CCardHeader
						>
						<CCardBody>
							<CForm>
								<CFormInput
									class="mb-2"
									type="text"
									label="Имя"
									placeholder="Введите имя пользователя"
								/>
								<CFormInput
									class="mb-2"
									type="text"
									label="Логин"
									placeholder="Введите логин пользователя"
								/>
								<CFormInput
									class="mb-2"
									type="password"
									label="Пароль"
									placeholder="Введите пароль пользователя"
								/>
								<CFormSelect
									v-if="role == 'user'"
									class="mb-2"
									label="Присваивание группы"
								>
									<option disabled selected>
										Выбор группы
									</option>
									<option value="1">401</option>
									<option value="2">402</option>
									<option value="3">403</option>
								</CFormSelect>
								<div class="text-center">
									<CButton color="primary">Создать</CButton>
								</div>
							</CForm>
						</CCardBody>
					</CCard>
					<CCard v-if="cardName == 'editUser'">
						<CCardHeader class="fw-bold text-center"
							>Редактирование пользователя</CCardHeader
						>
						<CCardBody>
							<CForm>
								<CFormInput
									class="mb-2"
									type="text"
									label="Имя"
									placeholder="Введите имя пользователя"
								/>
								<CFormInput
									class="mb-2"
									type="text"
									label="Логин"
									placeholder="Введите логин пользователя"
								/>
								<CFormInput
									class="mb-2"
									type="password"
									label="Пароль"
									placeholder="Введите пароль пользователя"
								/>
								<CFormSelect
									v-if="role == 'user'"
									class="mb-2"
									label="Присваивание группы"
								>
									<option disabled selected>
										Выбор группы
									</option>
									<option value="1">401</option>
									<option value="2">402</option>
									<option value="3">403</option>
								</CFormSelect>
								<div class="text-center">
									<CButton color="primary"
										>Редактировать</CButton
									>
								</div>
							</CForm>
						</CCardBody>
					</CCard>
				</div>
			</div>
		</CCardBody>
	</CCard>
</template>

<script>
export default {
	props: ["datasend", "showToast", "catchError"],
	data() {
		return {
			role: "user",
			groupList: null,
			groupName: "",
			cardName: null,
		};
	},
	mounted() {
		this.getList();
	},
	methods: {
		getList() {
			this.datasend("group", "GET", {})
				.then((res) => {
					if (res.data) {
						this.groupList = res.data;
						console.log(this.groupList);
					}
				})
				.catch((error) => {
					console.log(error);
				});
		},
		createGroup() {
			let form = new FormData();
			form.append("name", this.groupName);

			this.datasend("group", "POST", form)
				.then((res) => {
					if (res.success) {
						this.getList();
						this.showToast(res.success, res.message);
					} else if (res.errors) {
						this.catchError(res.errors);
					}
				})
				.catch((error) => {
					console.log(error);
				});
		},
	},
};
</script>
