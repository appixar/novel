<?php
class update extends cmd
{
    const REPO_URL = "https://github.com/appixar/arion.git";
    const MANIFEST_URL = "https://raw.githubusercontent.com/appixar/arion/main/manifest.json";
    const COMMITS_URL = "https://api.github.com/repos/appixar/arion/commits";

    public function __construct()
    {
        // CURRENT VERSION
        global $_MAN;
        $version = $_MAN['version'];
        $this->say("Arion current version: $version");
        $this->say("Looking for updates...");

        // LAST VERSION
        $json = json_decode(file_get_contents(self::MANIFEST_URL), true);
        $lastVersion = $json['version'];
        $lastUpdatedFiles = $json['updatedFiles'];

        if ($lastVersion > $version) {
            $this->say("New version found: $lastVersion", false, "green");
            // CREATE DIR
            shell_exec("mkdir .tmp");
            shell_exec("git clone " . self::REPO_URL . " .tmp"); //2>&1
            foreach ($lastUpdatedFiles as $file) {
                $this->say("Copying: $file ...", false, "green");
                if ($file === '.') exec("cp .tmp/* ./");
                else shell_exec("cp -R .tmp/$file ./");
            }
            shell_exec("rm -rf .tmp/");
            $this->say("Done!");
        } else $this->say("You are up to date.");
    }
}
