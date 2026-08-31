<?php

return [
    'user' => [
        'label' => 'Пользователи',
        'group' => 'Доступ',
        'actions' => [
            'create' => 'Создать пользователя',
            'delete' => 'Удалить',
            'resend_verification' => 'Повторно отправить подтверждение',
            'mark_as_verified' => 'Отметить как подтвержденного',
            'drop_verification' => 'Снять подтверждение',
            'reset_password' => 'Сбросить пароль',
            'impersonate' => 'Войти от имени',
            'archive' => 'Архивировать',
            'restore' => 'Восстановить',
            'status' => 'Статус',
        ],
        'fields' => [
            'name' => 'Имя',
            'email' => 'Эл. почта',
            'email_verified_at' => 'Подтвержденная эл. почта',
            'password' => 'Пароль',
            'status' => 'Статус',
            'avatar' => 'Аватар',
            'roles' => 'Роли',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ],
        'pages' => [
            'list' => 'Пользователи',
            'create' => 'Зарегистрировать пользователя',
            'edit' => 'Редактировать пользователя',
        ],
        'sections' => [
            'general' => 'Общее',
            'roles' => 'Роли',
        ],
    ],
    'role' => [
        'label' => 'Роли',
        'group' => 'Доступ',
        'actions' => [
            'create' => 'Создать роль',
            'delete' => 'Удалить',
        ],
        'fields' => [
            'name' => 'Имя',
            'description' => 'Описание',
            'is_active' => 'Активно',
            'is_system' => 'Системная',
            'users' => 'Пользователи',
            'permissions' => 'Разрешения',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ],
        'filters' => [
            'active' => 'Активно',
            'users' => 'Есть пользователи',
            'permissions' => 'Есть разрешения',
            'created_at' => 'Создано',
        ],
        'pages' => [
            'list' => 'Роли',
            'create' => 'Создать роль',
            'edit' => 'Редактировать роль',
        ],
        'sections' => [
            'general' => 'Общее',
            'resources' => 'Ресурсы',
            'widgets' => 'Виджеты',
            'pages' => 'Страницы',
        ],
    ],
    'provider' => [
        'label' => 'Аутентификация',
        'group' => 'Доступ',
        'actions' => [
            'create' => 'Создать провайдера',
            'delete' => 'Удалить',
            'test_connection' => 'Проверить подключение',
        ],
        'empty' => [
            'heading' => 'Нет провайдеров',
            'description' => 'Создайте первого Auth провайдера для входа через внешние сервисы',
        ],
        'fields' => [
            'name' => 'Название',
            'type' => 'Тип',
            'is_active' => 'Активный',
            'accounts' => 'Аккаунты',
            'client_id' => 'Client ID',
            'client_secret' => 'Client Secret',
            'redirect' => 'Redirect URL',
            'scopes' => 'Scopes',
            'additional' => 'Дополнительные параметры',
        ],
        'pages' => [
            'list' => 'Провайдеры',
            'create' => 'Создать провайдера',
            'edit' => 'Редактировать провайдера',
        ],
        'placeholders' => [
            'scopes' => 'Введите scope и нажмите Enter',
        ],
        'sections' => [
            'general' => 'Основная информация',
            'configuration' => 'Настройки OAuth',
        ],
    ],
    'accounts' => [
        'linked' => 'Подключен',
        'not_linked' => 'Не подключен',
        'actions' => [
            'link' => 'Подключить',
            'unlink' => 'Отключить',
        ],
    ],
];
