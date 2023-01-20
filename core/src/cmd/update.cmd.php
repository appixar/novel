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
        $json = json_decode(file_get_contents($manifest), true);
        $lastVersion = $json['version'];
        $lastUpdatedFiles = $json['updatedFiles'];

        if ($lastVersion > $version) {
            $this->say("New version found: $lastVersion", false, "green");
            // CREATE DIR
            shell_exec("mkdir .tmp");
            shell_exec("git clone $repo .tmp"); //2>&1
            foreach ($lastUpdatedFiles as $file) {
                $this->say("Copying: $file ...", false, "green");
                if ($file === '.') shell_exec("cp .tmp/* ./");
                else {
                    //if (!is_dir("./$file")) mkdir("./$file");
                    shell_exec("cp -R .tmp/$file ./");
                }
            }
            shell_exec("rm -rf .tmp/");
            $this->say("Done!");
        } else $this->say("You are up to date.");
    }
}
