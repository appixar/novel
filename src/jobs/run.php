<?php
// START ARION FRAMEWORK
include __DIR__ . "/../../core/autoload.php";
new arion();
job::unlock(); // remove false @lock's

// crontab -e: * * * * * php <path>/src/jobs/run.php
// chmod: sudo chmod -R 777 ./src
// view all php running: ps -C php -f
// remove all php: pkill -f ".php"

// START JOBS
job::schedule('process-pending-messages', 'every 1min');
