<?php

class cart
{
    public $error = false;
    public $return = false;

    //------------------------------------------------
    // UPLOAD CART ATTACH FROM USER
    //------------------------------------------------
    public function postImage($body)
    {
        global $_AUTH, $_APP;
        if (!@$_AUTH['user'] or !@$body['att_file'] or !@$body['cart_id']) return false;

        // DATA
        $data = [
            'cart_id' => $body['cart_id'],
            'user_id' => $_AUTH['user']['user_id'],
            'att_file' => $_APP['URL'] . '/upload/cart_attach/' . $body['att_file'],
            'att_date_insert' => date("Y-m-d H:i:s")
        ];

        // QUERY
        $my = new my(dynamic());
        $my->insert("qmz_cart_attach", $data);

        return true;
    }
}
