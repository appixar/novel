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
        if (!strpos("://", $url)) $url = "https://github.com/appixar/arion-" . $url . ".git";

        // CREATE DIR
        $dir = self::DIR_MODULES . "/$module/";
        if (is_dir($dir)) {
            $this->say("Module '$module' already installed", false, "yellow");
            exit;
        }
        // CHECK REPO
        $this->say("Looking for '$module' ...", false);
        if (!repo_exists($url)) {
            $this->say("Not found", false, "red");
            exit;
        }
        $this->say("Found!", false, 'green');
        
        // CLONE REPO
        shell_exec("mkdir $dir");
        shell_exec("mkdir .tmp");
        shell_exec("git clone $url .tmp"); //2>&1
        shell_exec("cp -R .tmp/* $dir");
        shell_exec("rm -rf .tmp");
        $this->say("Done!", false, "green");
    }
}
