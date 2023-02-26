<?php
class ApiServer extends Arion
{
    public function __construct($key = 0)
    {
        global $_APP, $_HEADER, $_AUTH, $_BODY;
        $_AUTH = false;
        // send some CORS headers so the API can be called from anywhere
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Credentials: true');
        header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Auth-Key");
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