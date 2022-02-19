<?php
//------------------------------------------------------
// Auth with login / password
//------------------------------------------------------
// Check auth, endpoint rules and get body data
$auth = http::auth(['required' => false]); // false = don't required
$body = http::body();
$data = array(); // return

// Check company (auth)
if (!@$_AUTH['com']) http::die(406, "Company ?");

// Load modules
arion::module('user');
arion::module('addr');

// User data
$user = new user();
if ($user->auth($body)) $data['user'] = $user->return;
else http::die(406, $user->error);

// Append company data (from $auth to $data)
foreach ($auth as $k => $v) $data['user'][$k] = $v;

// User address
$_AUTH['user']['user_id'] = $data['user']['user_id'];
$res = new addr();
if ($res->get()) $data['addr'] = $res->return;
else http::die(406, $res->error);

// Return
http::success($data);
