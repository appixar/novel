<?php
class UrlFormatter extends Novel
{
    public function __construct()
    {
        global $_APP, $_URI;

        if (!@$_APP['URL']) return false;

        // GET FULL URL
        $protocol = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        $url = $protocol . '://' . $host . $uri;
        // URL ENDS WITH "/" ??? FIX IT.
        if ($uri !== '/' and @substr($uri, -1) == '/') {
            // DONT REDIRECT TO PREVENT LOST POST DATA. A LOT OF TIME TO DISCOVER WHY.
            echo json_encode(['error' => 'Incorrect URL format (last /).']);
            //$url = substr($url, 0, strlen($url) - 1);
            //header("Location: $url");
            exit;
        }
        // DEFINE $_URI
        $uri_str = $_SERVER['REQUEST_URI'];
        $uri_str = substr($uri_str, 1); // bugfix = remove first "/"
        $uri_str = explode("?", $uri_str)[0];
        $_URI = explode("/", $uri_str);
        if (!@$_URI[0]) $_URI = array("home");

        // FIX $_URI => CLEAN BASE URL (REMOVE URL REAL PATH)
        if (@$_APP['URL']) {
            $url_parts = explode("/", $_APP['URL']);
            // find url real path
            if (@$url_parts[3]) {
                array_splice($url_parts, 0, 3); // remove 3 first
                $uri_parts = explode("/", $_SERVER['REQUEST_URI']);
                for ($i = 0; $i < count($url_parts); $i++) {
                    $url_part = $url_parts[$i];
                    if ($url_part === $_URI[$i]) unset($_URI[$i]);
                }
            }
            $_URI = array_values($_URI);
        }

        // FORCE URL?
        if (isset($_APP['FORCE_URL']) and !$_APP['FORCE_URL']) return false;

        // GET CURRENT URL
        $current_https = "http";
        $current_uri = ($_SERVER["REQUEST_URI"] === '/') ? '' : $_SERVER["REQUEST_URI"];

        // HTTPS BY CLOUDFLARE (PROXY)
        if (isset($_SERVER["HTTP_CF_VISITOR"])) $current_https = json_decode($_SERVER["HTTP_CF_VISITOR"], true)['scheme'];
        if (isset($_SERVER['HTTPS'])) $current_https = "https";

        // URL VARIATIONS
        $current_url = $current_https . "://{$_SERVER["HTTP_HOST"]}{$current_uri}";
        $current_url_pure = "{$_SERVER["HTTP_HOST"]}";
        $app_url_pure = explode("://", $_APP["URL"])[1];
        $app_url_https = explode("://", $_APP["URL"])[0];

        if (php_sapi_name() !== "cli") {
            // STATIC URL
            if (!@$_APP["DYNAMIC_SUB_DOMAIN"]) {
                if (strpos($current_url, $_APP["URL"]) === false) {
                    header("Location: " . $_APP["URL"] . $current_uri);
                    exit;
                }
            }
            // DYNAMIC URL
            else {
                // CHECK HTTPS
                if ($current_https !== $app_url_https) {
                    //die("Location 2: " . $app_url_https . '://' . $current_url_pure . $current_uri);
                    header("Location: " . $app_url_https . $current_url_pure . $current_uri);
                    exit;
                }
                // CHECK URL STRING
                if (strpos($current_url_pure, $app_url_pure) === false) {
                    //die("Location 3: " . $app_url_https . '://' . $current_url_pure . $current_uri);
                    header("Location: " . $_APP["URL"] . $current_uri);
                    exit;
                }
                // UPDATE APP URL
                $_APP["URL"] = $app_url_https . '://' . $current_url_pure;
            }
        } // WWW
    }
}
