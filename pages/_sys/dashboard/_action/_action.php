<?php
$stop_fn = __DIR__ . '/../../../src/jobs/stop';
if (@$_GET['autoplay'] == 0) file_put_contents($stop_fn, 1);
if (@$_GET['autoplay'] == 1) @unlink($stop_fn);
if (@$_GET['run']) {
    $fn = @$_GET['run'];
    $dir = __DIR__ . '/../../../';
    $dir = realpath($dir);
    exec("php $dir/$fn > /dev/null &");
}
if (@$_GET['kill']) {
    $pid = @$_GET['kill'];
    exec("kill -9 $pid");
}
back();