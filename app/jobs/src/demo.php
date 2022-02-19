<?php
// START ARION FRAMEWORK
include __DIR__ . "/../../../arion/autoload.php";
new arion();

// START JOB
$job = new job(true); // true = ignore path permissions
$job->start();

// LOOP IN DATABASES
$myShared = new my();
$com = $myShared->query('SELECT * FROM qmz_company WHERE com_status = 1');

for ($x = 0; $x < count($com); $x++) {
    $my = new my(['id' => 'dynamic', 'wildcard' => $com[$x]['com_code']]);
    next_company:
}
