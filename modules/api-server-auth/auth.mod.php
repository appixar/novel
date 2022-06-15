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
        if (!$_KEY and $rules['required']) http::die(401, 'User key required');
        elseif (!$_KEY) goto auth_end;

        //---------------------------------------------------------
        // GET USER DATA
        //---------------------------------------------------------
        $my = new my();
        $qr = "SELECT c.*, u.* FROM st_user u "
            . "INNER JOIN st_company c ON c.com_id = u.com_id "
            . "WHERE user_key = :key";
        $user = $my->query($qr, ['key' => $_KEY]);

        // VERIFY USER KEY
        if (!@$user[0] and $rules['required']) http::die(401, 'Invalid user key');
        if (@$user[0]["user_status"] != 1 and $rules['required']) http::die(401, 'Disabled account');

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
