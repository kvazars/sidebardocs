# SidebarDocs

Внутреннее веб-приложение на Laravel 11 и Vue 3 для работы с деревом документов, редактором контента и тестами.

## Что умеет проект

- Дерево папок и документов с правами доступа
- Просмотр и редактирование документов через Editor.js
- Импорт документов из `docx`, `pptx`, `pdf`
- Экспорт документов в Word
- Управление пользователями, группами и доступами
- Создание, прохождение и просмотр результатов тестов
- Публичные и ссылочные документы по slug

## Технологии

- Backend: Laravel 11, Sanctum, Eloquent
- Frontend: Vue 3, Vue Router, Pinia, CoreUI, Bootstrap 5
- Сборка: Vite 5
- Работа с файлами: `mammoth`, `jszip`, `pdfjs-dist`, `docx`, `intervention/image`

## Требования

- PHP `^8.2`
- Composer
- Node.js 18+ и npm
- SQLite или другая поддерживаемая Laravel БД

## Быстрый запуск

1. Установить PHP-зависимости:

```bash
composer install
```

2. Установить frontend-зависимости:

```bash
npm install
```

3. Создать `.env`:

```bash
cp .env.example .env
php artisan key:generate
```

4. Подготовить базу данных:

```bash
php artisan migrate
php artisan db:seed
```

5. Убедиться, что есть символическая ссылка на публичное хранилище:

```bash
php artisan storage:link
```

6. Запустить backend и frontend:

```bash
php artisan serve
npm run dev
```

По умолчанию Vite работает на `http://localhost:3001`, а Laravel API на `http://127.0.0.1:8000`.

## Сборка production

```bash
npm run build
```

## Основные каталоги

- [app/Http/Controllers](/Users/macos/projects/sidebardocs/app/Http/Controllers) — API-контроллеры
- [resources/js/components](/Users/macos/projects/sidebardocs/resources/js/components) — крупные Vue-компоненты интерфейса
- [resources/js/views](/Users/macos/projects/sidebardocs/resources/js/views) — страницы
- [resources/js/utils](/Users/macos/projects/sidebardocs/resources/js/utils) — импорт/экспорт документов
- [routes/api.php](/Users/macos/projects/sidebardocs/routes/api.php) — API-маршруты
- [database/migrations](/Users/macos/projects/sidebardocs/database/migrations) — структура БД

## Права доступа

- `admin` — полный доступ, включая управление пользователями и группами
- `ceo` — работа со своими материалами и административными разделами, где это разрешено
- `user` — доступ к разрешённым документам и тестам

Отдельно поддерживаются:

- публичные документы
- документы только по ссылке
- документы с групповыми ограничениями

## Импорт и экспорт

Импорт реализован на клиенте и доступен из редактора документа:

- `DOCX`
- `PPTX`
- `PDF`

Экспорт документа поддерживает сохранение в Word / DOCX с попыткой максимально близко повторить структуру содержимого.

## Текущее состояние проекта

- Проект активно дорабатывается
- Ключевые зоны сложности: редактор документов, импорт/экспорт и модуль тестирования
- Автоматическое тестовое покрытие пока не добавлено

## Полезные команды

```bash
php artisan route:list
php artisan migrate:fresh --seed
php artisan cache:clear
php artisan route:clear
npm run build
```

## Замечания по сопровождению

- В проекте много крупной клиентской логики, поэтому перед большими изменениями стоит проверять `npm run build`
- Для прав доступа лучше проверять как авторизованные, так и гостевые сценарии
- При изменениях в импорте документов полезно перепроверять реальные файлы `docx/pptx/pdf`
