<?php
//------------------------------------------------------
// Auth with user key on APP start
//------------------------------------------------------
$auth = http::auth(['required' => false]); // false = user key don't required

// Check company (auth)
if (!@$_AUTH['com']) http::die(406, "Company ?");

// Return
$data = $_AUTH;

// Load modules
arion::module('home');
arion::module('addr');

// User data, address + tax
if (@$_AUTH['user']) {
    // Addr
    $res = new addr();
    if ($res->get()) $data['addr'] = $res->return; // $_AUTH = util for taxes
    else http::die(406, $res->error);
}

// Home data
$res = new home();
if ($res->homeApp()) $data['home'] = $res->return;
else http::die(406, $res->error);

// Return
http::success($data);
