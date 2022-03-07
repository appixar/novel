<?php
class www extends arion
{
    public function __construct()
    {
        global $_APP;

        // GET CURRENT URL
        $https = "http";
        // HTTPS BY CLOUDFLARE (PROXY)
        if (isset($_SERVER["HTTP_CF_VISITOR"])) {
            $https = json_decode($_SERVER["HTTP_CF_VISITOR"], true)['scheme'];
        }
        if (isset($_SERVER['HTTPS'])) {
            $https = "https";
        }
        $current_url = $https . "://{$_SERVER["HTTP_HOST"]}{$_SERVER["REQUEST_URI"]}";

        if (php_sapi_name() != "cli" and strpos($current_url, $_APP["URL"]) === false) {
            header("Location: " . $_APP["URL"] . $_SERVER["REQUEST_URI"]);
            exit;
        }
    }
}
