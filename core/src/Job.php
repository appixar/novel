<?php
class Job extends Novel
{
    //
    private $conf = array(
        "logMaxSize" => 25 //mb
    );
    //
    private $time_start = 0;
    private $time_total = 0;
    //
    private $caller, $caller_path, $caller_fn, $caller_id, $caller_date, $log_fn;
    //
    private $last_id;
    private $caller_content; // verify changes
    //
    public function __construct($bypass = false)
    {
        $caller = debug_backtrace();
        $this->caller = $caller[0]['file'];
        $this->caller_path = dirname($this->caller);
        $this->caller_fn = basename($this->caller);
        //$this->caller_lock = $this->caller . "@lock";
        $this->caller_id = $this->caller . "@id";
        $this->caller_date = $this->caller . "@date";
        $this->log_fn = $this->caller . "@log";
        $this->caller_content = md5_file("{$this->caller_path}/{$this->caller_fn}");
        if (!$bypass and !is_writable($this->log_fn)) {
            $this->say("<red>* Deny! Type: sudo chmod -R 777 ./</end>", false, true);
            exit;
        }
    }
    public static function run_all_jobs()
    {
        global $_APP;
        if (!@$_APP['JOBS']) return false;
        $total_jobs = count($_APP['JOBS']);
        Mason::say("∴ $total_jobs jobs from {$_APP['NAME']}", true, 'blue');
        // check if autoplay is available
        $stop_fn = realpath(__DIR__ . '/../../src/jobs/stop');
        if (file_exists($stop_fn)) {
            Mason::say("<magenta>(!) autoplay is disabled</end>");
            Mason::say("remove: $stop_fn");
            exit;
        }
        foreach ($_APP['JOBS'] as $fn) {
            // already running
            if (self::check_fn_process($fn)) {
                Mason::say("✔ php {$fn} <magenta>(already running)</end>");
            }
            // run
            else {
                $dir = __DIR__ . '/../../';
                $dir = realpath($dir);
                $exec = "php $dir/$fn";
                Mason::say("<green>► php {$fn}</end>");
                exec("$exec > /dev/null &");
            }
        }
    }
    public function start()
    {
        $this->check_caller_process();
        $this->check_caller_changes();
        $this->setDate();
        set_time_limit(0);
        $this->time_start = microtime(true);
        $this->log('START.');
        //file_put_contents($this->caller_lock, "");
    }
    private function check_caller_changes()
    {
        clearstatcache();
        $current_caller_content = md5_file("{$this->caller_path}/{$this->caller_fn}");
        if ($current_caller_content !== $this->caller_content) {
            $this->log("FILE HAS CHANGED.");
            $this->end();
        }
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
    public function end()
    {
        $this->time_total = number_format((microtime(true) - $this->time_start), 4);
        $this->log("END. TOTAL RUNTIME: " . $this->secToTime($this->time_total));
        //@unlink($this->caller_lock);
        exit;
    }
    public function check_caller_process()
    {
        exec("ps aux | grep '{$this->caller_fn}' | grep -v grep | awk '{print $2}'", $findProcess);
        if (count($findProcess) > 1) {
            echo '(!) ALREADY RUNNING.' . PHP_EOL;
            exit;
        }
    }
    public static function check_fn_process($fn)
    {
        exec("ps aux | grep '{$fn}' | grep -v grep | awk '{print $2}'", $findProcess);
        if (count($findProcess) > 0) return true;
        else return false;
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
