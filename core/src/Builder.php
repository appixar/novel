<?php
//==================================
// SHOW PAGE
//==================================
class Builder extends Arion
{
    public function __construct($snippet = "")
    {
        global $_APP;
        global $_ORDER; // $files[] order to include
        global $_URI; // domain.com/ad/edit/123 => $_URI[0]=ad [1]=edit [2]=123
        global $_PAR; // domain.com/ad/edit/123 => $_PATH[0]=123 (first param after real directory) 
        global $_HEADER; // api server
        global $_BODY; // api server
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
        //else $page = end($_URI);

        //==================================
        // DEFINE $FILES
        // TARGET LIST EXISTS?
        //==================================
        $route_dir = $this->findRouteDir();
        $route_root_uri = $this->getRootUriFromRoute($route_dir);
        $page = $this->getPageFromRoute($route_dir);
        $yaml = $this->getYamlFromRoute($route_dir);
        $files = $this->getFilesFromRoute($route_dir);

        // MERGE $YAML TO $_APP
        if (is_array($yaml)) $_APP = array_merge($_APP, $yaml);

        //==================================
        // GET URL ALIAS IF EXISTS (/.css, /.js)
        //==================================
        $_ALIAS = $this->getAliasFromUri($route_dir);

        // SET $_PAR
        $_PAR = $this->getParamFromRoute($route_dir);

        //==================================
        // PATH_PARAM ENABLED?
        //==================================
        if (!@$_APP['PATH_PARAMS']) {
            if (@$_PAR[0] and ($_PAR[0] !== $page)) {
                http_response_code(404);
                $this->refreshError("Build error", "Route '{$_PAR[0]}' not found. Path Parameters is disabled.");
            }
            // FAKE ALIAS BUGFIX
            if (@array_key_exists(end($_URI), $_APP["ALIAS"])) $aliasExt = end($_URI);
            if (@$aliasExt and !@$_ALIAS) {
                http_response_code(404);
                $this->refreshError("Build error", "Alias source '$page$aliasExt' not found.");
            }
        }
        //==================================
        // DEFAULT LIBS, CORE LIBS & DEFAULT MODULES
        //==================================
        //$this->loadLibs();
        //$this->loadModules();

        // DEFINE UTIL VARIABLES & CONSTANTS
        $this->setDefinitionsFromRoute($route_dir, $snippet);

        //==================================
        // INCLUDE ONLY ALIAS IF EXISTS
        // TARGET FILE IS ALIAS (/.CSS/.JS/.POST)
        //==================================
        if ($_ALIAS) {
            array_shift($_PAR); // remove first element (/.post/)
            include $_ALIAS;
            exit;
        }
        //==================================
        // CONTENT
        //==================================
        $_ORDER = $files;
        $this->requireFiles();
        if (@$_APP["SNIPPET"]) {
            $_APP["SNIPPETS"][] = $_APP["SNIPPET"];
            unset($_APP["SNIPPET"]);
        }
    }
    private function findRouteDir()
    {
        global $_APP, $_URI;
        $uri_page = implode("/", $_URI);
        $uri_page_arr = explode("/", $uri_page); // way to current page
        //$path_x = 0; // level of tree (subdir)

        // LOOP IN ALL ROUTES & SUB ROUTES
        $routes_dir = $this->findPathsByType("routes");
        //prex($routes_dir);
        foreach ($routes_dir as $route_dir) {

            $uri_page_tmp = $uri_page_arr; // save way to find sub/dirs (subtract keys)

            for ($i = intval(count($uri_page_arr) - 1); $i >= 0; $i--) {
                $uri_page_dir = implode("/", $uri_page_tmp);
                $uri_page_curr = $uri_page_tmp[intval(count($uri_page_tmp)) - 1];
                array_pop($uri_page_tmp);
                $path = realpath($route_dir . $uri_page_dir);
                //echo $path."\r\n";
                if (file_exists($path)) {
                    return $path . "/";
                    break;
                }
            }
            //echo "$uri_page_dir $uri_page_curr\r\n";
        }
        exit;
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
        $alias = $_APP["ALIAS"];
        $_ALIAS = false;

        // find alias in end of url
        if (@array_key_exists(end($_URI), $alias)) {
            $ext = end($_URI);
            array_pop($_URI); // remove last element (/.ext)
            $page = end($_URI);
            //$uri_page = implode("/", $_URI);
            $f_name = str_replace("<ROUTE>", $page, $alias[$ext]);
            $f_alias = $route_dir . $f_name;
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
    private function getParamFromRoute($route_dir)
    {
        global $_URI;
        $array = array_filter(explode("/", $route_dir));
        $page_name = end($array);
        // remove elements before /routeName from $_URI
        $pos = array_search($page_name, $_URI);
        $array = array_slice($_URI, $pos);
        //
        $_PAR = $array;
        return $_PAR;
    }
    private function getFilesFromRoute($route_dir)
    {
        // GLOB
        global $_APP, $_HEADER, $_URI, $_PAR, $_ROUTE_ROOT, $_BUILD_COUNT;
        $files = array();

        // GET PAGE DATA
        $page = $this->getPageFromRoute($route_dir);
        $root = $this->getRootFromRoute($route_dir);
        $yaml = $this->getYamlFromRoute($route_dir);

        // MERGE YAML
        if (is_array($yaml)) $_APP = array_merge($_APP, $yaml);
        $flow = @$_APP["FLOW"];

        // FLOW LOOP
        if ($flow) {
            foreach ($flow as $elem) {
                $fn = str_replace("<ROUTE>", $page, $elem);
                if (substr($fn, 0, 1) === "/") {
                    $files[] = $root . $fn;
                }
                $file = $route_dir . $fn;
                if (file_exists($file)) $files[] = $file;
            }
            //prex($files);
            //return $files;
        }
        /*
        else {
            // API SERVER DEFAULT ROUTE FLOW
            if (@$_HEADER['method']) {
                $method = low($_HEADER['method']);
                $files[] = self::DIR_ROUTES . "$uri_page/$page.$method.php";
                $files[] = self::DIR_ROUTES . "$uri_page/$page.php";
            } else {
                $files[] = self::DIR_ROUTES . "$uri_page/$page.php";
            }
        }*/

        // REFORCE BUG FIX
        /*
        $f_php = self::DIR_ROUTES . "$uri_page/$page.php";
        $f_tpl = self::DIR_ROUTES . "$uri_page/$page.tpl";
        if (!@$_HEADER and (!file_exists($f_tpl) and !file_exists($f_php))) {
            // MAIN BUILD NOT FOUND
            if ($_BUILD_COUNT === 1) {
                $this->refreshError("Build error", "Source files for route '" . end($_URI) . "' not found.");
            }
            // CHILD BUILD NOT FOUND
            else {
                $this->refreshError("Build error", "Snippet '" . end($_URI) . "' not found.");
            }
        }*/
        return $files;
    }
    private function getYamlFromRoute($route_dir, $returnFileNameOnly = false)
    {
        global $_APP;
        $yaml = [];
        $array = array_filter(explode("/", $route_dir));
        $page = end($array);
        $dir = "/" . implode("/", $array);
        $fn = "$dir/$page.yml";
        if ($returnFileNameOnly) {
            if (file_exists($fn)) return $fn;
            else return false;
        }
        // ROUTE HAVE HIS OWN YAML
        if (file_exists($fn)) {
            $yaml = yaml_parse(file_get_contents($fn));
        }
        // ROUTE DONT HAVE YAML
        else {
            $dir_root = realpath(self::DIR_ROUTES);
            // CURRENT ROUTE IS A SUB ROUTE
            // SET A NEW YAML FLOW
            if (strpos($dir, $dir_root) === false) {
                $yaml["FLOW"] = ["<ROUTE>.php", "<ROUTE>.tpl"];
            }
            // CURRENT ROUTE IS A MAIN ROUTE
            else {
                $yaml = $_APP;
            }
        }
        return $yaml;
    }
    private function getPageFromRoute($route_dir)
    {
        $page = array_filter(explode("/", $route_dir));
        $page = end($page);
        return $page;
    }
    private function getRootFromRoute($route_dir)
    {
        $array = array_filter(explode("/", $route_dir));
        $pos = array_search("routes", $array);
        $array = array_slice($array, 0, $pos);
        $page = implode("/", $array);
        return "/$page";
    }
    private function getRootUriFromRoute($route_dir)
    {
        $array = array_filter(explode("/", $route_dir));
        $pos = array_search("routes", $array);
        $array = array_slice($array, $pos);
        $page = implode("/", $array);
        return $page;
    }
    private function setDefinitionsFromRoute($route_dir, $snippet = false)
    {
        global $_APP, $_URI;
        $route_root_uri = $this->getRootUriFromRoute($route_dir);
        $page_name = $this->getPageFromRoute($route_dir);
        //
        // IS NOT A SNIPPET
        if (!$snippet) {
            $key = "PAGE";
            if (!defined('PAGE')) { // prevent warning if build inside another build
                define("URL", $_APP["URL"]);
                define("PAGE", $page_name);
                define("PAGE_DIR", $route_dir);
                define("PAGE_YAML", $route_dir);
                define("PAGE_POST", $_APP["URL"] . "/$route_root_uri/.post");
                define("PAGE_RUN", $_APP["URL"] . "/$route_root_uri/.run");
                define("PAGE_URL", $_APP["URL"] . "/$route_root_uri");
            }
        } else {
            $key = "SNIPPET";
        }
        // set $_APP[PAGE] for a build inside another build, 
        // define is only for parent build
        $_APP[$key] = array(
            "NAME" => $page_name,
            "DIR" => $route_dir,
            "POST" => $_APP["URL"] . "/$route_root_uri/.post",
            "RUN" => $_APP["URL"] . "/$route_root_uri/.run",
            "URL" => $_APP["URL"] . "/$route_root_uri"
        );
        //$_BUILDS[] = $_APP["PAGE"]; // for obstart in show.sort
    }
}
