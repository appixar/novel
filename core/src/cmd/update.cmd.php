<?php
class update extends cmd
{
    public function __construct()
    {
        $repo = "https://github.com/appixar/arion.git";
        $repo_env = "https://raw.githubusercontent.com/appixar/arion/main/.env";

        // CURRENT VERSION
        $version = $_ENV['arion']['version'];
        $this->say("Arion current version: $version");
        $this->say("Looking for updates...");

        // LAST VERSION
        $content = file_get_contents($repo_env);
        $env = parse_ini_string($content, true);
        $lastVersion = $env['arion']['version'];

        if ($lastVersion > $version) {
            $this->say("New version found: $lastVersion");
        } else $this->say("You are up to date.");

        // CREATE DIR
        //$dir = self::DIR_MODULES . "/$module/";
        //shell_exec("mkdir .tmp");
        //shell_exec("git clone $url .tmp"); //2>&1
        //shell_exec("cp -R .tmp/* ./");
        //shell_exec("rm -rf .tmp/");
    }
}
