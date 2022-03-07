<?php
//============================================
// Insert user
//============================================
$auth = http::auth(['required' => false]);
$body = http::body();

// Check company (auth)
if (!@$_AUTH['com']) http::die(406, "Company ?");

// Load modules
arion::module('user');
arion::module('addr');

//============================================
// User data
//============================================
$user = new user();
if (!$user->post($body)) http::die(406, $user->error);

//============================================
// Address data
//============================================
$_AUTH['user']['user_id'] = $user->return['user_id']; // auth user_id for addr post
$mod = new addr();
if (!$mod->post($body)) http::die(406, $mod->error);

// return user module success (user_id, user_key)
http::success($user->return);
