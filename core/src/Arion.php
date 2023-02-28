<?php
// Class Arion
class Arion
{
    //const DIR_CONFIG = __DIR__ . "/../../app/config/";
    const DIR_ROOT = __DIR__ . "/../../";
    const DIR_CORE = __DIR__ . "/../";
    const DIR_CORE_LIBS = __DIR__ . "/../libs/";
    const DIR_LIBS = __DIR__ . "/../../src/libs/";
    const DIR_MODULES = __DIR__ . "/../../modules/";
    const DIR_SERVICES = __DIR__ . "/../../src/services/";
    const DIR_CONTROLLERS = __DIR__ . "/../../src/controllers/";
    const DIR_JOBS = __DIR__ . "/../../src/jobs/";
    const DIR_SCHEMA = __DIR__ . "/../../app/database/";
    const DIR_DB = __DIR__ . "/../../app/database/dump/";
    const DIR_ROUTES = __DIR__ . "/../../routes/";
    const DIR_LIST = ['modules', 'src/controllers', 'src/libs', 'src/services'];

    public function __construct()
    {
        // CHECK ERROR
        global $_SESSION;
        if (isset($_SESSION['_ERR'])) {
            $this->err($_SESSION['_ERR']['TITLE'], $_SESSION['_ERR']['TEXT']);
        }
        // CHECK ARION DEPENDENCIES
        $this->checkDependencies();
        
        // MERGE ALL CONFIG/*.YML FILE CONTENTS IN $_APP
        global $_APP;
        $_APP = $this->mergeConf();
        if (!$_APP) $this->refreshError("Config is missing", "Please check app.yml");

        // FIX URL
        if (PHP_SAPI !== 'cli' && isset($_SERVER['HTTP_USER_AGENT'])) {
            new UrlFormatter();
        }

        // LOAD CORE LIBS
        $this->loadCoreLibs();

        // LOAD 'AUTOLOAD' COMPONENTS FROM CONFIG
        $this->loadDefaults();
    }
    public static function get_dir_list()
    {
        return self::DIR_LIST;
    }
    private function loadDefaults()
    {
        global $_APP;
        if (!@$_APP['AUTOLOAD']) return;
        foreach ($_APP['AUTOLOAD'] as $component) {
            $this->load($component);
        }
    }
    public static function findFilesByType($type)
    {
        //return call_user_func(array($this, "findFiles_$type"));
        if ($type === 'config') {
            $dir_components = self::DIR_LIST;
            $dir_core = __DIR__ . "/../../app/";
            $ext = ".yml";
            return Arion::findDefaultFiles($type, $dir_core, $dir_components, $ext);
        }
        if ($type === 'mason') {
            $dir_components = self::DIR_LIST;
            $dir_core = __DIR__ . "/../../core/";
            $ext = ".php";
            return Arion::findDefaultFiles($type, $dir_core, $dir_components, $ext);
        }
        if ($type === 'database') {
            $dir_components = self::DIR_LIST;
            $dir_core = __DIR__ . "/../../app/";
            $ext = ".yml";
            return Arion::findDefaultFiles($type, $dir_core, $dir_components, $ext);
        }
    }
    public static function findDefaultFiles($type, $dir_core, $dir_components, $ext)
    {
        $root = __DIR__ . "/../../";
        $file_list = array(); // return

        // CORE CONFIGS
        $dir_core .= $type . "/";
        $files = @array_diff(@scandir($dir_core), [".", ".."]);
        foreach ($files as $file) {
            if (!is_file($dir_core . $file)) continue;
            if (substr($file, -4) !== $ext) continue;
            $file_list[] = realpath($dir_core . $file);
        }
        // ALL COMPONENTS CONFIGS
        foreach ($dir_components as $d) {
            $dir_type = $root . $d;
            // LOOP IN COMPONENTS
            $components = array_diff(scandir($dir_type), [".", ".."]);
            foreach ($components as $component) {
                $dir_conf = "$dir_type/$component/$type/";
                if (!@is_dir($dir_conf)) continue;
                $files = array_diff(scandir($dir_conf), [".", ".."]);
                foreach ($files as $file) {
                    if (substr($file, -4) !== $ext) continue;
                    $file_list[] = realpath($dir_conf . $file);
                }
            }
        }
        return $file_list;
    }
    // $type = routes, database, config
    public static function findPathsByType($type)
    {
        $path_list = [];
        // APP RESOURCES
        $path_list = Arion::findDefaultPaths($type);
        // CORES RESOURCES
        if ($type === 'routes') $path_list[] = realpath(self::DIR_ROUTES);
        if ($type === 'database') $path_list[] = realpath(self::DIR_SCHEMA);
        // REVERSE LAST ELEMENT(CORE DIR) TO FIRST POSITION
        $last = array_pop($path_list);
        array_unshift($path_list, $last);
        // RETURN
        return $path_list;
    }
    // $type = routes, database, config
    public static function findDefaultPaths($type)
    {
        $root = __DIR__ . "/../../";
        $path_list = array(); // return

        foreach (self::DIR_LIST as $d) {
            $path = $root . $d;
            if (!file_exists($path)) continue;
            // LOOP IN COMPONENTS
            $components = array_diff(scandir($path), [".", ".."]);
            foreach ($components as $component) {
                $path_target = "$path/$component/$type/";
                if (!@is_dir($path_target)) continue;
                $path_list[] = realpath($path_target) . "/";
            }
        }
        // ADD CORE
        $path_list[] = realpath(self::DIR_CORE) . "/$type/";
        return $path_list;
    }
    // MERGE ALL CONFIG/*.YML FILE CONTENTS IN $_APP
    public function mergeConf()
    {
        $_APP = array();
        $files = $this->findFilesByType('config');
        foreach ($files as $f) {
            $yml = file_get_contents($f);
            $arr = yaml_parse($yml);
            if (is_array($arr)) foreach ($arr as $k => $v) $_APP[$k] = $v;
        }
        return $_APP;
    }
    /*
    public function conf($config_file)
    {
        global $_APP;
        $yaml = file_get_contents(__DIR__ . "/../../" . $config_file);
        $_APP = yaml_parse($yaml);
        if (!$_APP) {
            $this->refreshError("Config error", "Please check app.yml");
        }
        $this->loadLibs();

        // FIX CURRENT URL
        if (PHP_SAPI !== 'cli' && isset($_SERVER['HTTP_USER_AGENT'])) {
            new UrlFormatter();
        }
    }*/
    // CHECK DEPENDENCIES
    private function checkDependencies()
    {
        if (!function_exists("yaml_parse")) {
            echo "Yaml is missing!\r\n";
            echo "> sudo apt-get install php-yaml\r\n";
            exit;
        }
    }
    /*public static function module($lib)
    {
        new loadModule($lib);
    }*/
    // INCLUDE DEFAULT LIBS
    public function loadCoreLibs()
    {
        // INCLUDE CORE LIBS
        $core_libs = scandir(self::DIR_CORE_LIBS);
        for ($i = 0; $i < count($core_libs); $i++) {
            $fn = $core_libs[$i];
            $fp = self::DIR_CORE_LIBS . $fn;
            if (is_file($fp)) require_once($fp);
        }
    }
    // INCLUDE RESOURCES. MODE 2
    // $app->load("api-server", "modules");
    public static function load($class_path)
    {
        arion_autoload($class_path);
    }

    // GET MODULE CONF
    /*
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
        Arion::err("MODULE.YML NOT FOUND");
    }*/
    // RENDER PAGE
    public function build()
    {
        if (PHP_SAPI !== 'cli' && isset($_SERVER['HTTP_USER_AGENT'])) {
            new Builder();
        }
    }
    /*
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
    }*/
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
