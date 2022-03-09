<?php
//==================================
// SHOW PAGE
//==================================
class build extends arion
{
    public function __construct($page = "")
    {
        global $_APP;
        global $_GET;
        global $_SCRIPTS, $_STYLES;
        global $_ORDER; // $files[] order to include
        global $_URI; // domain.com/ad/edit/123 => $_URI[0]=ad [1]=edit [2]=123
        global $_PAR; // domain.com/ad/edit/123 => $_PATH[0]=123 (first param after real directory) 
        global $_HEADER; // api server
        global $_BODY; // api server
        //
        global $_BUILDS; // for ob_start in show.sort
        global $_BUILD_COUNT;
        $_BUILD_COUNT++;
        //
        extract($GLOBALS, EXTR_REFS | EXTR_SKIP);
        //
        //==================================
        // DEFINE TARGET DIR & FILE
        //==================================
        if (isset($_GET['uri'])) {
            $_URI = explode("/", $_GET['uri']);
        }
        // PRESERVE GET PARAMETERS (DESPITE .HTACCESS)
        parse_str(@explode("?", $_SERVER['REQUEST_URI'])[1], $_GET);
        $query_string = @explode("?", $_SERVER['REQUEST_URI'])[1];
        if ($query_string) $query_string = "?$query_string";
        //
        if (!@$_URI[0]) {
            $_URI = array("home");
        }
        if ($page) {
            $_URI = explode("/", $page);
        }
        // URL ENDS WITH "/" ??? FIX IT.
        if (@substr($_SERVER['REDIRECT_URL'], -1) == '/') {
            array_pop($_URI);
            $uri_page = implode("/", $_URI);
            header("Location: {$_APP["URL"]}/$uri_page$query_string");
            exit;
        }
        //==================================
        // EXCEPTION = URL ALIAS (/.css, /.js)
        //==================================
        $alias = $_APP["ALIAS"];
        // find alias in url
        $_ALIAS = false;
        if (@array_key_exists(end($_URI), $alias)) {
            $ext = end($_URI);
            if ($ext == ".css") header("Content-type: text/css; charset: UTF-8; Cache-control: must-revalidate");
            if ($ext == ".js") header('Content-Type: application/javascript');
            array_pop($_URI); // remove last element (/.ext)
            $page = end($_URI);
            $uri_page = implode("/", $_URI);
            $f_name = str_replace("<ROUTE>", $page, $alias[$ext]);
            $f_alias = self::DIR_ROUTES . "$uri_page/$f_name";
            if (file_exists($f_alias)) {
                // if $f_alias is set, in the end of file will have a include + exit;
                $_ALIAS = $f_alias;
                if (function_exists('jwsafe')) jwsafe();
            }
        }
        //==================================
        // DEFINE PAGE & DEFINE $_PAR
        //==================================
        $uri_page = implode("/", $_URI); // REAL WAY OF THE PAGE
        $uri_dir = "";
        $uri_arr = $_URI;
        $par_arr = array();
        for ($i = intval(count($_URI) - 1); $i >= 0; $i--) {
            $uri_dir = self::DIR_ROUTES . $uri_page;
            if (is_dir($uri_dir)) {
                goto jump;
            }
            array_unshift($par_arr, end($uri_arr));
            array_pop($uri_arr);
            $uri_page = implode("/", $uri_arr);
        }
        jump:
        $_PAR = $par_arr;
        $page = end($uri_arr);

        //==================================
        // FIND PAGE .YML
        //==================================
        $uri_page_arr = explode("/", $uri_page); // way to current page
        $uri_page_tmp = $uri_page_arr; // save way to find sub/dirs (subtract keys)
        $path_x = 0; // level of tree (subdir)
        for ($i = intval(count($uri_page_arr) - 1); $i >= 0; $i--) {
            $uri_page_find = implode("/", $uri_page_tmp);
            $uri_page_curr = $uri_page_tmp[intval(count($uri_page_tmp)) - 1];
            $f_yml = self::DIR_ROUTES . "$uri_page_find/$uri_page_curr.yml";
            if (file_exists($f_yml)) {
                $yaml = arion::yml($f_yml);
                if (is_array($yaml)) {
                    if (!isset($yaml['YML_ISOLATED']) or !$yaml['YML_ISOLATED'] or $path_x == 0) {
                        $_APP = array_merge($_APP, $yaml);
                        if (isset($yaml['RESET_ROUTE_ROOT'])) {
                            $new_route_root = dirname($f_yml);
                        }
                    }
                }
            }
            array_pop($uri_page_tmp);
            $path_x++;
        }

        //==================================
        // TARGET FILES EXISTS?
        //==================================
        if ($_BUILD_COUNT > 1) $order = $_APP["FLOW_INNER"]; // changes flow if is a build inside another build
        else $order = $_APP["FLOW"];
        $files = array();
        if ($order) {
            for ($i = 0; $i < count($order); $i++) {
                $f_name = str_replace("<ROUTE>", $page, $order[$i]);
                $uri_tmp = "$uri_page/";
                if (substr($f_name, 0, 1) == "/") {
                    // route root has reseted by new yml
                    if (isset($new_route_root)) {
                        $files[] = $new_route_root . $f_name;
                        goto jump_file;
                    }
                    $uri_tmp = "";
                }
                $files[] = self::DIR_ROUTES . $uri_tmp . $f_name;
                jump_file:
            }
            //pre($files);exit;
        } else {
            // API DEFAULT ROUTE FLOW
            if (@$_HEADER['method']) {
                $method = low($_HEADER['method']);
                $files[] = self::DIR_ROUTES . "$uri_page/$page.$method.php";
                $files[] = self::DIR_ROUTES . "$uri_page/$page.php";
            } else {
                $files[] = self::DIR_ROUTES . "$uri_page/$page.php";
            }
        }
        $f_php = self::DIR_ROUTES . "$uri_page/$page.php";
        $f_tpl = self::DIR_ROUTES . "$uri_page/$page.tpl";
        if (!@$_HEADER['method'] and (!file_exists($f_tpl) and !file_exists($f_php))) {
            // MAIN BUILD NOT FOUND
            if ($_BUILD_COUNT == 1) {
                //http_response_code(404);
                //exit;
                $this->refreshError("Build error", "Page '" . end($_URI) . "' not found");
            }
            // CHILD BUILD NOT FOUND
            else {
                $this->refreshError("Build error", "Snippet '" . end($_URI) . "' not found");
            }
        }
        //echo $f_php;exit;

        //==================================
        // DEFAULT LIBS, CORE LIBS & DEFAULT MODULES
        //==================================
        $this->loadLibs();
        $this->loadModules();

        //==================================
        // DEFINE UTIL VARIABLES
        //==================================
        if (!defined('PAGE')) { // prevent warning if build inside another build
            define("URL", $_APP["URL"]);
            define("PAGE", $uri_page);
            define("PAGE_DIR", self::DIR_ROUTES . "$uri_page/");
            define("PAGE_POST", $_APP["URL"] . "/$uri_page/.post");
            define("PAGE_RUN", $_APP["URL"] . "/$uri_page/.run");
            define("PAGE_URL", $_APP["URL"] . "/$uri_page");
            // DEPRECATED
            //define("PAGE_DIR_URL", $_APP["URL"] . "/routes/$uri_page"); 
            //define("PAGE_WAY", "routes/$uri_page/$page"); 
            //define("PAGE_WAY_URL", $_APP["URL"] . "/routes/$uri_page/$page"); 
        }
        // set $_APP[PAGE] for a build inside another build, 
        // define is only for parent build
        $_APP["PAGE"] = array(
            "NAME" => $uri_page,
            "DIR" => self::DIR_ROUTES . "$uri_page/",
            "POST" => $_APP["URL"] . "/$uri_page/.post",
            "RUN" => $_APP["URL"] . "/$uri_page/.run",
            "URL" => $_APP["URL"] . "/$uri_page"
            // DEPRECATED
            //"DIR_URL" => $_APP["URL"] . "/routes/$uri_page",
            //"WAY" => "routes/$uri_page/$page",
            //"WAY_URL" => $_APP["URL"] . "/routes/$uri_page/$page",
        );
        $_BUILDS[] = $_APP["PAGE"]; // for obstart in show.sort

        //pre($_APP);exit;
        //==================================
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

        //echo count($_BUILDS);
        //pre($_ORDER); //exit;
        new sort();
    }
}
