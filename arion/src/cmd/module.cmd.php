<?php
class module extends cmd
{
    public function __construct()
    {
        cmd::autoload($this, true); // true = append second value to method. ex: $this->add(value)
    }
    public function rm()
    {
    }
    public function add($module)
    {
        // SET GIT URL
        $url = $module;
        if (!strpos("://", $url)) $url = "https://github.com/appixar/" . $url . ".git";

        // CREATE DIR
        $dir = self::DIR_MODULES . "/$module/";
        shell_exec("mkdir $dir");
        shell_exec("mkdir $dir/vendor");
        shell_exec("git clone $url $dir/vendor"); //2>&1
        shell_exec("cp -R $dir/vendor/* $dir");
        shell_exec("rm -rf $dir/vendor");
    }
}
