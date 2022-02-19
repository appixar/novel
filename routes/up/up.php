<?php
/*----------------------------------------
    POST DATA:
    module = 'banner:post'
    back = 'banner'
    perpendName = 'jucy.banner.'
----------------------------------------*/
// AUTH
http::auth(['auth_post' => true]);
if (!@$_AUTH['adm'] and !@$_AUTH['user']) return false;

// POST FIELDS
$auth = @$_POST['auth'];
$back = @$_POST['back'];
$module = @$_POST['module'];
$prepend = @$_POST['prependName']; // append to random name
//$name = @$_POST['name']; // static new name (not random)
$path = clean(@$_POST['path']);
//
unset($_POST['auth']);
unset($_POST['back']);
unset($_POST['module']);
unset($_POST['prependName']);
//unset($_POST['name']);
unset($_POST['path']);

// FORM FILE FIELD NAME
$field = '';
foreach ($_FILES as $k => $v) {
    $field = $k;
    break;
}
// UPLOAD
arion::module('upload');
$conf = [
    'field' => $field,
    'subpath' => $path,
    'prependName' => $prepend, // prepend to random name
    //'name' => $name // static name (not random)
];
if (@$_PAR[0] === 'json') $conf['type'] = 'json';
elseif (@$_PAR[0] === 'pdf') $conf['type'] = 'pdf';

// SAVE FILE
$up = upload::save($conf);

// UPLOAD FAIL
if (@$up['error'] or !@$up['success']) {
    if ($back) back(true, $back . '?error=' . $up['error']);
    else http::die(406, $up['error']);
    exit;
}

// INVOKE DYNAMIC MODULE
$class = explode(':', $module)[0];
$function = explode(':', $module)[1];

// RUN DYNAMIC MODULE
arion::module($class);
$mod = new $class();

// INCLUDE FIELD NAME TO DYNAMIC MODULE
$_POST[$field] = $up['success'];

// SUCCESS
if ($mod->$function($_POST)) {
    if ($back) back(true, $back . '?success=1');
    else http::success($up['success']);
}
// FAIL
else {
    if ($back) back(true, $back . '?error=Desculpe, tente novamente mais tarde');
    else http::die(406);
}