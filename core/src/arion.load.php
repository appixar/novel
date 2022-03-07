<?php
class loadmodule extends arion
{
    public function __construct($lib)
    {
        global $_APP;
        $fn = "$lib/$lib.mod.php";
        $fp = self::DIR_MODULES . $fn;
        if (is_file($fp)) {
            debug(__CLASS__, "modules/$lib");
            require_once($fp);
        }
    }
}
class loadModules extends arion
{
    public function __construct()
    {
        global $_APP;
        
        // INCLUDE DEFAULT PROJECT MOD
        $libs = @$_APP["DEFAULT_MODULES"];
        if (@$libs[0]) {
            $x = 0;
            debug_init();
            for ($i = 0; $i < count($libs); $i++) {
                $fn = "{$libs[$i]}/{$libs[$i]}.mod.php";
                $fp = self::DIR_MODULES . $fn;
                if (is_file($fp)) {
                    $x++;
                    require_once($fp);
                }
            }
            debug_end("modules/... [$x files]");
        }
    }
}