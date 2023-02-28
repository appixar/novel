<?php
$roles = [
    // FREE BY PASS (STILL CAN BE RESTRICED IN ROUTE CLASS)
    'free' => [
        'user',
        'user/auth',
        'user/verifysend',
        'user/verifycheck'
    ],
    // ALL LOGGED USERS
    'private' => [
        'user/blau.get',
    ],
    // SPECIFIC GROUP ONLY (ADMIN)
    'restricted' => [
        '_user/blau.post' => '*',
        'adm/*' => 'adminGroup',
    ]
    // ANY OTHER ROUTE NEEDS A SIMPLE USER GROUP
    // ...
];
/*
$license = [
    // FREE BY PASS
    'free' => [
        'user:mailGet',
        'user:domainGet'
    ],
    // ADMIN GROUP ONLY
    'restricted' => [
        'display:get' => ''
    ]
    // ANY OTHER ROUTE NEEDS A SIMPLE USER GROUP
    // ...
];
*/