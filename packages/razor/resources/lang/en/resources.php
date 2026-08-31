<?php

return [
    'document' => [
        'actions' => [
            'new' => 'Create',
            'create' => 'Create :label',
            'delete' => 'Delete',
            'edit' => 'Edit',
        ],
        'empty' => [
            'heading' => 'No Documents',
            'description' => 'Add a new one to get started.',
        ],
        'fields' => [
            'number' => 'Number',
            'parent' => 'Parent',
            'holder' => 'Holder',
            'entity' => 'Entity',
            'template' => 'Template',
            'section' => 'Section',
            'content' => 'Content',
            'issued_at' => 'Issued At',
            'signed_at' => 'Signed At',
            'expires_at' => 'Expires At',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ],
        'pages' => [
            'manage' => 'Manage Documents',
            'list' => 'Documents',
        ],
        'modal' => [
            'create' => [
                'heading' => 'Create Document',
                'description' => 'Create a new document.',
            ],
            'edit' => [
                'heading' => 'Edit Document',
                'description' => 'Edit the document.',
            ],
        ],
    ],
    'template' => [
        'label' => 'Templates',
        'group' => 'Settings',
        'actions' => [
            'create' => 'Add',
            'delete' => 'Delete',
            'history' => 'History',
            'preview' => 'Preview',
        ],
        'empty' => [
            'heading' => 'No Templates',
            'description' => 'Add a new one to get started.',
        ],
        'fields' => [
            'name' => 'Name',
            'numeration' => 'Numeration',
            'section' => 'Section',
            'is_active' => 'Active',
            'is_default' => 'Default',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ],
        'filters' => [
            'active' => 'Active',
            'section' => 'Section',
            'created_at' => 'Created At',
        ],
        'history' => [
            'version' => 'Version :version',
        ],
        'pages' => [
            'list' => 'Templates',
            'create' => 'Create Template',
            'edit' => 'Edit Template',
        ],
        'preview' => [
            'record' => 'Record',
        ],
        'sections' => [
            'general' => 'General',
            'template' => 'Template',
        ],
    ],
];
