<?php

//use Symfony\Component\Yaml\Yaml;

//★ ∴ → ☿ √ ✓ ✗ ❤ ⚡ ⚠ ✝ ❖ ⚯ ☝ ► ☠ 
// https://unicode-table.com/en/#basic-latin

class cmd extends arion
{
    private $argv;
    private $git_errors = 0;
    public function __construct()
    {
        global $argv, $_APP;
        if (PHP_SAPI !== 'cli' or isset($_SERVER['HTTP_USER_AGENT'])) die($this->say('Console only.'));
        if (!isset($argv[1])) die(ARION_VERSION . PHP_EOL);
        $this->argv = &$argv;
        $this->run();
    }
    public function run()
    {
        $commands = array("install", "add", "delete", "database");
        if (!in_array($this->argv[1], $commands)) {
            $this->say('Command not found.');
            exit;
        }
        if (method_exists('cmd', $this->argv[1])) $this->{$this->argv[1]}();
    }
    public function database()
    {
        global $_APP;
        $host = $_APP['MYSQL'][0]['HOST'];
        $name = $_APP['MYSQL'][0]['NAME'];
        $user = $_APP['MYSQL'][0]['USER'];
        $pass = $_APP['MYSQL'][0]['PASS'];
        //
        if (!@$this->argv[2]) die($this->say('Missing parameters.'));
        if ($this->argv[2] == "dump") {
            $fn = time() . '-' . $name . '.sql';
            $fp = self::DIR_DB . $fn;
            //exec("mysqldump --user=$user --password=$pass --host=$host --no-data $name > $fp");
            $this->say("* Generated: app/database/dump/<green>$fn</end>", true);
        }
        if ($this->argv[2] == "up") {

            // sub --arguments (optional)
            $sub_argv = array();
            for ($i = 0; $i < count($this->argv); $i++) {
                if (substr($this->argv[$i], 0, 2) === '--') {
                    $sub_argv[] = explode('--', $this->argv[$i])[1];
                }
            }

            // run schema
            $schema = new schema();
            $schema->up($sub_argv);
        }
    }
    public function install()
    {
        /*if (!$this->arg(2)) {
            $yaml = file_get_contents("install.yml");
            $yaml = Yaml::parse($yaml);
            $this->say("Arion Installer", "header", 1);
            $this->say("Cloning repositories...");
            foreach ($yaml as $k => $v) {
                $this->git($k, $v);
            }
            $this->say('Finished.', 'green');
        }*/
    }
    public function add()
    {
        /*if (!$this->arg(2)) die($this->say('Missing parameters.'));
        $url = $this->arg(2);
        if (strpos($url, 'lib-') > -1) $this->git("libs", $url);
        if (strpos($url, 'mod-') > -1) $this->git("modules", $url);
        $this->say('Finished.', 'green');*/
    }
    private function git($dir, $urls = array())
    {
        if (!is_dir($dir)) die("* Error: ./$dir directory not found" . PHP_EOL);
        $this->say("$dir/...", "warning");
        for ($i = 0; $i < @count($urls); $i++) {
            $url = $urls[$i];
            $url_clean = $this->gitURL($url);
            $url_account = $this->gitURL($url, 1);
            $repo = str_replace(".git", "", @end(explode("/", $url)));
            $this->say("- $url_clean", "cyan");
            if (!is_dir("$dir/vendor")) shell_exec("mkdir $dir/vendor");
            if (!is_dir("$dir/vendor/$repo")) shell_exec("cd $dir/vendor && git clone $url_account"); //2>&1
            else shell_exec("cd $dir/vendor/$repo && git pull");
        }
        $this->say("Ok.");
    }
    private function gitURL($url, $include_account = 0)
    {
        global $_APP;
        if (!strpos("://", $url)) {
            if ($include_account) $url = "https://{$_APP['GIT']}@github.com/appixar/" . $url;
            else $url = "https://github.com/appixar/" . $url;
        }
        $repo = @end(explode("/", $url));
        if (!strpos($repo, '.git')) $url .= ".git";
        return $url;
    }

    public static function say($text, $header = false, $color = '')
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
            $_content = $c . str_repeat($header_symbol, $header_width) . $colors['end'];
            echo $_content . PHP_EOL;
        }

        // TEXT
        $_content = "{$c}$text{$colors['end']}";
        echo $_content . PHP_EOL;

        // CLOSE HEADER BAR
        if ($header) {
            $_content = $c . str_repeat($header_symbol, $header_width) . $colors['end'];
            echo $_content . PHP_EOL;
        }
    }
}
