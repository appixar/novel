<?php
if (@!$_ENV['SYS_PASS']) exit;
if (@!$_APP['MONITOR'] or @!$_APP['MONITOR']['ENABLED']) exit;
$pass = @$_POST['pass'];
if (!$pass) exit;
if ($pass === $_ENV['SYS_PASS']) {
    $_SESSION['_sys']['auth'] = 1;
    header("Location: /_sys/dashboard");
    exit;
}
$_SESSION['cb'][] = [
    "type" => 'warning',
    "text" => "Incorrect password"
];
back();

