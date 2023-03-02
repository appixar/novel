<?php
// START ARION
include __DIR__ . "/../core/autoload.php";
$app = new Arion();

// START API SERVER
#$app->load("modules/api"); # already loaded in app.yml AUTOLOAD
#new Api();

// RENDER PAGE
$app->build();
