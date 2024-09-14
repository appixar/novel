<?php
class up extends Mason
{
    const REPO_URL = "https://github.com/appixar/novel.git";
    //const MANIFEST_URL = "https://raw.githubusercontent.com/appixar/novel/main/manifest.json";
    const COMMITS_URL = "https://api.github.com/repos/appixar/novel/commits";

    public function __construct()
    {
        // CURRENT VERSION
        global $_MAN;
        $currVersion = @$_MAN['version'];
        $currSha = @$_MAN['commit']['sha'];
        $this->say("Novel current version: $currVersion");
        $this->say("Looking for updates...");

        $updateNow = 0;

        // 1. CHECK LAST VERSION
        /*
        $json = json_decode(file_get_contents(self::MANIFEST_URL), true);
        $lastVersion = $json['version'];
        $lastUpdatedFiles = $json['updated'];
        if ($lastVersion > $version) {
            $this->say("New version found: $lastVersion", false, "magenta");
            $updateNow++;
        }*/
        // OR... 2. CHECK LAST COMMIT DATE
        //else {

        // GET LAST COMMIT
        $options = ['http' => ['method' => 'GET', 'header' => ['User-Agent: PHP']]];
        $context = stream_context_create($options);
        $json = json_decode(file_get_contents(self::COMMITS_URL, false, $context), true);
        $lastSha = @$json[0]['sha'];
        $lastDate = @$json[0]['commit']['committer']['date'];
        $lastAuthor = @$json[0]['commit']['committer']['name'];

        // VERIFY SHA
        if ($lastSha != $currSha) {
            $this->say("New commit detected: $lastDate", false, "green");
            $this->say("Commiter: $lastAuthor", false, "green");
            $this->say("SHA: $lastSha", false, "green");
            $updateNow++;
        }

        if ($updateNow) {

            // CREATE DIR
            shell_exec("mkdir .tmp");
            shell_exec("git clone " . self::REPO_URL . " .tmp"); //2>&1

            // GET UPDATED FILES ONLY
            $newManifest = json_decode(file_get_contents('.tmp/manifest.json'), true);
            $ignoreOnUpdate = $newManifest['ignoreOnUpdate'];

            // REMOVE IGNORED FILES
            if (@$ignoreOnUpdate[0]) {
                foreach ($ignoreOnUpdate as $file) {
                    $file = $this->cleanPath($file);
                    shell_exec("rm -rf .tmp/$file");
                }
            }
            // COPY OTHER FILES
            $this->copyFiles();

            // UPDATE MANIFEST: COMMIT SHA & COMMIT DATE
            $this->say("Updating manifest ...", false, "magenta");
            $manifest = json_decode(file_get_contents("manifest.json"), true); // CHANGE PLAIN TEXT TO PREVENT MINIFY FILE
            $manifest['commit']['sha'] = $lastSha;
            $manifest['commit']['date'] = $lastDate;
            $manifest = json_encode($manifest);
            file_put_contents("manifest.json", $manifest);

            // FINISH
            $this->say("Done!", false, "green");
        } else $this->say("You are up to date.");
    }
    private function cleanPath($path)
    {
        $path = trim($path);
        $path = str_replace('..', '', $path);
        if (substr($path, 0, 1) === '/') $path = substr($path, 1);
        return $path;
    }
    private function copyFiles()
    {
        // REMOVE GIT FILES
        shell_exec("rm -rf .tmp/.git");
        shell_exec('find .tmp/ -name "*.git*" -type f -delete');
        
        // COPY REMAINING FILES
        $listFiles = getDirContents('.tmp/');
        shell_exec("cp -R .tmp/* ./");
        $this->say("Copying files...", false, "magenta");
        $listFilesNew = []; // clean git, etc
        foreach ($listFiles as $f) {
            if (!is_dir($f)) {
                $fn = explode(".tmp/", $f)[1];
                $this->say("* $fn");
                $listFilesNew[] = $f;
            }
        }
        $this->say("Total files: " . count($listFilesNew), false, "magenta");
        shell_exec("rm -rf .tmp");
    }
}
