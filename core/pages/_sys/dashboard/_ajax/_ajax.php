<?php
if (@!$_SESSION['_sys']['auth']) exit;

function getProcess($str)
{
    exec("ps aux | grep '$str'", $output);
    $process = [];
    $i = 0;
    foreach ($output as $line) {
        $line = preg_replace('/\s+/', ' ', $line);
        $parts = explode(" ", $line);
        // Verifique se a linha tem partes suficientes para ser um processo válido
        if (count($parts) < 9) continue;
        $line = preg_replace('/\s+/', ' ', $line);
        $parts = explode(" ", $line);
        $process[$i]['user'] = $parts[0];
        $process[$i]['pid'] = $parts[1];
        $process[$i]['cpu'] = $parts[2];
        $process[$i]['ram'] = $parts[3];
        $process[$i]['start'] = $parts[8];
        // Junte todas as partes do comando a partir da nona parte
        $process[$i]['cmd'] = implode(' ', array_slice($parts, 10));
        $i++;
    }
    // remove 2 first process (ps... + grep...)
    //array_shift($process);
    //array_shift($process);
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
