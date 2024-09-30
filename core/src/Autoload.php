<?php
function xplend_autoload($class_name_or_class_path)
{
    // GET SRC DIR LIST
    $dir_list = Xplend::get_dir_list();
    $dir_root = realpath(__DIR__ . "/../../");

    // FIX NAME
    $class_name = '';
    $str = $class_name_or_class_path;
    $str = str_replace('\\', '/', $str);
    $str = str_replace('//', '/', $str);

    // SET CLASS NAME
    // CLASS NAME IS FULL A PATH. LAST STR FROM ARRAY "/" = CLASS NAME.
    $dir_array = explode('/', $str);
    if (@$dir_array[1]) {
        $class_name = array_pop($dir_array);
        $dir_list = [implode('/', $dir_array)]; // UNIQUE ELEMENT IN ARRAY (SPECIFIC FULL PATH)
    }
    // IS NOT PATH. GET FULL STR.
    else $class_name = $str;

    foreach ($dir_list as $dir) {

        // controllers/class_name.php
        $fn = "$dir_root/$dir/$class_name.php";
        if (file_exists($fn)) {
            require_once($fn);
            break;
        }
        $path = "$dir_root/$dir/$class_name/";

        // controllers/class_name/autoload.php
        $fn = $path . "autoload.php";
        if (file_exists($fn)) {
            require_once($fn);
            break;
        }
        // controllers/class_name/class_name.php
        $fn = $path . $class_name . ".php";
        if (file_exists($fn)) {
            require_once($fn);
            break;
        }
    }

    // MORE DEEP IN MODULES (modules/ModuleName/controllers/class_name.php, etc)
    $modules = @array_diff(@scandir(Xplend::DIR_MODULES), [".", ".."]);
    foreach ($modules as $module) {
        $module_path = Xplend::DIR_MODULES . $module;
        if (!@is_dir($module_path)) continue;
        foreach ($dir_list as $dir) {
            $full_path = "$module_path/$dir/$class_name.php";
            //if ($str == 'RegisterController') echo "$full_path\r\n";
            if (file_exists($full_path)) {
                require_once($full_path);
                break;
            }
        }
    }

    // load vendors
    $vendors_autoload = "$dir_root/modules/vendor/autoload.php";
    if (file_exists($vendors_autoload)) require_once($vendors_autoload);
}
spl_autoload_register('xplend_autoload');
