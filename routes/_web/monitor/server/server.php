<?php
// Call class
include __DIR__ . '/server.class.php';

// Response data
$data = array();

// Server info
$os = new Server();
$os->servers = [
    'qmz_db' => [
        'host' => 'db.qmoleza.com',
        'port' => 22000,
        'user' => 'appixar',
        'pass' => '4pp1x4r.777@---'
    ],
    'qmz_www' => [
        'host' => 'www.qmoleza.com',
        'port' => 22000,
        'user' => 'appixar',
        'pass' => '4pp1x4r.777@---'
    ],
    'qmz_app' => [
        'host' => 'app.qmoleza.com',
        'port' => 22000,
        'user' => 'appixar',
        'pass' => '4pp1x4r.777@---'
    ],
    'appixar' => [
        'host' => 'appixar.com',
        'port' => 22000,
        'user' => 'appixar',
        'pass' => '4pp1x4r.777@---'
    ]
];
// Process data
foreach ($os->servers as $k => $v) {
    $os->connect($k);
    $data[$k] = [
        'host' => $v['host'],
        'hostname' => $os->getHostname(),
        'cores' => $os->getSystemCores(),
        'disk' => $os->getDisk() . "%",
        'cpu' => $os->getCPU() . "%",
        'ram' => $os->getRAM2() . "%",
        'uptime' => $os->getUptime()
    ];
}
echo json_encode($data);
