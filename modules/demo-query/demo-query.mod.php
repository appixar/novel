<?php

class query
{
    public $error = false;
    public $return = false;
    //------------------------------------------------
    // GET
    //------------------------------------------------
    public function get($body)
    {
        global $_AUTH;
        if (!@$_AUTH['adm'] or !@$body['query']) return false;

        $query = $body['query'];

        // VALIDATE QUERY
        $disallow = [
            ';', 'drop', 'delete from', '/update', 'insert into', 'truncate', 'alter', 'table', 'create', 'database', '/set ', 'show', 'replace', 'use ', 'flush', 'load', 'pass'
        ];
        for ($i = 0; $i < count($disallow); $i++) {
            $str = $disallow[$i];
            if (stripos($query, $str) > -1) http::die(400, "Disallow: " . trim($str));
        }

        // RUN QUERY
        $my = new my(dynamic());
        $res = $my->query($query);
        
        // CLEAN SENSITIVE DATA
        $sensitive = [
            'key', 'pass', '#fname', '#lname', '#phone', '#cpf'
        ];
        for ($i = 0; $i < count($res); $i++) {
            foreach ($res[$i] as $k => $v) {
                for ($x = 0; $x < count($sensitive); $x++) {
                    if (stripos($k, $sensitive[$x]) > -1) $res[$i][$k] = '?';
                }
            }
        }

        // RETURN
        if (empty($res)) $res = true; // callback bugfix
        $this->return = $res;
        return $this->return;
    }
}
