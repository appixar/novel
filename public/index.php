<?php
// START ARION
include __DIR__ . "/../core/autoload.php";
$app = new Arion();

// START API SERVER
#$app->load("api-server", "modules"); # already loaded in app.yml AUTOLOAD
//new Api();

// RENDER PAGE
$app->build();
