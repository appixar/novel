<?php
class Novel
{
    //const DIR_CONFIG = __DIR__ . "/../../app/config/";
    const DIR_ROOT = __DIR__ . "/../../";
    const DIR_CORE = __DIR__ . "/../";
    const DIR_CORE_LIBS = __DIR__ . "/../libs/";
    const DIR_LIBS = __DIR__ . "/../../src/libs/";
    const DIR_MODULES = __DIR__ . "/../../modules/";
    const DIR_SERVICES = __DIR__ . "/../../src/services/";
    const DIR_CONTROLLERS = __DIR__ . "/../../src/controllers/";
    const DIR_HANDLERS = __DIR__ . "/../../src/routes/";
    const DIR_JOBS = __DIR__ . "/../../src/jobs/";
    const DIR_SCHEMA = __DIR__ . "/../../app/database/";
    const DIR_DB = __DIR__ . "/../../app/database/dump/";
    const DIR_PAGES = __DIR__ . "/../../pages/";
    const DIR_LIST = ['modules', 'src', 'src/controllers', 'src/libs', 'src/services', 'src/routes'];

    public function __construct()
    {
        // CHECK ERROR
        global $_SESSION;
        if (isset($_SESSION['_ERR'])) {
            $this->err($_SESSION['_ERR']['TITLE'], $_SESSION['_ERR']['TEXT'], $_SESSION['_ERR']['JSON'], $_SESSION['_ERR']['NUMBER']);
        }
        // CHECK NOVEL DEPENDENCIES
        $this->checkDependencies();

        // MERGE ALL CONFIG/*.YML FILE CONTENTS IN $_APP
        global $_APP, $_APP_VAULT, $_ENV;
        $_ENV = $this->getEnv();
        $_APP = $this->mergeConf();
        $_APP_VAULT = Novel::replaceEnvValues($_APP);
        if (!$_APP) Novel::refreshError("Config is missing", "Please check app.yml");

        // FIX URL
        if (PHP_SAPI !== 'cli') {
            new UrlFormatter();
        }

        // LOAD CORE LIBS
        $this->loadCoreLibs();

        // LOAD 'AUTOLOAD' COMPONENTS FROM CONFIG
        $this->loadDefaults();
    }
    public function api($condition_by_route = false)
    {
        new Api($condition_by_route);
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
            return Novel::findDefaultFiles($type, $dir_core, $dir_components, $ext);
        }
        if ($type === 'mason') {
            $dir_components = self::DIR_LIST;
            $dir_core = __DIR__ . "/../../core/";
            $ext = ".php";
            return Novel::findDefaultFiles($type, $dir_core, $dir_components, $ext);
        }
        if ($type === 'database') {
            $dir_components = self::DIR_LIST;
            $dir_core = __DIR__ . "/../../app/";
            $ext = ".yml";
            return Novel::findDefaultFiles($type, $dir_core, $dir_components, $ext);
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
            if (file_exists($dir_type)) {
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
        }
        return $file_list;
    }
    // $type = pages, database, config
    public static function findPathsByType($type)
    {
        $path_list = [];
        // APP RESOURCES
        $path_list = Novel::findDefaultPaths($type);
        // CORES RESOURCES
        if ($type === 'pages') $path_list[] = realpath(self::DIR_PAGES);
        if ($type === 'database') $path_list[] = realpath(self::DIR_SCHEMA);
        // REVERSE LAST ELEMENT(CORE DIR) TO FIRST POSITION
        $last = array_pop($path_list);
        array_unshift($path_list, $last);
        // RETURN
        return $path_list;
    }
    // $type = pages, database, config
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
            $fname = basename($f);
            $yml = file_get_contents($f);
            $arr = yaml_parse($yml);
            if (is_array($arr)) {
                // if routes.yml = sum data
                if ($fname === 'routes.yml') {
                    $routes_new = @$arr['ROUTES'];
                    if ($routes_new) {
                        foreach ($routes_new as $k => $v) $_APP['ROUTES'][$k] = $v;
                    }
                }
                // if not routes.yml = create/replace data
                else {
                    foreach ($arr as $k => $v) $_APP[$k] = $v;
                }
            }
        }
        return $_APP;
    }
    public function getEnv()
    {
        $env = [];
        $envPath = __DIR__ . '/../../.env'; // Ajuste o caminho conforme necessário
        if (file_exists($envPath)) {
            $envFile = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($envFile as $line) {
                if ($line[0] != '#') { // Ignorar comentários
                    list($name, $value) = explode('=', $line, 2);
                    putenv("$name=$value");
                    $env[$name] = $value;
                }
            }
        }
        return $env;
    }
    public function setEnv($key, $value)
    {
        $path = __DIR__ . '/../../.env'; // Caminho do seu arquivo .env
        if (!file_exists($path)) file_put_contents($path, "");
        if (!is_writable($path)) Novel::refreshError('.env is not writeable', 'sudo chmod 777 .env');

        // Verificar se a chave já existe no arquivo .env
        $fileContent = file_get_contents($path);
        if (strpos($fileContent, $key . '=') !== false) {
            // Se existir, substituir o valor
            $fileContent = preg_replace('/' . $key . '=.*/', $key . '=' . $value, $fileContent);
        } else {
            // Se não existir, adicionar no final do arquivo
            $fileContent .= PHP_EOL . $key . '=' . $value;
        }

        // Escrever o novo conteúdo no arquivo .env
        file_put_contents($path, $fileContent);
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
            Novel::refreshError("Yaml is missing", "sudo apt-get install php-yaml");
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
    // NECESSARY???
    // $app->load("RegisterController");
    // or... $app->load("src/controllers/RegisterController");
    //
    // $app->load("api");
    // or... $app->load("modules/api");
    public static function load($class_name_or_class_path)
    {
        novel_autoload($class_name_or_class_path);
    }
    public static function isAPI()
    {
        global $_isAPI;
        return $_isAPI;
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
        Novel::err("MODULE.YML NOT FOUND");
    }*/
    // RENDER PAGE
    public function build($snippet = '', $snippet_params = [])
    {
        if (PHP_SAPI !== 'cli') {
            new Api(true);
            new Builder($snippet, $snippet_params);
        }
    }
    public static function replaceEnvValues($array)
    {
        global $_ENV;
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $value = Novel::replaceEnvValues($value);
            } elseif ($value) {
                preg_match_all('/<ENV\.(.*?)>/', $value, $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $match) {
                        $envValue = @$_ENV[$match];
                        if ($envValue !== null) {
                            $value = str_replace('<ENV.' . $match . '>', $envValue, $value);
                        }
                    }
                }
            }
        }
        return $array;
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
    public static function refreshError($title, $text, $number = 500)
    {
        global $_SESSION, $_isAPI;
        $json = false;
        if (@$_isAPI) $json = true;
        if (PHP_SAPI !== 'cli') {
            $_SESSION['_ERR']['TITLE'] = $title;
            $_SESSION['_ERR']['TEXT'] = $text;
            $_SESSION['_ERR']['JSON'] = $json;
            $_SESSION['_ERR']['NUMBER'] = $number;
            $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            header("Location: $current_url");
        } else {
            echo "-" . PHP_EOL;
            echo "* ERROR   : $title" . PHP_EOL;
            echo "* MESSAGE : $text" . PHP_EOL;
            echo "-" . PHP_EOL;
        }
        exit;
    }
    // DISPLAY ERROR & DIE
    public static function err($title, $text = false, $json = false, $number = 500)
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

        if ($json) {
            header("Content-Type: application/json; charset=UTF-8");
            http_response_code($number);
            echo json_encode(["error" => $title, "message" => $text]);
            exit;
        }
        // BROWSER (PUBLIC)
        if (PHP_SAPI !== 'cli' && isset($_SERVER['HTTP_USER_AGENT']) && !@$_HEADER['method']) {
            http_response_code($number);
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
