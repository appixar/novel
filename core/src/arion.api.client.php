<?php

class api extends arion
{
    public $return = false;
    public $error = false;
    public $info = false;

    public function __construct()
    {
    }
    public function get($endpoint, $body = array(), $conf = array())
    {
        return $this->req("GET", $endpoint, $body, $conf);
    }
    public function post($endpoint, $body = array(), $conf = array())
    {
        return $this->req("POST", $endpoint, $body, $conf);
    }
    public function put($endpoint, $body = array(), $conf = array())
    {
        return $this->req("PUT", $endpoint, $body, $conf);
    }
    public function delete($endpoint, $body = array(), $conf = array())
    {
        return $this->req("DELETE", $endpoint, $body, $conf);
    }
    public function req($method, $endpoint, $body = array(), $conf = array())
    {
        global $_APP, $_SESSION;

        if (@!$conf['api_id']) $conf['api_id'] = 0;
        if (@!$conf['upload']) $conf['upload'] = false;

        // URL
        $url = $_APP['API_CLIENT'][$conf['api_id']]['URL'];
        $url .= $endpoint;

        // Data & headers
        $headers = array('Content-Type: application/json');
        $h = $_APP['API_CLIENT'][$conf['api_id']]['HEADER'];
        $h = @$_SESSION[$h];
        if ($h) foreach ($h as $k => $v) $headers[] = "$k: $v";

        if ($conf['upload'] and !empty($_FILES)) {
            /*$file_tmp = $_FILES[$conf['upload']]['tmp_name'];
            $file_name = $_FILES[$conf['upload']]['name'];
            $cf = new CURLFile($file_tmp, mime_content_type($file_tmp), $file_name);
            $body['file'] = $cf;*/
        }
        $body = json_encode($body);

        // Send curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_STDERR, fopen('php://stderr', 'w'));
        //
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        //
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, "Thunder Client (https://www.thunderclient.io)");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $res = curl_exec($ch);

        // Return
        $this->info = curl_getinfo($ch);
        if (curl_error($ch)) {
            $this->error = curl_error($ch);
            return false;
        }
        $this->return = json_decode($res, true);
        if (json_last_error()) $this->return = $res;
        curl_close($ch);

        // Return
        return $this->return;
    }
}
