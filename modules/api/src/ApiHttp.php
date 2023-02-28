<?php

class ApiHttp extends Api
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
            try {
                $res = new $module($rules);
            } catch (Error $e) {
                http::die(406, "Auth module not found: $module");
            }
            return $res;
        }
    }
    public static function route($conf)
    {
        global $_AUTH, $_APP, $_HEADER, $_PAR;

        // DEFAULT CONF
        if (!@$conf['controller']) http::die(406, 'Route controller not found');
        if (!@$conf['data']) $conf['data'] = 'body';

        // DATA FROM BODY OR POST?
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

        // AUTH
        http::auth();

        // BUGFIX
        if (!empty($_GET)) $data = $_GET;

        // GET ROUTE MODULE NAME
        $class = explode(':', $conf['controller'])[0];

        // LOAD ROUTE MODULE
        try {
            $mod = new $class();
        } catch (Error $e) {
            http::die(406, "Class not found: $class");
        }
        // EXPLICIT FUNCTION = ROUTE MODULE::FUNCTION NAME
        $function = @explode(':', $conf['controller'])[1];
        // ENDPOINT FUNCTION = URL/ROUTE/SMART_FUNCTION
        if (!$function and @$_PAR[0]) {
            $endpointFunction = $_PAR[0];
            $endpointFunction = str_replace("-", "_", $endpointFunction);
            if (method_exists($class, $endpointFunction)) $function = $endpointFunction;
        }
        elseif (!$function) $function = low($_HEADER['method']);

        // SECUTIRY CHECK
        // CHECK PERMISSION TO ROUTE (CLASS/FUNCTION) IN AUTH MODE
        /*
        $module = @$_APP['API_SERVER']['AUTH_MODULE'];
        if ($module) {
            arion::module($module);
            if (method_exists($module, 'license')) {
                $check = $module::license($class, $function);
                if (!$check) http::die(405, 'Denied');
            }
        }*/

        // Return
        if ($function) {
            if (@$mod->$function($data)) {
                if (@$mod->return) http::success($mod->return);
                else http::success();
            } else {
                if (!@$mod->error) $mod->error = "Badly formatted return";
                http::die(406, $mod->error);
            }
        }
        else http::die(406, "Empty controller function");
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
        global $_APP;
        if ($num == 400) $str = 'Bad request';
        if ($num == 401) $str = 'Unauthorized';
        if ($num == 404) $str = 'Not found';
        if ($num == 405) $str = 'Method not allowed';
        if ($num == 406) $str = 'Controller fatal error';
        if (@!$_APP['API_SERVER']['ALWAYS_200'] === true) header("HTTP/1.1 $num $str");
        else header("HTTP/1.1 200");
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
    // VALIDATE INPUT FIELDS
    // ARRAY FORMAT:
    //      'TABLE_NAME_0' => 'REQUIRED FIELD0,FIELD1,...',
    //      'TABLE_NAME_1' => 'REQUIRED FIELD0,FIELD1,...'
    //      'TABLE_NAME_2', (WITHOUT REQUIRED FIELDS)
    public static function validate($receivedData, $dbRequiredFields, $mysqlId = 0)
    {
        global $_APP;

        // GET TABLE YML
        $mysql = $_APP['MYSQL'][$mysqlId];
        if (!@$mysql['PATH']) http::die(406, 'DB Path Missing');
        $path = __DIR__ . '/../../' . $mysql['PATH'] . '/';

        // DONT HAVE REQUIRED FIELDS, ONLY TABLE NAME. TRANSFORM $DBREQUIREDFIELD
        if (@$dbRequiredFields[0]) {
            $dbRequiredFieldsTemp = array();
            for ($i = 0; $i < count($dbRequiredFields); $i++) {
                $dbRequiredFieldsTemp[$dbRequiredFields[0]] = 1;
            }
            $dbRequiredFields = $dbRequiredFieldsTemp;
        }

        // LOOP IN TABLES
        foreach ($dbRequiredFields as $table => $fields) {
            $fp = $path . $table . '.yml';
            $array = Yaml::parse(file_get_contents($fp));
            $array = $array['field'];
            $tableFields = array();

            // BUILD TABLE ARRAY $tableFields
            for ($i = 0; $i < count($array); $i++) {
                foreach ($array[$i] as $fieldName => $fieldValue) {
                    $tableFields[$fieldName] = $fieldValue;
                }
            }

            // LOOP IN ALL FIELDS
            $requiredFields = explode(',', @$fields);
            foreach ($tableFields as $fieldName => $fieldValue) {
                $type = explode(" ", $fieldValue)[0];
                $size = @explode("/", $fieldValue)[1];
                $data = @$receivedData[$fieldName];
                if ($data) $receivedData[$fieldName] = http::validateSpecial($data, $type, $fieldName);
                elseif (in_array($fieldName, $requiredFields)) http::die(400, "Missing field: $fieldName");
            }
        }
        return $receivedData;
    }
    // VALIDATE & TRANSFORM DATA 
    private static function validateSpecial($data, $type, $fieldName)
    {
        switch ($type) {
                //-------------------------------------
                // CHECK EMAIL
                //-------------------------------------
            case "email":
                // check string format
                if (!filter_var($data, FILTER_VALIDATE_EMAIL)) http::die(400, "Invalid $type: $data");
                // check domain
                $domain = explode("@", $data)[1];
                if (!checkdnsrr($domain, 'MX')) http::die(400, "Invalid domain: $data");
                $data = low($data);
                break;
                //-------------------------------------
                // CHECK CPF
                //-------------------------------------
            case "cpf":
                if (!validaCPF($data)) http::die(400, "Invalid $type: $data");
                $data = clean($data);
                break;
                //-------------------------------------
                // UCWORDS (FNAME, LNAME)
                //-------------------------------------
            case "ucwords":
                if (strlen($data) < 3) http::die(400, "$fieldName is too short");
                $data = ucwords(low($data));
                break;
                //-------------------------------------
                // DATE
                //-------------------------------------
            case "date":
                // check str. size
                $dateSizeCheck = false;
                if (strlen($data) === 10 or strlen($data) === 19) $dateSizeCheck = true;
                if (!$dateSizeCheck) http::die(400, "$fieldName invalid lenght");
                // separate date
                $date = explode(' ', $data)[0];
                // time?
                $time = '00:00:00';
                if (@explode(' ', $data)[1] and @explode(':', $data)[1]) $time = explode(' ', $data)[1];
                // format br?
                if (@explode('/', $data)[1]) {
                    $date = @explode('/', $date)[2] . '-' . @explode('/', $date)[1] . '-' . @explode('/', $date)[0];
                }
                // append time
                $data = "$date $time";
                break;
                //-------------------------------------
                // PHONE
                //-------------------------------------
            case "phone":
                $data = clean($data);
                if (strlen($data) !== 11) http::die(400, "$fieldName invalid lenght");
                break;
                //-------------------------------------
                // CPF
                //-------------------------------------
            case "phone":
                $data = clean($data);
                if (strlen($data) !== 11) http::die(400, "$fieldName invalid lenght");
                break;
        }
        return $data;
    }
}
