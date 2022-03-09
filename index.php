<?php
// CUSTOM SETTINGS
date_default_timezone_set('America/Sao_Paulo');

// START ARION
include __DIR__ . "/core/autoload.php";
$arion = new arion();

// START API SERVER
new apiServer();

// RENDER PAGE
$arion->build();
