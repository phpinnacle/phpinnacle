<?php

return [
    'user' => [
        'label' => 'Users',
        'group' => 'Access',
        'actions' => [
            'create' => 'Create',
            'delete' => 'Delete',
            'resend_verification' => 'Resend Verification',
            'mark_as_verified' => 'Mark as Verified',
            'drop_verification' => 'Drop Verification',
            'reset_password' => 'Reset Password',
            'impersonate' => 'Impersonate',
            'archive' => 'Archive',
            'restore' => 'Restore',
            'status' => 'Status',
        ],
        'fields' => [
            'name' => 'Name',
            'email' => 'E-Mail',
            'email_verified_at' => 'Verified E-Mail',
            'password' => 'Password',
            'status' => 'Status',
            'avatar' => 'Avatar',
            'roles' => 'Roles',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ],
        'pages' => [
            'list' => 'Users',
            'create' => 'Register User',
            'edit' => 'Edit User',
        ],
        'sections' => [
            'general' => 'General',
            'roles' => 'Roles',
        ],
    ],
    'role' => [
        'label' => 'Roles',
        'group' => 'Access',
        'actions' => [
            'create' => 'Create',
            'delete' => 'Delete',
        ],
        'fields' => [
            'name' => 'Name',
            'description' => 'Description',
            'is_active' => 'Active',
            'is_system' => 'System',
            'users' => 'Users',
            'permissions' => 'Permissions',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ],
        'filters' => [
            'active' => 'Is Active',
            'users' => 'Has Users',
            'permissions' => 'Has Permissions',
            'created_at' => 'Created At',
        ],
        'pages' => [
            'list' => 'Roles',
            'create' => 'Create Role',
            'edit' => 'Edit Role',
        ],
        'sections' => [
            'general' => 'General',
            'resources' => 'Resources',
            'widgets' => 'Widgets',
            'pages' => 'Pages',
        ],
    ],
    'provider' => [
        'label' => 'Auth Providers',
        'group' => 'Access',
        'actions' => [
            'create' => 'Create Provider',
            'delete' => 'Delete',
            'test_connection' => 'Test Connection',
        ],
        'empty' => [
            'heading' => 'No providers',
            'description' => 'Create your first Auth provider to enable external service login',
        ],
        'fields' => [
            'name' => 'Name',
            'type' => 'Type',
            'is_active' => 'Active',
            'accounts' => 'Linked Accounts',
            'client_id' => 'Client ID',
            'client_secret' => 'Client Secret',
            'redirect' => 'Redirect URL',
            'scopes' => 'Scopes',
            'additional' => 'Additional Parameters',
        ],
        'pages' => [
            'list' => 'Auth Providers',
            'create' => 'Create Provider',
            'edit' => 'Edit Provider',
        ],
        'placeholders' => [
            'scopes' => 'Enter scope and press Enter',
        ],
        'sections' => [
            'general' => 'General Information',
            'configuration' => 'Auth Configuration',
        ],
    ],
    'accounts' => [
        'linked' => 'Linked',
        'not_linked' => 'Not linked',
        'actions' => [
            'link' => 'Link',
            'unlink' => 'Unlink',
        ],
    ],
];
