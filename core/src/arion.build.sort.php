<?php
// BUFFER CALLBACK
function buffer_callback($buffer)
{
    global $_APP, $_BUILDS, $_SCRIPTS, $_STYLES;

    // SCRIPTS FROM ARION->SCRIPTS(ARRAY) (PAGE.PHP)
    $before = "</body>";
    //if (strpos($buffer, "<link/>")) $before = "<link/>";
    foreach ($_SCRIPTS as $page => $files) {
        $buffer = (str_replace($before, "\r\n\r\n<!-- arion::scripts() from $page -->$before\r\n", $buffer));
        for ($i = 0; $i < count($files); $i++) {
            // script don't exists on page yet
            if (!strpos($buffer, "src='{$files[$i]}'")) {
                $buffer = (str_replace($before, "<script src='{$files[$i]}' type='text/javascript'></script>\r\n$before", $buffer));
            }
        }
    }
    // STYLES FROM ARION->SCRIPTS(ARRAY) (PAGE.PHP)
    $before = "</head>";
    //if (strpos($buffer, "<link/>")) $before = "<link/>";
    foreach ($_STYLES as $page => $files) {
        $buffer = (str_replace($before, "\r\n\r\n<!-- arion::styles() from $page -->$before\r\n", $buffer));
        for ($i = 0; $i < count($files); $i++) {
            // style don't exists on page yet
            if (!strpos($buffer, "href='{$files[$i]}'")) {
                $buffer = (str_replace($before, "<link href='{$files[$i]}' rel='stylesheet'>\r\n$before", $buffer));
            }
        }
    }

    // SCRIPTS & STYLES FROM .JS & .CSS FILES INSIDE ROUTE
    for ($i = 0; $i < count($_BUILDS); $i++) {
        $page = $_BUILDS[$i]["NAME"];
        $way = $_BUILDS[$i]["WAY"];
        $url = $_BUILDS[$i]["URL"];
        if (file_exists($way . ".css")) {
            $buffer = (str_replace("</head>", "\r\n\r\n<!-- arion::build($page) styles -->\r\n<link href='$url/.css' rel='stylesheet'>\r\n\r\n</head>", $buffer));
        }
        if (file_exists($way . ".js")) {
            $buffer = (str_replace("</body>", "\r\n\r\n<!-- arion::build($page) scripts -->\r\n<script src='$url/.js' type='text/javascript'></script>\r\n\r\n</body>", $buffer));
        }
    }
    // FIX SRC TARGET - INCLUDE HTTP://BASEURL
    //--------
    // src
    //--------
    // save http
    /*$buffer = (str_replace("src='https://", "{src_https}", $buffer));
    $buffer = (str_replace('src="http://', "{src_http}", $buffer));
    // fix blank
    $buffer = (str_replace("src='", "src='" . URL . "/", $buffer));
    $buffer = (str_replace('src="', 'src="' . URL . "/", $buffer));
    // restore http
    $buffer = (str_replace("{src_https}", "src='https://", $buffer));
    $buffer = (str_replace("{src_http}", 'src="http://', $buffer));
    //--------
    // href
    //--------
    // save http
    //$buffer = (str_replace("href='https://", "{href_https}", $buffer));
    //$buffer = (str_replace('href="http://', "{href_http}", $buffer));
    // fix blank
    $buffer = (str_replace("href='", "href='" . URL . "/", $buffer));
    $buffer = (str_replace('href="', 'href="' . URL . "/", $buffer));
    // restore http
    $buffer = (str_replace("{href_https}", "href='https://", $buffer));
    $buffer = (str_replace("{href_http}", 'href="http://', $buffer));*/

    return $buffer;
}

class sort extends build
{
    public function __construct()
    {
        global $_APP, $_ORDER, $_PAR, $_URI;

        foreach ($GLOBALS as $k => $v) {
            global ${$k};
        }

        if ($_APP["INCLUDE_CSS_JS"]) {
            ob_start("buffer_callback");
        }

        $_APP["FLOW_X"] = 0; // flow sort order

        foreach ($_ORDER as $file) {
            if (file_exists($file)) {
                $start = microtime(true); // inicia cronômetro
                debug(__CLASS__, "$file...", "muted");

                include $file;
                $_APP["FLOW_X"]++;

                $time_elapsed_secs = number_format((microtime(true) - $start), 4);
                debug(__CLASS__, "$file in $time_elapsed_secs s");
            }
        }
        if ($_APP["INCLUDE_CSS_JS"]) {
            ob_end_flush();
        }
    }
}
