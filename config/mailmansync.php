<?php
$lists = null;
if (env('MAILMAN_LISTS')) {
    $lists = json_decode(env('MAILMAN_LISTS'));
}
return [
    'url' => env('MAILMAN_ADMIN_URL', 'http://localhost/mailman'),
    'mock' => env('MAILMAN_MOCK', false),
    'lists' => $lists ?: [
        'examplelist' => [
            'password' => 'supersecure',
        ],
    ],
];
