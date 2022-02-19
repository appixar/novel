<?php
header('Content-type: text/html');

// get codes
if (!@$_POST['com_code'] or !@$_POST['rec_code'] or strlen($_POST['user_pass']) < 6) {
    header("Location: {$_SERVER['HTTP_REFERER']}?err=1");
    exit;
}
$com_code = clean_spaces(clean($_POST['com_code']));
$rec_code = clean_spaces(clean($_POST['rec_code']));
$new_pass = $_POST['user_pass'];

// get config
$my = new my(['id' => 'dynamic', 'wildcard' => $com_code]);
$conf = $my->query("SELECT * FROM qmz_config WHERE conf_id = 1");
if (empty($conf)) die('<pre>COMPANY NOT FOUND');

// check code
$res = $my->query("SELECT * FROM qmz_user_recover WHERE rec_code = '$rec_code' AND rec_status = 1");
if (empty($res)) {
    header("Location: {$_SERVER['HTTP_REFERER']}?err=2");
    exit;
}
// get user
$user = $my->query("SELECT user_id FROM qmz_user WHERE user_id = '{$res[0]['user_id']}'");
if (empty($user)) {
    header("Location: {$_SERVER['HTTP_REFERER']}?err=3");
    exit;
}

// change pass
$user_key = geraSenha(32, true, true, true); // update key too
$upd = array(
    'user_key' => $user_key,
    'user_pass' => password_hash($new_pass, PASSWORD_DEFAULT),
);
// save data
$my->update('qmz_user', $upd, array('user_id' => $user[0]['user_id']));

// disable code
$my->update('qmz_user_recover', array('rec_status' => 2), array('rec_code' => $rec_code));

header("Location: https://$com_code.qmoleza.com");
