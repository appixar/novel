<?php

class http extends arion
{
    public function __construct()
    {
    }
    // check header authentication
    public static function auth($rules = array())
    {
        global $_APP, $_AUTH;
        $_AUTH = false;

        // AUTH MODULE
        $module = @$_APP['API_SERVER']['AUTH_MODULE'];
        if ($module) {
            arion::module($module);
            $res = new $module($rules);
            return $res;
        }
    }
    public static function route($conf)
    {
        global $_HEADER;

        // Default conf
        if (!@$conf['module']) http::die(406, 'Module not found');
        if (!@$conf['data']) $conf['data'] = 'body';
        if (!@$conf['auth']) $conf['auth'] = true;
        //if (!@$conf['upload']) $conf['upload'] = false;

        // Auth
        if ($conf['auth']) {
            if (is_array($conf['auth'])) http::auth($conf['auth']); // rules
            else http::auth();
        }
        // Data
        switch ($conf['data']) {
            case 'body':
                $data = http::body();
                break;
            case 'post':
                $data = http::post();
                break;
            case 'get':
                $data = $_GET;
                break;
        }

        // bugfix
        if (!empty($_GET)) $data = $_GET;

        // Build route
        $class = explode(':', $conf['module'])[0];
        $function = @explode(':', $conf['module'])[1];
        if (!$function) $function = low($_HEADER['method']);

        // Run module
        arion::module($class);
        $mod = new $class();

        // Return
        if ($mod->$function($data)) {
            if (@$mod->return) http::success($mod->return);
            else http::success();
        } else http::die(406, $mod->error);
    }
    public static function get($params = "")
    {
        return self::reqType("GET", $params);
    }
    public static function put($params = "")
    {
        return self::reqType("PUT", $params);
    }
    public static function delete($params = "")
    {
        return self::reqType("DELETE", $params);
    }
    public static function post($params = "")
    {
        return self::reqType("POST", $params);
    }
    private static function checkMethod($type)
    {
        global $_HEADER;
        //echo "{$_HEADER['method']} $type";
        if ($_HEADER['method'] and $_HEADER['method'] !== $type) http::die(405);
    }
    private static function reqType($type, $params = "")
    {
        self::checkMethod($type);
        return self::req($params);
    }
    // return params in array
    public static function req($params = "")
    {
        global $_PAR;
        if ($params == "") return true;
        $req = array();
        $e = explode("/", $params);
        for ($i = 0; $i < count($e); $i++) {
            $name = $e[$i];
            // param. optional
            if (strpos($name, '[') > -1) {
                $name = str_replace('[', '', $name);
                $name = str_replace(']', '', $name);
                $req[$name] = @$_PAR[$i];
            }
            // param. required
            else {
                if (!@$_PAR[$i]) http::die(400, 'Missing parameters.');
                $req[$name] = $_PAR[$i];
            }
        }
        return $req;
    }
    public static function die($num = 406, $msg = '')
    {
        if ($num == 400) $str = 'Bad request';
        if ($num == 401) $str = 'Unauthorized';
        if ($num == 404) $str = 'Not found';
        if ($num == 405) $str = 'Method Not Allowed';
        if ($num == 406) $str = 'Error in Route';
        //header("HTTP/1.1 $num $str");
        header("HTTP/1.1 200");
        if ($msg) $str = addslashes(strip_tags($msg));
        $json = json_encode(array(
            'error' => $num,
            'message' => $str
        ));
        die($json);
    }
    public static function success($msg = '')
    {
        header("HTTP/1.1 200");
        $json['success'] = 1;
        if ($msg and $msg != 1) {
            if (is_array($msg)) {
                foreach ($msg as $k => $v) $json['data'][$k] = $v;
            } else $json['message'] = addslashes(strip_tags($msg));
        }
        $json_encoded = json_encode($json, true);
        // TRY FIX JSON MALFORMED
        if (json_last_error_msg() != 'No error') {
            if (json_last_error_msg() == 'Malformed UTF-8 characters, possibly incorrectly encoded') {
                $json_encoded = json_encode(utf8ize($json));
            }
        }
        // TRY FAIL
        if ($json_encoded == 'null' and json_last_error_msg()) http::die(500, 'JSON format error: ' + json_last_error_msg());
        elseif ($json_encoded == 'null') http::die(500, 'JSON format error');
        // RETURN
        die($json_encoded);
    }
    public static function body()
    {
        global $_HEADER, $_BODY;
        if ($_HEADER['method'] == 'GET') {
            $input = $_GET;
        }
        if (!@$input or $_HEADER['method'] == 'POST') {
            $inputJSON = file_get_contents('php://input');
            $input = json_decode($inputJSON, TRUE); //convert JSON into array
        }
        $_BODY = $input;
        return $input;
    }
}
