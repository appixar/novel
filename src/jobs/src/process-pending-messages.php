<?php
// START ARION FRAMEWORK
include __DIR__ . "/../../../core/autoload.php";
new arion();

// START JOB
$job = new Job(true); // true = ignore path permissions
$job->start();

// LOG
$job->say('PROCESS PENDING MESSAGES', true, true);

// RUN
$controller = new ResponseController();

#while (true) {
$res = $controller->processPendingMessages();
#print_r($res);
if (!empty($res)) {
    $job->say(date('Y-m-d H:i:s'), false, true, 'green');
    $job->say(json_encode($res), false, true);
}
#sleep(3);
#}

$job->end();
