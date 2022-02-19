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
        shell_exec("mkdir tmp-git");
        shell_exec("git clone $url tmp-git"); //2>&1
        shell_exec("cp -R tmp-git/* ./");
        shell_exec("rm -rf tmp-git/");
    }
}
