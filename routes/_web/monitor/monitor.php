<?php
// apt-get install php7.0-ssh2
$data = [
    'ws' => [
        'title' => 'qmoleza-www',
        'cpu' => '99%',
        'info' => 'RAM 29% SSD 31%',
        'uptime' => '19 days, 8 hours, 13 min',
        //
        ':class' => 'c0'
    ],
    'teste' => 'blau'
];
echo json_encode($data);
