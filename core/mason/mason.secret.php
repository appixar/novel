<?php
class secret extends Mason
{
    public function __construct()
    {
        Mason::autoload($this, true); // true = append second value to method. ex: $this->add(value)
    }
    public function new()
    {
        global $_APP;

        // CHECK CURRENT SECRET
        $replaceSearch = "";
        if (array_key_exists('SECRET', $_APP)) {
            if ($_APP['SECRET'] === false) $replaceSearch = "SECRET: false";
            elseif (@$_APP['SECRET'] != "") $replaceSearch = "SECRET: {$_APP['SECRET']}";
            else $replaceSearch = "SECRET:";
        } else {
            $this->say("Key 'SECRET' not found in app.yml", false, "red");
            exit;
        }

        // CREATE NEW SECRET
        $newSecret = geraSenha(32);
        $replaceTo = "SECRET: $newSecret";

        // REPLACE PLAIN TEXT TO PREVENT MINIFY FILE
        if ($replaceSearch) {
            $data = file_get_contents("app/config/app.yml");
            $data = str_replace($replaceSearch, $replaceTo, $data);
            file_put_contents("app/config/app.yml", $data);
        }
        $this->say('Updating app.yml ...');
        $this->say("SECRET: $newSecret");
        $this->say('Done!');
    }
}
