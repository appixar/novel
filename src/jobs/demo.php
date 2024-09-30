<?php
// START ARION FRAMEWORK
include __DIR__ . "/../../core/autoload.php";
new Xplend();

// START JOB
$job = new Job(true); // true = ignore path permissions
infinite:
$job->start();

// PROCESS
$my = new MyService();
$res = $my->query("SELECT * FROM dk_wa_env WHERE env_status = 1");
foreach ($res as $r) {
}

// LOOP
sleep(1);
goto infinite;
$job->end();