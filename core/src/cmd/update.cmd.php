<?php
class update extends cmd
{
    public function __construct()
    {
        //cmd::autoload($this);

        $this->say("current version={$_ENV['arion']['version']}", true);
        
        // SET GIT URL
        $url = "https://github.com/appixar/arion.git";

        // CREATE DIR
        //$dir = self::DIR_MODULES . "/$module/";
        //shell_exec("mkdir .tmp");
        //shell_exec("git clone $url .tmp"); //2>&1
        //shell_exec("cp -R .tmp/* ./");
        //shell_exec("rm -rf .tmp/");
    }
}
