<?php


print_r(getDirContents('.'));
// get .env data
$_ENV = parse_ini_file('.env', true);
print_r($_ENV);
