<?php

class auth
{
    public function __construct($rules = array())
    {
        global $_HEADER, $_APP, $_AUTH, $_BODY, $_URI;
        $_AUTH = false;
        $_KEY = false;

        // Default rules
        if (!isset($rules['required'])) $rules['required'] = false;
        if (!isset($rules['auth_post'])) $rules['auth_post'] = false; // AUTH DATA IN POST (NOT IN HEADER). UTIL FOR FORMS.

        //---------------------------------------------------------
        // GET HEADER DATA
        // -> Authorization: <user_key>
        //---------------------------------------------------------

        // HEADER AUTH DATA
        if (!$rules['auth_post']) {
            if (!@$_HEADER['authorization'] and $rules['required']) http::die(401);
            if (!@$_HEADER['authorization'] and !$rules['required']) return true;
            $_KEY = $_HEADER['authorization'];
        }
        // $_POST AUTH DATA
        else {
            if (!@$_POST['auth']) http::die(401);
            $_KEY = $_POST['auth'];
        }
        // DECODE HEADER KEY
        $_USER_KEY = explode(":", base64_decode($_KEY))[0];
        $_SESS_KEY = explode(":", base64_decode($_KEY))[1];
        if (!$_SESS_KEY) goto auth_end;

        //---------------------------------------------------------
        // GET USER DATA
        //---------------------------------------------------------
        $my = new my();
        $user = $my->query("SELECT * FROM ox_user WHERE user_key = :key", ['key' => $_USER_KEY]);

        // VERIFY USER KEY
        if (!@$user[0] and $rules['required']) http::die(401, 'Invalid user key');
        if (@$user[0]["user_status"] != 1 and $rules['required']) http::die(401, 'Disabled account');

        // VERIFY SESSION KEY
        if ($user) {
            $sess = $my->query("SELECT session_id, session_status FROM ox_user_session WHERE session_key = :key AND user_id = {$user[0]["user_id"]}", ['key' => $_SESS_KEY]);
            if (!@$sess[0]) http::die(401, 'Invalid session key');
            if (@$sess[0]['session_status'] != 1) http::die(401, 'Expired session key');
        }

        // REGISTER ACTIVITY
        if (!empty(@$sess)) {
            
            // beautify db
            if ($_POST) $post_json = json_encode(@$_POST, true);
            else $post_json = '';

            // data
            $ins = array(
                'user_id' => $user[0]["user_id"],
                'session_id' => $sess[0]['session_id'],
                'log_endpoint' => implode('/', $_URI),
                'log_method' => @$_HEADER['method'],
                'log_header' => json_encode($_HEADER, true),
                'log_data_body' => json_encode(@$_BODY, true),
                'log_data_post' => $post_json,
                'log_date_insert' => date("Y-m-d H:i:s")
            );
            $my->insert('ox_user_session_log', $ins);
        }
        // RETURN
        $_AUTH = @$user[0];
        auth_end:
        return $_AUTH;
    }
    public static function license($class, $function)
    {
        return true;
        global $_AUTH, $_URI, $_HEADER;
        include __DIR__ . '/license.array.php';

        //----------------------------------------------
        // FREE ROUTES
        //----------------------------------------------
        // CHECK BY CLASS & FUNCTION
        //if (in_array($uri, "$class:$function") return true;

        // ... OR CHECK BY URI (ENDPOIT ROUTE)
        $uri = implode('/', $_URI);
        $uri_method = $uri . "." . low($_HEADER['method']);
        if (in_array($uri, $license['free']) or in_array($uri_method, $license['free'])) return true;

        //----------------------------------------------
        // PRIVATE ROUTES
        //----------------------------------------------
        if (in_array($uri, $license['private']) or in_array($uri_method, $license['private'])) {
            if (@$_AUTH) return true;
            else return false;
        }

        return false;
    }
}
