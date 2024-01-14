<?php
class Builder extends Novel
{
    public $pageName;
    public $pageDir;
    public $pageRootUri;
    //
    public function __construct($snippet = "", $snippet_params = [])
    {
        global $_APP, $_APP_VAULT;
        global $_ORDER; // $files[] order to include
        global $_URI; // domain.com/ad/edit/123 => $_URI[0]=ad [1]=edit [2]=123
        // $_PAR = ROUTE URL PARAMS
        // IF ROUTE YML & API SERVER: domain.com/ad/edit/{id} => $_PARAM[id]=123 based on routes.yml
        // IF ROUTE FILE SYSTEM: domain.com/ad/edit/123 => $_PATH[0]=123 (first param after real directory) 
        global $_PAR;
        global $_HEADER; // api server
        global $_BODY; // api server
        global $_ROUTE, $_ROUTE_PERMISSION; // current route + permission
        global $_isAPI;
        //
        global $_BUILD_COUNT;
        $_BUILD_COUNT++;

        // GET ALL VARIABLES
        extract($GLOBALS, EXTR_REFS | EXTR_SKIP);

        //==================================
        // $PAGE PRE DEFINED? UPDATE $_URI
        //==================================
        if ($snippet) {
            $_URI = explode("/", $snippet);
        }
        // BUG FIX END "/" IF URL HAVE GET PARAMETERS
        if (!empty($_URI) and end($_URI) === '') array_pop($_URI);
        if (empty($_URI)) $_URI[] = 'home';
        //prex($_URI);
        //-
        // API FIRST.
        // FIRST OF ALL, TRY TO FIND ROUTE IN APP/CONFIG/ROUTES.YML
        // API SERVER?
        //-
        if (@$_APP['API_SERVER']) {
            $_isAPI = true;
            $this->checkApiServerRoute();
            // IF FOUND ROUTE. STOP HERE.
            if (@$_HEADER or @!$_APP['PAGES']) {
                $msg = "Not found";
                if (@!$_APP['ROUTES']) $msg = "Route config not found";
                if (@!$_APP['API_SERVER']['ALWAYS_200'] === true) header("HTTP/1.1 404 $msg");
                else header("HTTP/1.1 200 $msg");
                $json = json_encode(array(
                    'error' => 404,
                    'message' => $msg
                ));
                die($json);
            }
        }
        // IF ROUTE FOUND, STOP HERE.
        // IF NOT FOUND, CONTINUE... AND TRY FIND PAGE IN /PAGES
        // CONTINUE IN FILE SYSTEM INCLUDES...
        if (@$_APP['PAGES']) $_isAPI = false;
        //==================================
        // DEFINE $FILES
        // TARGET LIST EXISTS?
        //==================================
        if (!@$_APP['PAGES']) Novel::refreshError(404, "Not found", 404);
        $this->pageDir = $this->findPageDir();
        $this->pageRootUri = $this->getRootUriFromDir($this->pageDir);
        $this->pageName = $this->getPageFromDir($this->pageDir);
        $yaml = $this->getYamlFromDir($this->pageDir);

        // MERGE $YAML TO $_APP
        if (is_array($yaml)) $_APP = array_merge($_APP, $yaml);

        // CREATE $_APP_REAL WITH REAL VARIABLES .ENV
        $_APP_VAULT = Novel::replaceEnvValues($_APP);

        //==================================
        // GET URL ALIAS IF EXISTS (/.css, /.js)
        //==================================
        $_ALIAS = $this->getAliasFromUri($this->pageDir);

        // SET $_PAR
        //$_PAR = $this->getParamFromUri();
        //==================================
        // PATH_PARAM ENABLED?
        //==================================
        //if (!@$_APP["PAGES"]["URL_PARAMS"]) {
        if (@!$this->pageDir) {
            Novel::refreshError("Not found", "Page '" . end($_URI) . "' not found.", 404);
        }
        // FAKE ALIAS BUGFIX
        if (@$_APP["PAGES"]["URL_MASK"]) {
            if (@array_key_exists(end($_URI), $_APP["PAGES"]["URL_MASK"])) $aliasExt = end($_URI);
            if (@$aliasExt and !@$_ALIAS) {
                Novel::refreshError("Not found", "URL Mask '{$this->pageName}$aliasExt' not found.", 404);
            }
        }
        //}
        if (!$this->pageName) Novel::refreshError("Not found", "Page '{$_PAR[0]}' not found.", 404);

        //==================================
        // DEFAULT LIBS, CORE LIBS & DEFAULT MODULES
        //==================================
        //$this->loadLibs();
        //$this->loadModules();

        // DEFINE UTIL VARIABLES & CONSTANTS
        $this->setDefinitionsFromDir($this->pageDir, $snippet, $snippet_params);
        $_ORDER = $this->getFilesFromDir($this->pageDir);

        //==================================
        // INCLUDE ONLY ALIAS IF EXISTS
        // TARGET FILE IS ALIAS (/.CSS/.JS/.POST)
        //==================================
        if ($_ALIAS) {
            //array_shift($_PAR); // remove first element (/.post/)
            include $_ALIAS;
            exit;
        }
        //==================================
        // CONTENT
        //==================================
        $this->requireFiles();
        if (@$_APP["SNIPPET"]) {
            $_APP["SNIPPETS"][] = $_APP["SNIPPET"];
            unset($_APP["SNIPPET"]);
        }
    }
    private function checkApiServerRoute()
    {
        global $_APP, $_HEADER, $_URI, $_PAR, $_ROUTE, $_ROUTE_PERMISSION;
        if (@!$_APP['ROUTES']) return false;
        // multiple $matches_ to organize data and otimize array search
        $_matches_parts = [];
        $_matches_params = [];
        // loop routes
        foreach ($_APP['ROUTES'] as $endpoint => $controller) {
            $_method = up(explode(' ', $endpoint)[0]);
            $_url = @low(explode(' ', $endpoint)[1]);
            if (!$_url) {
                $_url = low($_method);
                $_method = 'ALL';
            }
            // clean url
            if ($_url[0] === '/') $_url = substr($_url, 1); // remove first '/'
            if (substr($_url[0], -1) === '/') $_url = substr($_url, 0, -1); // remove last '/'

            // util vars
            $_parts = explode('/', $_url);
            $_found = true;
            $_found_parts = 0;
            $_param_key = [];
            $_param_value = [];

            // different method? next!
            if ($_method !== 'ALL' and $_method !== $_SERVER["REQUEST_METHOD"]) continue;
            if ($_method !== 'ALL') $_found_parts++;

            // wildcard
            $_asterisk = false;

            // compare endpoints positions with current url
            for ($i = 0; $i < count($_parts); $i++) {
                // position is wildcard *
                if ($_parts[$i] === '*') {
                    $_asterisk = true;
                    break;
                }
                // position is <variable> (required)
                elseif (@$_URI[$i] and substr($_parts[$i], 0, 1) === '<' and substr($_parts[$i], -1) === '>') {
                    $_param_key[] = substr($_parts[$i], 1, -1); // remove <>
                    $_param_value[] = $_URI[$i];
                    //$_found = true;
                }
                // position is [variable] (optional)
                elseif (substr($_parts[$i], 0, 1) === '[' and substr($_parts[$i], -1) === ']') {
                    $_param_key[] = substr($_parts[$i], 1, -1); // remove []
                    $_param_value[] = @$_URI[$i];
                    $_found_optional_variable = true;
                    //$_found = true;
                }
                // position differs from url
                elseif ($_parts[$i] !== @$_URI[$i]) {
                    //echo "{$_parts[$i]} !== {$_URI[$i]}\r\n";
                    $_found = false;
                }
                $_found_parts++;
            }
            // compare url size with endpoint size
            if (!empty($_URI)) {
                if (!$_asterisk and count($_URI) !== count($_parts)) {
                    if (!@$_found_optional_variable) $_found = false;
                }
            }

            // SAVE POSSIBLE ENDPOINT
            if ($_found) {
                $_matches_parts[$endpoint] = $_found_parts;
                foreach ($_param_key as $i => $key) {
                    $_matches_params[$endpoint][$key] = @$_param_value[$i];
                }
            }
        }
        // FIND THE BIGGEST KEY (MORE FOUND "/" PARTS)
        //      THAN... INVOKE CONTROLLER AND EXIT!
        if (!empty($_matches_parts)) {
            Api::buildApiHeaders();
            $biggestKeyFound = array_search(max($_matches_parts), $_matches_parts);
            $controllerContent = $_APP['ROUTES'][$biggestKeyFound];
            $controller = @trim(@explode(" ", $controllerContent)[0]);
            $_ROUTE = $biggestKeyFound;
            // permission flag
            $flag = @$_APP['API_SERVER']['ROUTE_PERMISSION_FLAG'];
            if (!$flag) $flag = "⛊";
            $permissionContent = @trim(@explode($flag, $controllerContent)[1]);
            if ($permissionContent) {
                $_ROUTE_PERMISSION = trim($permissionContent);
            }
            $_PAR = @$_matches_params[$biggestKeyFound];
            // controler.{method} as variable?
            $method = @explode(".", $controller)[1];
            if (@$method and substr($method, 0, 1) === '<' and substr($method, -1) === '>') {
                $method_name = $_PAR[substr($method, 1, -1)];
                $controller_name = explode(".", $controller)[0];
                $controller = "$controller_name.$method_name";
            }
            if (@$method and substr($method, 0, 1) === '[' and substr($method, -1) === ']') {
                $method_name = @$_PAR[substr($method, 1, -1)];
                if (!$method_name) $method_name = 'index';
                $controller_name = explode(".", $controller)[0];
                $controller = "$controller_name.$method_name";
            }
            Http::route([
                'controller' => $controller,
                'params' => @$_PAR,
                'required' => true
            ]);
            exit;
        }
        //}
    }
    private function findRealPath($uriArray, $rootDir)
    {
        global $_PAR;
        // Remover o domínio base da URL e converter em um array de segmentos
        //$path = str_replace($baseUrl, '', $url);
        //$segments = explode('/', trim($path, '/'));
        $segments = $uriArray;
        $currentPath = $rootDir;
        $finalPath = '';
        // Navegar pelos segmentos da URL
        foreach ($segments as $segment) {
            //echo "*$currentPath/$segment<br>";
            if (is_dir($currentPath . '/' . $segment)) {
                // Se o segmento da URL corresponder a um diretório real
                $currentPath .= '/' . $segment;
                $finalPath .= '/' . $segment;
            } else {
                // Se não corresponder, tentamos substituir pelo diretório especial
                $foundSpecialDir = false;
                if (file_exists($currentPath)) {
                    $allDirs = scandir($currentPath);
                    // special dir <name>
                    $specialDirs = array_filter($allDirs, function ($dir) {
                        return $dir[0] === '<' && substr($dir, -1) === '>';
                    });
                    foreach ($specialDirs as $specialDir) {
                        if (is_dir($currentPath . '/' . $specialDir)) {
                            $specialDirClean = substr($specialDir, 1, -1);
                            $_PAR[$specialDirClean] = $segment;
                            //echo "$specialDir = $segment"; exit;
                            $currentPath .= '/' . $specialDir;
                            $finalPath .= '/' . $specialDir;
                            $foundSpecialDir = true;
                            break;
                        }
                    }
                    // special dir @name
                    $specialDirs = array_filter($allDirs, function ($dir) {
                        return $dir[0] === '@';
                    });
                    foreach ($specialDirs as $specialDir) {
                        if (is_dir($currentPath . '/' . $specialDir)) {
                            $specialDirClean = substr($specialDir, 1);
                            $_PAR[$specialDirClean] = $segment;
                            //echo "$specialDir = $segment"; exit;
                            $currentPath .= '/' . $specialDir;
                            $finalPath .= '/' . $specialDir;
                            $foundSpecialDir = true;
                            break;
                        }
                    }
                }
                if (!$foundSpecialDir) {
                    // Se nem o diretório especial foi encontrado, então a URL é inválida
                    return false;
                }
            }
        }
        return $rootDir . $finalPath;
    }
    private function findPageDir()
    {
        global $_APP, $_URI;
        $uri_page = implode("/", $_URI);
        $uri_page_arr = explode("/", $uri_page); // way to current page
        // CHECK IF ALIAS EXISTS IN URL
        $alias = @$_APP["PAGES"]["URL_MASK"];
        if (@array_key_exists(end($_URI), $alias)) {
            array_pop($uri_page_arr); // REMOVE ALIAS TO FOUND DIR
        }
        // LOOP IN ALL ROUTES & SUB ROUTES
        $pages_path = $this->findPathsByType("pages");
        foreach ($pages_path as $path) {
            $realpath = $this->findRealPath($uri_page_arr, $path);
            if ($realpath) return $realpath;
        }
        return false;
    }
    private function requireFiles()
    {
        global $_APP, $_ORDER, $_PAR, $_URI;

        foreach ($GLOBALS as $k => $v) global ${$k};

        $_APP["FLOW_X"] = 0; // flow sort order

        foreach ($_ORDER as $file) {
            if (file_exists($file)) {
                $start = microtime(true); // inicia cronômetro
                new Debug(__CLASS__, "$file...", "muted");

                require_once($file);
                $_APP["FLOW_X"]++;

                $time_elapsed_secs = number_format((microtime(true) - $start), 4);
                new Debug(__CLASS__, "$file in $time_elapsed_secs s");
            }
        }
    }
    private function getAliasFromUri($route_dir)
    {
        global $_APP, $_URI;
        $alias = @$_APP["PAGES"]["URL_MASK"];
        if (!$alias) return false;
        $_ALIAS = false;

        // find alias in end of url
        if (@array_key_exists(end($_URI), $alias)) {
            $ext = end($_URI);
            array_pop($_URI); // remove last element (/.ext)
            $page = end($_URI);
            //$uri_page = implode("/", $_URI);
            $f_name = str_replace("<PAGE>", $page, $alias[$ext]);
            $f_alias = "$route_dir/$f_name";
            if (file_exists($f_alias)) {
                // if $f_alias is set, in the end of file will have a include + exit;
                if ($ext == ".css") header("Content-type: text/css; charset: UTF-8; Cache-control: must-revalidate");
                if ($ext == ".js") header('Content-Type: application/javascript');
                $_ALIAS = $f_alias;
                //if (function_exists('jwsafe')) jwsafe();
            }
        }
        return $_ALIAS;
    }
    private function getParamFromUri()
    {
        global $_URI;
        //
        $uri_str = implode("/", $_URI);
        $uri_str_clean = str_replace($this->pageRootUri, "", $uri_str);
        // bug fix "/"
        if (substr($uri_str_clean, 0, 1) === '/') $uri_str_clean = substr($uri_str_clean, 1); // Remove o primeiro caractere
        // return
        $_PAR = explode("/", $uri_str_clean);
        return $_PAR;
    }
    private function getFilesFromDir($route_dir)
    {
        // GLOB
        global $_APP, $_HEADER, $_URI, $_PAR, $_ROUTE_ROOT, $_BUILD_COUNT;
        $files = array();

        // GET PAGE DATA
        $page = $this->getPageFromDir($route_dir);
        $root = $this->getRootFromDir($route_dir);
        $yaml = $this->getYamlFromDir($route_dir);
        $yaml_fn = $this->getYamlFromDir($route_dir, true);
        if ($yaml_fn) {
            $yaml_dir = explode("/", $yaml_fn);
            array_pop($yaml_dir);
            $yaml_dir = implode("/", $yaml_dir);
        }

        // MERGE YAML
        if (is_array($yaml)) $_APP = array_merge($_APP, $yaml);
        $flow = @$_APP["PAGES"]["FILE_SEQUENCE"];

        // SNIPPET? INCLUDE ONLY .PHP & .TPL
        if (@$_APP["SNIPPET"]) {
            $flow = ["<PAGE>.php", "<PAGE>.tpl"];
        }

        // FLOW LOOP
        if ($flow) {
            foreach ($flow as $elem) {
                $fn = str_replace("<PAGE>", $page, $elem);
                if (substr($fn, 0, 1) === "/") $file = "$root/$fn";
                elseif (@$yaml_dir and substr($fn, 0, 2) === "./") $file = "$yaml_dir/$fn";
                else $file = "$route_dir/$fn";
                //$files[] = $file;
                if (file_exists($file)) $files[] = realpath($file);
            }
            //prex($files);
            //return $files;
        }
        /*
        else {
            // API SERVER DEFAULT ROUTE FLOW
            if (@$_HEADER['method']) {
                $method = low($_HEADER['method']);
                $files[] = self::DIR_PAGES . "$uri_page/$page.$method.php";
                $files[] = self::DIR_PAGES . "$uri_page/$page.php";
            } else {
                $files[] = self::DIR_PAGES . "$uri_page/$page.php";
            }
        }*/

        // REFORCE BUG FIX
        /*
        $f_php = self::DIR_PAGES . "$uri_page/$page.php";
        $f_tpl = self::DIR_PAGES . "$uri_page/$page.tpl";
        if (!@$_HEADER and (!file_exists($f_tpl) and !file_exists($f_php))) {
            // MAIN BUILD NOT FOUND
            if ($_BUILD_COUNT === 1) {
                Novel::refreshError("Build error", "Source files for route '" . end($_URI) . "' not found.");
            }
            // CHILD BUILD NOT FOUND
            else {
                Novel::refreshError("Build error", "Snippet '" . end($_URI) . "' not found.");
            }
        }*/
        return $files;
    }
    private function getYamlFromDir($route_dir, $returnFileNameOnly = false)
    {
        global $_APP;
        $yaml = [];
        $array_dir = array_filter(explode("/", $route_dir));
        $array_dir_pointer = $array_dir;
        // !!!
        // LOOP "/" ROUTE_DIR TO FIND .YML !!!
        // !!!
        foreach ($array_dir as $dir_name) {
            $page = end($array_dir_pointer);
            $dir = "/" . implode("/", $array_dir_pointer);
            if ($dir === realpath(self::DIR_PAGES)) continue;
            $fn = "$dir/$page.yml";
            // ROUTE HAVE HIS OWN YAML
            if (file_exists($fn)) {
                if ($returnFileNameOnly) {
                    if (file_exists($fn)) return $fn;
                    else return false;
                }
                $yaml = yaml_parse(file_get_contents($fn));
                return $yaml;
            }
            // ROUTE DONT HAVE YAML
            else {
                $dir_root = realpath(self::DIR_PAGES);
                #echo "$dir $dir_root";
                // CURRENT ROUTE IS A SUB ROUTE
                // SET A NEW YAML FLOW
                if ($dir !== '/' and strpos($dir, $dir_root) === false) {
                    #echo 1; exit;
                    $yaml["PAGES"]["FILE_SEQUENCE"] = ["<PAGE>.php", "<PAGE>.tpl"];
                }
                // CURRENT ROUTE IS A MAIN ROUTE
                else {
                    $yaml = $_APP;
                }
            }
            array_pop($array_dir_pointer);
        }
        if ($returnFileNameOnly) return false;
        return $yaml;
    }
    private function getPageFromDir($route_dir)
    {
        $page = array_filter(explode("/", $route_dir));
        $page = end($page);
        return $page;
    }
    private function getRootFromDir($route_dir)
    {
        $array = array_filter(explode("/", $route_dir));
        $pos = array_search("pages", $array);
        $array = array_slice($array, 0, $pos);
        $page = implode("/", $array);
        return "/$page";
    }
    private function getRootUriFromDir($route_dir)
    {
        $array = array_filter(explode("/", $route_dir));
        $pos = array_search("pages", $array);
        $array = array_slice($array, $pos);
        $page = implode("/", $array);
        return $page;
    }
    private function setDefinitionsFromDir($route_dir, $snippet = false, $snippet_params = [])
    {
        global $_APP, $_URI;
        //$route_root_uri = $this->getRootUriFromDir($route_dir);
        $route_dir = realpath($route_dir);
        $route_root_uri = $this->pageRootUri;
        $page_name = $this->getPageFromDir($route_dir);
        // get curr url
        $protocol = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $uri = @explode("?", $_SERVER['REQUEST_URI'])[0];
        $current_url = $protocol . '://' . $host . $uri;
        //
        // IS NOT A SNIPPET
        if (!$snippet) {
            $key = "PAGE";
            if (!defined('PAGE')) { // prevent warning if build inside another build
                define("URL", $_APP["URL"]);
                define("PAGE", $page_name);
                define("PAGE_DIR", $route_dir);
                define("PAGE_YAML", $route_dir);
                define("PAGE_POST", "$current_url/.post");
                define("PAGE_RUN", "$current_url/.run");
                define("PAGE_URL", $_APP["URL"] . "/$route_root_uri");
            }
        } else {
            $key = "SNIPPET";
            $snippet_uri = $this->getRootUriFromDir($route_dir);
            $current_url = $protocol . '://' . $host . "/" . $snippet_uri;
        }
        // set $_APP[PAGE] for a build inside another build, 
        // define is only for parent build
        $page_array_parts = explode("/", $route_root_uri);
        $_APP[$key] = array(
            "NAME" => $page_name,
            "DIR" => $route_dir,
            "POST" => "$current_url/.post",
            "RUN" => "$current_url/.run",
            "URL" => $_APP["URL"] . "/$route_root_uri",
            "PARTS" => $page_array_parts
        );
        if ($snippet) {
            $_APP[$key]["PAR"] = @$snippet_params;
        }
        //$_BUILDS[] = $_APP["PAGE"]; // for obstart in show.sort
    }
}
