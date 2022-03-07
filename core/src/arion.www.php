<?php
class www extends arion
{
    public function __construct()
    {
        global $_APP;

        // GET CURRENT URL
        $current_https = "http";
        $current_uri = ($_SERVER["REQUEST_URI"] === '/') ? '' : $_SERVER["REQUEST_URI"];
        // HTTPS BY CLOUDFLARE (PROXY)
        if (isset($_SERVER["HTTP_CF_VISITOR"])) $current_https = json_decode($_SERVER["HTTP_CF_VISITOR"], true)['scheme'];
        if (isset($_SERVER['HTTPS'])) $current_https = "https";
        // URL VARIATIONS
        $current_url = $current_https . "://{$_SERVER["HTTP_HOST"]}{$current_uri}";
        $current_url_pure = "{$_SERVER["HTTP_HOST"]}{$current_uri}";
        $app_url_pure = explode("://", $_APP["URL"])[1];
        $app_url_https = explode("://", $_APP["URL"])[0];

        if (php_sapi_name() !== "cli") {

            // STATIC URL
            if (!@$_APP["DYNAMIC_SUB_DOMAIN"]) {
                if (strpos($current_url, $_APP["URL"]) === false) {
                    //die("Location 1: " . $_APP["URL"] . $current_uri);
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
