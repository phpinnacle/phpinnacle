<?php

return [
    'integration' => [
        'label' => 'Integrations',
        'group' => 'Settings',
        'actions' => [
            'create' => 'Add',
            'update' => 'Edit',
            'delete' => 'Delete',
            'clone' => 'Clone',
            'test' => 'Test',
        ],
        'empty' => [
            'heading' => 'No Integrations',
            'description' => 'Create your first integration to start automating tasks.',
        ],
        'fields' => [
            'title' => 'Title',
            'type' => 'Type',
            'format' => 'Format',
            'auth' => 'Authentication',
            'auth_key' => 'Auth Key',
            'auth_secret' => 'Auth Secret',
            'response_kind' => 'Response Kind',
            'response_code' => 'Response Code',
            'transforms' => 'Transforms',
            'transforms_type' => 'Type',
            'transforms_path' => 'Path',
            'transforms_config' => 'Config',
        ],
        'filters' => [
            'type' => 'Type',
            'format' => 'Format',
            'auth' => 'Authentication',
            'response_kind' => 'Response',
        ],
        'pages' => [
            'list' => 'Integrations',
            'create' => 'Create Integration',
            'edit' => 'Edit Integration',
            'webhooks' => 'Webhooks',
        ],
        'sections' => [
            'general' => [
                'heading' => 'General',
            ],
            'auth' => [
                'heading' => 'Authentication',
            ],
            'transforms' => [
                'heading' => 'Transforms',
            ],
        ],
    ],
    'webhook' => [
        'label' => 'Webhooks',
        'group' => 'Settings',
        'actions' => [
            'view' => 'View',
            'create' => 'Add',
            'update' => 'Edit',
            'delete' => 'Delete',
            'clone' => 'Clone',
            'retry' => 'Retry',
        ],
        'empty' => [
            'heading' => 'No Webhooks',
            'description' => 'Create your first webhook to start receiving events.',
        ],
        'fields' => [
            'id' => 'ID',
            'origin' => 'Origin URL',
            'status' => 'Status',
            'payload' => 'Payload',
            'headers' => 'Headers',
            'error' => 'Error Details',
            'error_type' => 'Error Type',
            'error_file' => 'Error File',
            'error_message' => 'Error Message',
            'error_validation' => 'Validation Errors',
            'processed_at' => 'Processed At',
        ],
        'filters' => [
            'status' => 'Status',
            'processed_at' => 'Processed At',
        ],
        'pages' => [
            'list' => 'Webhooks',
            'create' => 'Create Webhook',
            'edit' => 'Edit Webhook',
        ],
        'sections' => [
            'general' => [
                'heading' => 'General',
            ],
            'error' => [
                'heading' => 'Error Details',
            ],
        ],
        'tabs' => [
            'general' => 'General',
            'error' => 'Error Details',
        ],
    ],
];
