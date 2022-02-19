<?php
// Send SMS
$route = [
    'module' => 'sms',
    'auth' => ['required' => false]
];
http::route($route);
