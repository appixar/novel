<?php
function arion_autoload($class_name, $type = '')
{
    $class_name = str_replace('\\', '/', $class_name);
    $class_name = str_replace('_', '/', $class_name);
    $dir_list = Arion::dir();
    $dir_root = realpath(__DIR__ . "/../../");

    if ($type) $dir_list = [$type];

    foreach ($dir_list as $dir) {

        // controllers/ClassName.php
        $fn = "$dir_root/$dir/$class_name.php";
        if (file_exists($fn)) {
            require_once($fn);
            break;
        }
        $path = "$dir_root/$dir/$class_name/";

        // controllers/ClassName/autoload.php
        $fn = $path . "autoload.php";
        if (file_exists($fn)) {
            require_once($fn);
            break;
        }
        // controllers/ClassName/ClassName.php
        $fn = $path . $class_name . ".php";
        if (file_exists($fn)) {
            require_once($fn);
            break;
        }
    }

    // MORE DEEP IN MODULES (modules/ModuleName/controllers/ClassName.php, etc)
    $modules = @array_diff(@scandir(Arion::DIR_MODULES), [".", ".."]);
    foreach ($modules as $module) {
        $module_path = Arion::DIR_MODULES . $module;
        if (!@is_dir($module_path)) continue;
        foreach ($dir_list as $dir) {
            $full_path = "$module_path/$dir/$class_name.php";
            if (file_exists($full_path)) {
                require_once($full_path);
                break;
            }
        }
    }
}
spl_autoload_register('arion_autoload');

/*
class Autoload extends Arion {
    public function __construct($class_name)
    {
        
    }
}*/