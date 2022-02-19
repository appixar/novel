<?php

class api extends arion
{
    public $return = false;
    public $error = false;
    public $info = false;

    public function __construct()
    {
    }
    public function get($endpoint, $body = array())
    {
        return $this->req("GET", $endpoint, $body);
    }
    public function post($endpoint, $body = array())
    {
        return $this->req("POST", $endpoint, $body);
    }
    public function req($method, $endpoint, $body = array(), $api_id = 0)
    {
        global $_APP, $_SESSION;

        // URL
        $url = $_APP['API_CLIENT'][$api_id]['URL'];
        $url .= $endpoint;

        // Data & headers
        $headers = array('Content-Type: application/json');
        $h = $_APP['API_CLIENT'][$api_id]['HEADER'];
        $h = $_SESSION[$h];
        foreach ($h as $k => $v) $headers[] = "$k: $v";
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
