<?php
class Job extends arion
{
    //
    private $conf = array(
        "logMaxSize" => 25 //mb
    );
    //
    private $time_start = 0;
    private $time_total = 0;
    //
    private $caller;
    private $caller_path;
    private $caller_fn;
    private $caller_lock;
    private $caller_id;
    private $caller_date;
    private $log_fn;
    private $error_fn;
    //
    private $last_id;
    //
    public function __construct($bypass = false)
    {
        $caller = debug_backtrace();
        $this->caller = $caller[0]['file'];
        $this->caller_path = dirname($this->caller);
        $this->caller_fn = basename($this->caller);
        $this->caller_lock = $this->caller . "@lock";
        $this->caller_id = $this->caller . "@id";
        $this->caller_date = $this->caller . "@date";
        $this->log_fn = $this->caller . "@log";
        $this->error_fn = $this->caller . "@error";
        if (!$bypass and !is_writable($this->log_fn)) {
            $this->say("<red>* Deny! Type: sudo chmod -R 777 ./</end>", false, true);
            exit;
        }
    }
    public static function schedule($fn, $interval = "every 1min")
    {
        global $_APP;
        $now = false;
        cmd::say("∴ $fn : $interval", true, 'blue');
        job::lockClean();
        // interval words
        $w = explode(" ", $interval);
        if ($w[0] == 'every') {
            if (!@$w[1]) cmd::say("⚠ unknown interval", false, 'red');
            if (@$w[1] == '1min' or @$w[1] == '1m') $now = true;
        }
        if ($now) {
            $dir = __DIR__ . "/../../app/jobs";
            $exec = "php $dir/src/{$fn}.php $interval from {$_APP['NAME']} " . date('H:i:s');
            $exec_say = "<green>► php src/{$fn}.php</end> <blue>-></end> <magenta>$interval from {$_APP['NAME']}</end>";
            cmd::say($exec_say);
            exec("$exec > /dev/null &");
        }
    }
    public function start()
    {
        //$this->lockClean(); // must before, in schedule()
        $this->lockDie();
        $this->setDate();
        set_time_limit(0);
        $this->time_start = microtime(true);
        $this->log('START.');
        file_put_contents($this->caller_lock, "");
    }
    private function setDate()
    {
        file_put_contents($this->caller_date, time());
    }
    public function set_last_id($id, $say = true)
    {
        file_put_contents($this->caller_last, $id);
        if ($say) $this->say("SET LAST ID: <blue>$id</end>", true, true, "pink");
    }
    public function get_last_id()
    {
        if (file_exists($this->caller_last)) {
            $last_id = file_get_contents($this->caller_last);
        } else {
            file_put_contents($this->caller_last, 0);
            $last_id = 0;
        }
        $this->say("CONTINUE AFTER LAST ID: <blue>$last_id</end>...", true, true, "pink");
        return $last_id;
    }
    private function secToTime($seconds)
    {
        $t = round($seconds);
        return sprintf('%02d:%02d:%02d', ($t / 3600), ($t / 60 % 60), $t % 60);
    }
    public function log($message)
    {
        if (file_exists($this->log_fn) and filesize($this->log_fn) >= intval($this->conf['logMaxSize'] * 1024 * 1024)) {
            // clear log file
            file_put_contents($this->log_fn, "", FILE_APPEND);
        }
        file_put_contents($this->log_fn, "[" . date("Y-m-d H:i:s") . "] $message" . PHP_EOL, FILE_APPEND);
    }
    public function logerror($message)
    {
        if (file_exists($this->error_fn) and filesize($this->error_fn) >= intval($this->conf['logMaxSize'] * 1024 * 1024)) {
            // clear log file
            file_put_contents($this->error_fn, "", FILE_APPEND);
        }
        file_put_contents($this->error_fn, "[" . date("Y-m-d H:i:s") . "] $message" . PHP_EOL, FILE_APPEND);
    }
    public function end()
    {
        $this->time_total = number_format((microtime(true) - $this->time_start), 4);
        $this->log("END. TOTAL RUNTIME: " . $this->secToTime($this->time_total));
        @unlink($this->caller_lock);
        exit;
    }
    // IF LOCK FILE EXISTS, DIE
    public function lockDie()
    {
        if (file_exists($this->caller_lock)) {
            $this->error('LOCKED');
            exit;
        }
    }
    // REMOVE LOCK FILES NOT RUNNING
    public static function lockClean()
    {
        // find -lock files
        $dir = __DIR__ . "/../../app/jobs/src";
        $files = scandir($dir);
        $locked = array();
        for ($i = 0; $i < count($files); $i++) {
            $fn = $files[$i];
            if (strpos($fn, '@lock') !== false) {
                $locked[] = $fn;
            }
        }
        if ($locked) {
            // find php processes
            $output = shell_exec('ps -C php -f');
            foreach ($locked as $k => $v) {
                // locked file is not running
                $fn_lock = $v;
                $fn = str_replace("@lock", "", $v);
                if (strpos($output, "php $fn") === false) {
                    @unlink($fn_lock);
                    cmd::say("UNLOCKING $v...");
                }
            }
        }
    }
    public function validate($res)
    {
        // Check errors
        $return = json_decode($res['res']);
        if ($res['err']) {
            $this->say("(!) cURL Error: {$res['err']}", false, true, "red");
            exit;
        }
        if (isset($return->message)) {
            $this->say("(!) API Message: $return->message", false, true, "red");
            exit;
        }
        if (isset($return->api->error)) {
            $this->say("(!) API Error: $return->api->error", false, true, "red");
            exit;
        }
    }
    public function error($text)
    {
        $this->say($text, false, true, 'red');
        $this->logerror($text);
    }
    public function say($text, $header = false, $log = false, $color = '')
    {
        $header_width = 50;
        $header_symbol = "·";
        $colors = array(
            'header' => "\033[95m",
            //
            'pink' => "\033[94m",
            'cyan' => "\033[36m",
            'green' => "\033[92m",
            'yellow' => "\033[93m",
            'red' => "\033[91m",
            'blue' => "\033[1m",
            'magenta' => "\033[35m",
            //
            'blink' => "\033[5m",
            'strong' => "\033[1m",
            'u' => "\033[4m",
            'end' => "\033[0m"
        );
        foreach ($colors as $k => $v) {
            $text = str_replace("<$k>", $v, $text);
            $text = str_replace("</$k>", $colors['end'], $text);
        }

        if (!$color) $c = '';
        else $c = $colors[$color];

        // OPEN HEADER BAR
        if ($header) {
            $_content = @$colors[$color] . str_repeat($header_symbol, $header_width) . @$colors['end'];
            echo $_content . PHP_EOL;
            if ($log) $this->log($_content);
        }

        // TEXT
        $_content = "{$c}$text{$colors['end']}";
        echo $_content . PHP_EOL;
        if ($log) $this->log($_content);

        // CLOSE HEADER BAR
        if ($header) {
            $_content = @$colors[$color] . str_repeat($header_symbol, $header_width) . @$colors['end'];
            echo $_content . PHP_EOL;
            if ($log) $this->log($_content);
        }
    }
}
