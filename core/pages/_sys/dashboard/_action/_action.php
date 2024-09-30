<?php
if (@!$_SESSION['_sys']['auth']) exit;

$basedir = realpath(Xplend::DIR_ROOT);
$fn = @$_GET['fn'];
if ($fn) $fn = "$basedir/$fn";
$pid = @$_GET['pid'];
$action = @$_GET['action'];

// ALL SCRIPTS => AUTO PLAY OFF!
if ($action === 'autoplay-off') {
    $fn = "$basedir/src/jobs/stop";
    file_put_contents($fn, 1);
    if (!file_exists($fn)) makeCb(0);
}
// ALL SCRIPTS => AUTO PLAY OFF!
if ($action === 'autoplay-on') {
    $fn = "$basedir/src/jobs/stop";
    @unlink($fn);
    if (file_exists($fn)) makeCb(0);
}
// RUN SCRIPT
if ($action === 'run') {
    $stop_fn = "$fn-stop";
    @unlink($stop_fn);
    exec("php $fn > /dev/null &");
}
// KILL SCRIPT
if ($action === 'kill') {
    exec("kill -9 $pid");
}
// STOP SCRIPT
if ($action === 'stop') {
    $fn = "$fn-stop";
    file_put_contents($fn, 1);
    if (!file_exists($fn)) makeCb(0);
}
// RESTART SCRIPT
if ($action === 'restart') {
    $fn = "$fn-restart";
    $stop_fn = "$fn-stop";
    @unlink($stop_fn);
    file_put_contents($fn, 1);
    if (!file_exists($fn)) makeCb(0);
}
back();
