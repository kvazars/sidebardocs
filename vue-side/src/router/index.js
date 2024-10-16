import { h, resolveComponent } from 'vue'
import { createRouter, createWebHashHistory } from 'vue-router'

import DefaultLayout from '@/layouts/DefaultLayout'

const routes = [
  {
    path: '/',
    name: 'Home',
    meta: { title: 'Главная' },
    component: DefaultLayout,
    children: [
      {
        path: '/folder/:id/edit',
        name: 'EditFolder',
        meta: { title: 'Редактирование папки' },
        component: () => import('@/views/EditFolder.vue'),
        props: true,
      },
      {
        path: '/folder/new/:parent',
        name: 'NewFolder',
        meta: { title: 'Создание папки' },
        component: () => import('@/views/EditFolder.vue'),
        props: true,
      },
      {
        path: '/files/:id',
        name: 'ShowFile',
        meta: { title: 'Просмотр файла' },
        component: () => import('@/views/ShowFile.vue'),
        props: true,
      },
      {
        path: '/files/:id/edit',
        name: 'EditFile',
        meta: { title: 'Редактирование документа' },
        component: () => import('@/views/editFile.vue'),
        props: true,
      },
      {
        path: '/files/new/:parent',
        name: 'CreateFile',        
        meta: { title: 'Создание документа' },
        component: () => import('@/views/editFile.vue'),
        props: true,
      },
      {
        path: '/:catchAll(.*)',
        name: 'NotFound',
        meta: { title: 'Ресурс не найден' },
        component: () => import('@/views/pages/Page404'),
      },
      {
        path: '500',
        name: 'Page500',
        component: () => import('@/views/pages/Page500'),
      },
      {
        path: 'login',
        name: 'Login',
        component: () => import('@/views/pages/Login'),
      },
    ],
  },
 
]

const router = createRouter({
  history: createWebHashHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior() {
    // always scroll to top
    return { top: 0 }
  },
})

export default router
