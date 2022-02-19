<?php
// Send SMS
$route = [
    'module' => 'sms:check',
    'auth' => ['required' => false]
];
http::route($route);