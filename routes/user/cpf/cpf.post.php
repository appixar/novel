<?php
// Check unique CPF
$route = [
    'module' => 'user:cpf',
    'auth' => ['required' => false]
];
http::route($route);
