<?php
$lists = null;
if (env('MAILMAN_LISTS')) {
    $lists = json_decode(env('MAILMAN_LISTS'), true);
}
return [
    'url' => env('MAILMAN_ADMIN_URL', 'http://localhost:8001/3.1'),
    'mock' => env('MAILMAN_MOCK', false),
    'lists' => $lists ?: [
        'list.example.com' => [
            'user' => 'restadmin',
            'password' => 'supersecure',
        ],
    ],
];
