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

// GET .ENV DATA
$_ENV = parse_ini_file(__DIR__ . '/../.env', true);
define('ARION_VERSION', "Arion PHP Light Framework {$_ENV['arion']['version']}");

// INCLUDES
include __DIR__ . '/src/arion.php';
include __DIR__ . '/src/arion.lib.php';
include __DIR__ . '/src/arion.module.php';
include __DIR__ . '/src/arion.www.php';
include __DIR__ . '/src/arion.build.php';
include __DIR__ . '/src/arion.build.sort.php';
include __DIR__ . '/src/arion.yml.php';
include __DIR__ . '/src/arion.debug.php';
include __DIR__ . '/src/arion.wizard.php';
include __DIR__ . '/src/arion.database.schema.php';
include __DIR__ . '/src/arion.cmd.php';
include __DIR__ . '/src/arion.job.php';
include __DIR__ . '/src/arion.api-server.php';
include __DIR__ . '/src/arion.api-server.http.php';
include __DIR__ . '/src/arion.api-client.php';
