<?php
class apiServer extends arion
{

    private $table;
    private $furious;
    private $expiry;
    private $table_user;
    private $new_token;

    public function __construct($key = 0)
    {
        global $_APP, $_HEADER, $_AUTH, $_BODY;
        $_AUTH = false;
        // send some CORS headers so the API can be called from anywhere
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Credentials: true');
        header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Auth-Key");
        //header("Access-Control-Allow-Headers: *");
        header("Content-Type: application/json; charset=UTF-8");
        //
        $_HEADER['method'] = $_SERVER["REQUEST_METHOD"];
        $headers = apache_request_headers();
        foreach ($headers as $header => $value) {
            $header = strtolower($header); // bugfix
            $_HEADER[$header] = $value;
        }
        /*$this->table = $_APP['API_SERVER'][0]['TABLE'];
        $this->table_user = $_APP['AUTH'][0]['TABLE'];
        //
        if (!$this->auth()) {
            http::die(401);
        }*/
    }
    private function auth()
    {
        /*global $_HEADER;

        return true;

        // VALIDATE TOKEN
        if (@$_HEADER['AUTH_KEY']) return $this->validate();

        // VALIDATE USER/PASS
        if (@!$_HEADER['USER']) return false;

        // AUTH LOGIN & PASS
        else {
            $user = $_HEADER['USER'];
            $pass = $_HEADER['PASS'];
            $res = jwquery("SELECT user_id FROM {$this->table_user} WHERE user_email = '$user' AND user_pass = '$pass'");
            if (!$res) return false;
            return true;
        }*/
    }
    private function validate()
    {
        /*global $_HEADER;
        $key = $_HEADER['AUTH_KEY'];
        $res = jwquery("SELECT user_id FROM {$this->table} WHERE auth_key = '$key'");
        if (!$res) return false;
        return true;*/
    }
}
