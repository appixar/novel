<?php
// > composer require phpseclib/phpseclib
include __DIR__ . '/../../../../vendor/autoload.php';

class Server
{
    public $servers = array();
    private $conn = false;
    public function connect($serverId)
    {
        $conf = $this->servers[$serverId];
        $this->conn = new \phpseclib3\Net\SSH2($conf['host'], $conf['port']);
        if (!$this->conn->login($conf['user'], $conf['pass'])) die('Login Failed');
    }
    public function getDisk()
    {
        $cmd = "df -h";
        if ($this->conn) $res = $this->conn->exec($cmd);
        else $res = shell_exec($cmd);
        $res = explode('/dev/vda1', $res)[1];
        $res = explode('%', $res)[0];
        $res = explode(' ', $res);
        $res = $res[count($res) - 1];
        return $res;
    }
    public function getHostname()
    {
        $cmd = "hostname";
        if ($this->conn) $res = $this->conn->exec($cmd);
        else $res = shell_exec($cmd);
        $res = explode(PHP_EOL, $res)[0];
        return $res;
    }
    public function getSystemCores()
    {
        $cmd = "cat /proc/cpuinfo | grep processor | wc -l";
        if ($this->conn) $res = $this->conn->exec($cmd);
        else $res = shell_exec($cmd);
        $res = explode(PHP_EOL, $res)[0];
        return $res;
    }
    public function getUptime()
    {
        //$cmd = "uptime | awk -F'( |,|:)+' '{if ($7==\"min\") m=$6; else {if ($7~/^day/) {d=$6;h=$8;m=$9} else {h=$6;m=$7}}} {print d+0,\"days,\",h+0,\"h,\",m+0,\"min\"}'";
        $cmd = "uptime | awk -F'( |,|:)+' '{if ($7==\"min\") m=$6; else {if ($7~/^day/) {d=$6;h=$8;m=$9} else {h=$6;m=$7}}} {print d+0,\"days\",h+0,\"h\",m+0,\"m\"}'";
        if ($this->conn) $res = $this->conn->exec($cmd);
        else $res = shell_exec($cmd);
        $res = explode(PHP_EOL, $res)[0];
        return $res;
    }
    function getRAM()
    {
        if ($this->conn) $free = $this->conn->exec('free');
        else $free = shell_exec('free');
        //
        $free = (string)trim($free);
        $free_arr = explode("\n", $free);
        $mem = explode(" ", $free_arr[1]);
        $mem = array_filter($mem, 'strlen');
        $mem = array_merge($mem); // $mem[1]=total
        //
        $bmem = explode(" ", $free_arr[2]);
        $bmem = array_filter($bmem, 'strlen');
        $bmem = array_merge($bmem);
        $b_free = $mem[1] - $bmem[2]; // $bmem=buffer livre
        $memory_usage = 100 - ($b_free / $mem[1] * 100);
        $memory_usage = floatval(number_format($memory_usage, 2, ".", ""));
        return $memory_usage;
    }
    function getRAM2()
    {
        if ($this->conn) $free = $this->conn->exec('free');
        else $free = shell_exec('free');

        $free = (string)trim($free);
        $free_arr = explode("\n", $free);
        $mem = explode(" ", $free_arr[1]);
        $mem = array_filter($mem);
        $mem = array_merge($mem);
        $memory_usage = $mem[2] / $mem[1] * 100;
        $memory_usage = floatval(number_format($memory_usage, 2, ".", ""));
        return intval($memory_usage);
    }
    function getCPU()
    {
        /* get core information (snapshot) */
        $stat1 = $this->getCoreInformation();
        /* sleep on server for one second */
        sleep(1);
        /* take second snapshot */
        $stat2 = $this->getCoreInformation();
        /* get the cpu percentage based off two snapshots */
        $data = $this->getCpuPercentages($stat1, $stat2);
        $cpu = 0;
        foreach ($data as $k => $v) {
            $cpu += floatval(100 - $v["idle"]);
        }
        $cpu = floatval($cpu / count($data));
        $cpu = floatval(number_format($cpu, 2, ".", ""));
        return $cpu;
    }
    //===============================================
    // GETCPU => HELPER FUNCTIONS
    //===============================================
    public function getCoreInformation()
    {
        if ($this->conn) {
            $data = $this->conn->exec('cat /proc/stat');
            $data = explode(PHP_EOL, $data);
        } else $data = file('/proc/stat');

        $cores = array();
        foreach ($data as $line) {
            if (preg_match('/^cpu[0-9]/', $line)) {
                $info = explode(' ', $line);
                $cores[] = array(
                    'user' => $info[1],
                    'nice' => $info[2],
                    'sys' => $info[3],
                    'idle' => $info[4]
                );
            }
        }
        return $cores;
    }
    public function getCpuPercentages($stat1, $stat2)
    {
        if (count($stat1) !== count($stat2)) {
            return;
        }
        $cpus = array();
        for ($i = 0, $l = count($stat1); $i < $l; $i++) {
            $dif = array();
            $dif['user'] = $stat2[$i]['user'] - $stat1[$i]['user'];
            $dif['nice'] = $stat2[$i]['nice'] - $stat1[$i]['nice'];
            $dif['sys'] = $stat2[$i]['sys'] - $stat1[$i]['sys'];
            $dif['idle'] = $stat2[$i]['idle'] - $stat1[$i]['idle'];
            $total = array_sum($dif);
            $cpu = array();
            foreach ($dif as $x => $y) $cpu[$x] = round($y / $total * 100, 1);
            $cpus['cpu' . $i] = $cpu;
        }
        return $cpus;
    }
}
/*
echo $ssh->exec('pwd');
echo $ssh->exec('ls -la');
*/
