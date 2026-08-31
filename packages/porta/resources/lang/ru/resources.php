<?php

return [
    'integration' => [
        'label' => 'Интеграции',
        'group' => 'Настройки',
        'actions' => [
            'create' => 'Добавить',
            'update' => 'Редактировать',
            'delete' => 'Удалить',
            'clone' => 'Клонировать',
            'test' => 'Проверить',
        ],
        'empty' => [
            'heading' => 'Нет интеграций',
            'description' => 'Создайте первую интеграцию, чтобы начать автоматизацию задач.',
        ],
        'fields' => [
            'title' => 'Название',
            'type' => 'Тип',
            'format' => 'Format',
            'auth' => 'Аутентификация',
            'auth_key' => 'Ключ аутентификации',
            'auth_secret' => 'Секрет аутентификации',
            'response_kind' => 'Тип ответа',
            'response_code' => 'Код ответа',
            'transforms' => 'Преобразования',
            'transforms_type' => 'Тип',
            'transforms_path' => 'Путь',
            'transforms_config' => 'Конфигурация',
        ],
        'filters' => [
            'type' => 'Тип',
            'format' => 'Format',
            'auth' => 'Аутентификация',
            'response_kind' => 'Ответ',
        ],
        'pages' => [
            'list' => 'Интеграции',
            'create' => 'Создать интеграцию',
            'edit' => 'Редактировать интеграцию',
            'webhooks' => 'Вебхуки',
        ],
        'sections' => [
            'general' => [
                'heading' => 'Общее',
            ],
            'auth' => [
                'heading' => 'Аутентификация',
            ],
            'transforms' => [
                'heading' => 'Преобразования',
            ],
        ],
    ],
    'webhook' => [
        'label' => 'Вебхуки',
        'group' => 'Настройки',
        'actions' => [
            'view' => 'Просмотр',
            'create' => 'Добавить',
            'update' => 'Редактировать',
            'delete' => 'Удалить',
            'clone' => 'Клонировать',
            'retry' => 'Повторить',
        ],
        'empty' => [
            'heading' => 'Нет вебхуков',
            'description' => 'Создайте первый вебхук, чтобы начать получать события.',
        ],
        'fields' => [
            'id' => 'ID',
            'origin' => 'URL источника',
            'status' => 'Статус',
            'payload' => 'Полезная нагрузка',
            'headers' => 'Заголовки',
            'error' => 'Подробности ошибки',
            'error_type' => 'Тип ошибки',
            'error_file' => 'Файл ошибки',
            'error_message' => 'Сообщение об ошибке',
            'error_validation' => 'Ошибки валидации',
            'processed_at' => 'Обработано',
        ],
        'filters' => [
            'status' => 'Статус',
            'processed_at' => 'Обработано',
        ],
        'pages' => [
            'list' => 'Вебхуки',
            'create' => 'Создать вебхук',
            'edit' => 'Редактировать вебхук',
        ],
        'sections' => [
            'general' => [
                'heading' => 'Общее',
            ],
            'error' => [
                'heading' => 'Подробности ошибки',
            ],
        ],
        'tabs' => [
            'general' => 'Общее',
            'error' => 'Подробности ошибки',
        ],
    ],
];
