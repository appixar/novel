<?php
function demoLinks($subdir = "")
{
    global $_APP;
    if ($subdir) {
        $subdir = "$subdir/";
    }
    $dir = __DIR__ . "/../../routes/dev/demo/" . $subdir;
    $ls = scandir($dir);

    // home first
    if (!$subdir) {
        $url = "{$_APP["URL"]}/dev/demo/home";
        $class = "";
        if ($url == PAGE_URL) {
            $class = "active";
        }
        echo "<a href='$url' class='$class'>HOME</a> - ";
    }

    // others links
    for ($i = 2; $i < count($ls); $i++) {
        $fn = $ls[$i];
        if ($fn != "home") {
            $tpl = "$dir/$fn/$fn.tpl";
            //echo $tpl."<br>";
            if (file_exists($tpl)) {
                $url = "{$_APP["URL"]}/dev/demo/" . $subdir . $fn;
                $class = "";
                if ($url == PAGE_URL) {
                    $class = "active";
                }
                echo "<a href='$url' class='$class'>" . strtoupper($fn) . "</a> - ";
            }
        }
    }
}
