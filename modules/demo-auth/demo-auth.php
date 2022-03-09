<?php

class auth
{
    public function __construct($rules = array())
    {
        global $_HEADER, $_APP, $_AUTH, $_BODY;
        $_AUTH = false;
        $_KEY = false;

        // Default rules
        if (!isset($rules['required'])) $rules['required'] = true;
        if (!isset($rules['auth_post'])) $rules['auth_post'] = false; // AUTH DATA IN POST (NOT IN HEADER). UTIL FOR FORMS.

        //---------------------------------------------------------
        // GET HEADER DATA
        // -> Authorization: <user_key>
        //---------------------------------------------------------
        $my = new my();

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
        //---------------------------------------------------------
        // GET USER DATA
        //---------------------------------------------------------
        $user = $my->query("SELECT * FROM bp_user WHERE user_key = :key", ['key' => $_KEY])[0];

        // VERIFY 
        if (!@$user and $rules['required']) http::die(401, 'Invalid key');
        if (@$user["user_status"] != 1 and $rules['required']) http::die(401, 'Disabled account');

        // RETURN
        $_AUTH = $user;
        return $_AUTH;
    }
    public static function license($class, $function)
    {
        die("$class $function");
    }
}
