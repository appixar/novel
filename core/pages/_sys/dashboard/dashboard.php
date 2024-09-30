<?php
if (@!$_SESSION['_sys']['auth']) {
    header("Location: ./auth");
    exit;
}
$jobsConfig = [
    'jobs' => [
        'title' => 'project jobs',
        'cmd' => "{$_SERVER['HTTP_HOST']}/src/jobs/"
    ]
];

if (@$_APP['MONITOR']['CUSTOM_JOBS']) {
    $customJobs = $_APP['MONITOR']['CUSTOM_JOBS'];
    foreach ($customJobs as $k => $v) {
        $jobsConfig[$k] = [
            'title' => $k,
            'cmd' => $v
        ];
    }
}

// check autoplay
$autoplay = true;
if (file_exists(Xplend::DIR_ROOT . '/src/jobs/stop')) $autoplay = false;

// process to find by ajax
$process_to_find = [];
foreach ($jobsConfig as $k => $v) $process_to_find[$k] = $v['cmd'];
$process_to_find = http_build_query($process_to_find);
