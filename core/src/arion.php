<?php
// Include Yaml
require_once __DIR__ . "/../libs/yaml/vendor/autoload.php";

use Symfony\Component\Yaml\Yaml;

// Class arion
class arion
{
    //const DIR_CONFIG = __DIR__ . "/../../app/config/";
    const DIR_CORE_LIBS = __DIR__ . "/../libs/";
    const DIR_LIBS = __DIR__ . "/../../libs/";
    const DIR_MODULES = __DIR__ . "/../../modules/";
    const DIR_SCHEMA = __DIR__ . "/../../app/database/schema/";
    const DIR_DB = __DIR__ . "/../../app/database/dump/";
    const DIR_ROUTES = __DIR__ . "/../../routes/";

    public function __construct()
    {
        global $_APP;
        $_APP = array();
        // STORE ALL CONFIG/*.YML FILE CONTENTS IN $_APP
        $dir = __DIR__ . "/../../app/config/";
        $files = scandir($dir);
        for ($i = 0; $i < count($files); $i++) {
            $f = $files[$i];
            if ($f == '.' or $f == '..' or !is_file("$dir/$f")) goto next_file;
            $yaml = file_get_contents("$dir/$f");
            $array = yaml_parse($yaml);
            if (is_array($array)) foreach ($array as $k => $v) $_APP[$k] = $v;
            next_file:
        }
        if (!$_APP) $this->refreshError("Config error", "Please check app.yml");
        $this->loadLibs();
        $this->www();
    }
    public function conf($config_file)
    {
        global $_APP;
        $yaml = file_get_contents(__DIR__ . "/../../" . $config_file);
        $_APP = yaml_parse($yaml);
        if (!$_APP) {
            $this->refreshError("Config error", "Please check app.yml");
        }
        $this->loadLibs();
        $this->www();
    }
    // FIX CURRENT URL
    private function www()
    {
        if (PHP_SAPI !== 'cli' && isset($_SERVER['HTTP_USER_AGENT'])) {
            new www();
        }
    }
    // INCLUDE DEFAULT LIBS
    public function loadLibs()
    {
        new loadLibs();
    }
    // INCLUDE LIB
    public static function lib($lib)
    {
        new loadLib($lib);
    }
    // INCLUDE MODULE
    public static function module($lib)
    {
        new loadModule($lib);
    }
    // INCLUDE DEFAULT MODULES
    public function loadModules()
    {
        new loadModules();
    }
    // GET MODULE CONF
    public static function moduleConf()
    {
        $dir = $_SERVER["PWD"] . "/";
        $loop = 0;
        while ($loop < 3) {
            foreach (scandir($dir) as $k => $fn) {
                if ($fn == "module.yml") {
                    $yaml = file_get_contents($dir . "module.yml");
                    return yaml_parse($yaml);
                }
            }
            $dir .= "../";
            $loop++;
        }
        arion::err("MODULE.YML NOT FOUND");
    }
    // RENDER PAGE
    public function build($page = "")
    {
        global $_SESSION;
        if (isset($_SESSION['_ERR'])) {
            $this->err($_SESSION['_ERR']['TITLE'], $_SESSION['_ERR']['TEXT']);
        }
        if (PHP_SAPI !== 'cli' && isset($_SERVER['HTTP_USER_AGENT'])) {
            new build($page);
        }
    }
    public function scripts($array = array())
    {
        global $_SCRIPTS, $_APP;
        if (!is_array($array)) $array = array($array);
        $_SCRIPTS[$_APP['PAGE']['NAME']] = $array;
    }
    public function styles($array = array())
    {
        global $_STYLES;
        $_STYLES[] = $array;
    }
    public function refreshError($title, $text)
    {
        global $_SESSION;
        $_SESSION['_ERR']['TITLE'] = $title;
        $_SESSION['_ERR']['TEXT'] = $text;
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        header("Location: $current_url");
        exit;
    }
    // DISPLAY ERROR & DIE
    public static function err($title, $text = false)
    {
        global $_SESSION, $_HEADER;
        unset($_SESSION['_ERR']);
        $ascii = <<<EOD
   ___            __          
  / _ \___ ____  / /____  ____
 / , _/ _ `/ _ \/ __/ _ \/ __/
/_/|_|\_,_/ .__/\__/\___/_/   
         /_/                  
EOD;

        // BROWSER (PUBLIC)
        if (PHP_SAPI !== 'cli' && isset($_SERVER['HTTP_USER_AGENT']) && !@$_HEADER['method']) {
            http_response_code(404);
            echo "<html><head><meta name='viewport' content='width=device-width, initial-scale=1.0'></head>";
            echo "<body style='margin:0;padding:0;background:#14213d;width:100%;height:100%;display:table'>";
            echo "<div style='display:table-cell;text-align:center;vertical-align:middle;color:#fff;font-family:monospace;font-size:16px'>";
            echo "<p style='color:#fca311;font-size:20px'><strong>$title</strong></p>";
            echo "<p style='color:#e5e5e5'>$text</p>";
            echo "</div></body></html>";
            exit;
        }
        // TERMINAL (PRIVATE)
        else {
            die("\n-\n# $title :: $text\n-\n");
        }
    }
}
