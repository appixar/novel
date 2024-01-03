<?php
function formatTime($time)
{
    $parts = preg_split('/\s+/', $time);
    $totalHours = 0;

    // Se tiver duas partes, significa que temos horas acumuladas que excedem um dia
    if (count($parts) == 2) {
        $totalHours += intval($parts[0]) * 24; // Convertendo os dias em horas
        list($hours, $minutes) = explode(':', $parts[1]);
        $totalHours += intval($hours);
    } else {
        list($hours, $minutes) = explode(':', $time);
        $totalHours += intval($hours);
    }

    $days = floor($totalHours / 24);
    $remainingHours = $totalHours % 24;

    $formattedTime = '';
    if ($days > 0) $formattedTime .= $days . ' d ';
    if ($remainingHours > 0) $formattedTime .= $remainingHours . ' h ';
    $formattedTime .= intval($minutes) . ' min';

    return $formattedTime;
}
function getProcess($str)
{
    exec("ps aux | grep '$str'", $output);
    $process = [];
    $i = 0;
    foreach ($output as $line) {
        $line = preg_replace('/\s+/', ' ', $line);
        $parts = explode(" ", $line);
        if (count($parts) < 9) continue;
        $process[$i]['user'] = $parts[0];
        $process[$i]['pid'] = $parts[1];
        $process[$i]['cpu'] = $parts[2];
        $process[$i]['ram'] = $parts[3];
        $process[$i]['start'] = $parts[8];
        $process[$i]['time'] = formatTime($parts[9]); // Formatar o tempo de execução
        $process[$i]['cmd'] = implode(' ', array_slice($parts, 10));
        $i++;
    }
    return $process;
}
function getVM()
{
    // cpu
    exec("top -bn1 | grep 'Cpu(s)' | awk '{print $2+$4}'", $cpu);
    // ram
    exec("free -m | awk 'NR==2{printf \"%s/%sMB (%.2f%%)\\n\", $3,$2,$3*100/$2 }'", $ram);
    // disk
    exec("df -h | awk '\$NF==\"/\"{printf \"%d/%dGB (%s)\\n\", \$3,\$2,\$5}'", $disk);
    // uptime
    exec("uptime", $uptime);
    return [
        'cpu' => $cpu[0] . "%",
        'ram' => $ram[0],
        'disk' => $disk[0],
        'uptime' => $uptime[0]
    ];
}
$return = [];
foreach (@$_GET as $k => $v) $return[$k] = getProcess($v);
$return['vm'] = getVM();
echo json_encode($return);
exit;
