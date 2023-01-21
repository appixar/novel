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
    public function up($module)
    {
        // CREATE DIR
        $dir = self::DIR_MODULES . "/$module/";
        if (!is_dir($dir)) {
            $this->say("Module '$module' is not installed", false, "yellow");
            exit;
        }
        // CLONE
        $this->cloneRepo($module, true);
    }
    public function add($module)
    {
        // SET GIT URL
        $url = "https://github.com/appixar/arion-" . $module . ".git";

        // CREATE DIR
        $dir = self::DIR_MODULES . "/$module/";
        if (is_dir($dir)) {
            $this->say("Module '$module' already installed", false, "yellow");
            exit;
        }
        // CLONE
        $this->cloneRepo($module);
    }
    private function cloneRepo($module, $update = false)
    {
        // VAR'S
        $repo_url = "https://github.com/appixar/arion-" . $module . ".git";
        $dir = self::DIR_MODULES . "/$module/";

        // CHECK REPO
        $this->say("Looking for '$module' ...", false);
        if (!repo_exists($repo_url)) {
            $this->say("Not found", false, "red");
            exit;
        }
        $this->say("Found!", false);

        //------------------------------------------------
        // UPDATE MODULE.
        // (CHECK VERSION & COPY ONLY SPECIF FILES)
        //------------------------------------------------
        if ($update) {

            // CURRENT MODULE VERSION
            $currManifest = json_decode(file_get_contents("$dir/manifest.json"), true);
            $currSha = $currManifest['commit']['sha'];
            $currDate = $currManifest['commit']['date'];

            // GET LAST COMMIT
            $this->say("Checking last commit ...", false);
            $lastCommit = $this->getLastCommit($module);
            $lastSha = @$lastCommit['sha'];
            $lastDate = @$lastCommit['commit']['committer']['date'];
            $lastAuthor = @$lastCommit['commit']['committer']['name'];

            // COMPARE SHA
            if ($lastSha != $currSha) {
                $this->say("New commit detected: $lastDate", false, "green");
                $this->say("Commiter: $lastAuthor", false, "green");
                $this->say("SHA: $lastSha", false, "green");

                // UPDATE NOW!
                // CLONE REPO
                shell_exec("rm -rf .tmp");
                shell_exec("mkdir .tmp");
                shell_exec("git clone $repo_url .tmp"); //2>&1

                // GET UPDATED FILES ONLY
                if (!file_exists('.tmp/manifest.json')) {
                    $this->say("manifest.json not found.", false, "red");
                    shell_exec("rm -rf .tmp");
                    exit;
                }
                $newManifest = json_decode(file_get_contents('.tmp/manifest.json'), true);
                $ignoreOnUpdate = @$newManifest['ignoreOnUpdate'];

                // MOVE README & MANIFEST FROM ROOT -> TO MODULE FOLDER
                // ... TO PRESERVE MAIN ARION MANIFEST
                shell_exec("mv .tmp/manifest.json $dir");
                shell_exec("mv .tmp/README.md $dir");

                // REMOVE IGNORED FILES
                foreach ($ignoreOnUpdate as $file) {
                    $file = $this->cleanPath($file);
                    shell_exec("rm -rf .tmp/$file");
                }
                shell_exec("rm -rf .tmp/.git");
                shell_exec('find . -name "*.git*" -type f -delete');

                // COPY REMAINING FILES
                $this->copyFiles();

                // UPDATE MANIFEST: COMMIT SHA & COMMIT DATE
                $this->say("Updating manifest ...", false, "magenta");
                $manifest = json_decode(file_get_contents("$dir/manifest.json"), true); // CHANGE PLAIN TEXT TO PREVENT MINIFY FILE
                $manifest['commit']['sha'] = $lastSha;
                $manifest['commit']['date'] = $lastDate;
                $manifest = json_encode($manifest);
                file_put_contents("$dir/manifest.json", $manifest);

                // FINISH
                $this->say("Done!", false, "green");
            } else $this->say("Module is up to date.");

            // DIE
            exit;
        }
        //------------------------------------------------
        // INSTALL MODULE.
        // (DON'T CHECK VERSION & COPY ALL FILES)
        //------------------------------------------------
        // CLONE REPO
        shell_exec("rm -rf .tmp");
        shell_exec("mkdir $dir");
        shell_exec("mkdir .tmp");
        shell_exec("git clone $repo_url .tmp"); //2>&1
        // REMOVE GIT FILES
        shell_exec("rm -rf .tmp/.git");
        shell_exec('find . -name "*.git*" -type f -delete');
        // MOVE README & MANIFEST FROM ROOT -> TO MODULE FOLDER
        // ... TO PRESERVE MAIN ARION MANIFEST
        shell_exec("mv .tmp/manifest.json $dir");
        shell_exec("mv .tmp/README.md $dir");
        // COPY OTHER FILES
        $this->copyFiles();
        $this->say("Done!", false, "green");
    }
    private function getLastCommit($module)
    {
        $commit_url = "https://api.github.com/repos/appixar/arion-$module/commits";
        $options = ['http' => ['method' => 'GET', 'header' => ['User-Agent: PHP']]];
        $context = stream_context_create($options);
        $json = json_decode(file_get_contents($commit_url, false, $context), true);
        return $json[0];
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
