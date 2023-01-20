<?php
class update extends cmd
{
    public function __construct()
    {
        // CURRENT VERSION
        global $_MAN;
        $version = $_MAN['version'];
        $this->say("Arion current version: $version");
        $this->say("Looking for updates...");

        // LAST VERSION
        $repo = "https://github.com/appixar/arion.git";
        $manifest = "https://raw.githubusercontent.com/appixar/arion/main/manifest.json";
        $content = file_get_contents($manifest);
        $json = json_decode($content, true);
        $lastVersion = $json['version'];

        if ($lastVersion > $version) {
            $this->say("New version found: $lastVersion", false, "green");
            // CREATE DIR
            /*shell_exec("mkdir .tmp");
            shell_exec("git clone $repo .tmp"); //2>&1
            shell_exec("cp -R .tmp/* ./");
            shell_exec("rm -rf .tmp/");*/
        } else $this->say("You are up to date.");
    }
}
