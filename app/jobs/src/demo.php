<?php
// START ARION FRAMEWORK
include __DIR__ . "/../../../arion/autoload.php";
new arion();

// START JOB
$job = new job(true); // true = ignore path permissions
$job->start();

// LOG
#$job->say('CRON START HEADER', true, true);
#$job->say(date('Y-m-d H:i:s'), false, true, 'green');

// SET LAST ID / GET LAST ID
#$job->set_last_id($user_id);
#echo $job->get_last_id();

// LOOP IN DATABASES
$myShared = new my();
$com = $myShared->query('SELECT * FROM qmz_company WHERE com_status = 1');

for ($x = 0; $x < count($com); $x++) {
    $my = new my(['id' => 'dynamic', 'wildcard' => $com[$x]['com_code']]);
    next_company:
}

$job->end();