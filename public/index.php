<?php
// START ARION
include __DIR__ . "/../core/autoload.php";
$arion = new arion();

// START API SERVER
new apiServer();

// RENDER PAGE
$arion->build();
