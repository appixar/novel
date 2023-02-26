<?php
/*
 ______     ______     __     ______     __   __    
/\  __ \   /\  == \   /\ \   /\  __ \   /\ '-.\ \   
\ \  __ \  \ \  __<   \ \ \  \ \ \/\ \  \ \ \-.  \  
 \ \_\ \_\  \ \_\ \_\  \ \_\  \ \_____\  \ \_\\'\_\ 
  \/_/\/_/   \/_/ /_/   \/_/   \/_____/   \/_/ \/_/ 

  P H P   F R A M E W O R K
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CUSTOM SETTINGS
date_default_timezone_set('America/Sao_Paulo');

// GET MANIFEST DATA
$_MAN = json_decode(file_get_contents(__DIR__ . '/../manifest.json'), true);

// INCLUDES
include __DIR__ . '/src/Arion.php';
include __DIR__ . '/src/Autoload.php';
#include __DIR__ . '/src/arion.lib.php';
#include __DIR__ . '/src/arion.module.php';
include __DIR__ . '/src/UrlFormatter.php';
include __DIR__ . '/src/Builder.php';
#include __DIR__ . '/src/arion.build.sort.php';
#include __DIR__ . '/src/arion.yml.php';
include __DIR__ . '/src/Debug.php';
#include __DIR__ . '/src/arion.wizard.php';
#include __DIR__ . '/src/arion.database.schema.php';
include __DIR__ . '/src/Mason.php';
include __DIR__ . '/src/Job.php';
#include __DIR__ . '/src/arion.api-client.php';
