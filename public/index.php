<?php
// START ARION
include __DIR__ . "/../core/autoload.php";
$app = new Arion();

// START API SERVER
//$app->load("api-server", "modules");
//new ApiServer();

// RENDER PAGE
$app->build();
