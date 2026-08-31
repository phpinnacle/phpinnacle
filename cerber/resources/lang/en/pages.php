<?php

return [
    'login' => [
        'as' => 'Login as:',
    ],
    'profile' => [
        'label' => 'Profile',
        'sections' => [
            'general' => 'General',
            'password' => 'Password',
            'two_factor' => 'Two-Factor Authentication',
            'oauth_accounts' => 'Connected Accounts',
        ],
        'descriptions' => [
            'general' => 'Configure your profile',
            'password' => 'Update your password',
            'two_factor' => 'Manage your two-factor authentication settings',
            'oauth_accounts' => 'Manage your connected social accounts',
        ],
        'actions' => [
            'update_profile' => 'Update Profile',
            'change_password' => 'Change Password',
        ],
        'fields' => [
            'name' => 'Name',
            'email' => 'Email',
            'password' => 'Password',
            'confirmation' => 'Password Confirmation',
        ],
    ],
];
