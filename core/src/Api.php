<?php
class Api extends Xplend
{
    public function __construct($condition_by_route = false)
    {
        if (!$condition_by_route) Api::buildApiHeaders();
    }
    public static function buildApiHeaders()
    {
        global $_APP, $_HEADER, $_AUTH, $_BODY;
        $_AUTH = false;
        header("Content-Type: application/json; charset=UTF-8");
        // get header data
        $_HEADER['method'] = $_SERVER["REQUEST_METHOD"];
        $headers = apache_request_headers();
        foreach ($headers as $header => $value) {
            $header = strtolower($header); // bugfix
            $_HEADER[$header] = $value;
        }
    }
}
