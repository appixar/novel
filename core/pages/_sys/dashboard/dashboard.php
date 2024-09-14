<?php
if (@!$_SESSION['_sys']['auth']) {
    header("Location: ./auth");
    exit;
}
$config = [
    'jobs' => [
        'title' => 'project jobs',
        'cmd' => "{$_SERVER['HTTP_HOST']}/src/jobs/"
    ],
    'node' => [
        'title' => 'bots',
        'cmd' => 'node'
    ]
];

// check autoplay
$autoplay = true;
if (file_exists(Novel::DIR_ROOT . '/src/jobs/stop')) $autoplay = false;

// process to find by ajax
$process_to_find = [];
foreach ($config as $k => $v) $process_to_find[$k] = $v['cmd'];
$process_to_find = http_build_query($process_to_find);
