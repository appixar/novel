<?php
class lib extends arion
{
    public function __construct($lib)
    {
        global $_APP;
        $fn = "$lib/$lib.lib.php";
        $fp = self::DIR_LIBS . $fn;
        if (is_file($fp)) {
            debug(__CLASS__, "libs/$lib");
            require_once($fp);
        }
    }
}
class loadLibs extends arion
{
    public function __construct()
    {
        global $_APP;

        // INCLUDE CORE LIBS
        $core_libs = scandir(self::DIR_CORE_LIBS);
        for ($i = 0; $i < count($core_libs); $i++) {
            $fn = $core_libs[$i];
            $fp = self::DIR_CORE_LIBS . $fn;
            if (is_file($fp)) require_once($fp);
        }

        // INCLUDE DEFAULT PROJECT LIBS
        $libs = @$_APP["DEFAULT_LIBS"];
        if (@$libs[0]) {
            $x = 0;
            debug_init();
            for ($i = 0; $i < count($libs); $i++) {
                $fn = "{$libs[$i]}/{$libs[$i]}.lib.php";
                $fp = self::DIR_LIBS . $fn;
                if (is_file($fp)) {
                    $x++;
                    require_once($fp);
                }
            }
            debug_end("libs/... [$x files]");
        }
    }
}