<?php
class Wizard extends arion
{
    public function __construct()
    {
        if (PHP_SAPI !== 'cli' and isset($_SERVER['HTTP_USER_AGENT'])) {
            //arion::err("CMD ONLY");
        }
    }
    // Esta função é linda.
    // BLA BLA LA
    public function prompt($text, $def)
    {
        echo "• $text [$def] ";
        $res = trim(fgets(fopen("php://stdin", "r")));
        //----------------------------
        // LIMITED TO: Y/N
        //----------------------------
        if (strtolower($def) == "y/n") {
            if (ctype_upper(explode("/", $def)[0])) $def = 1; // Y
            else $def = 0; // N
            // diff res
            if (strtolower($res) == "y") $res = 1;
            elseif (strtolower($res) == "n") $res = 0;
            else $res = $def;
        }
        //----------------------------
        // RETURN
        //----------------------------
        if ($res == "") {
            $res = $def;
        }
        return $res;
    }
}

class RunSQL extends arion
{
    public $keys = array();
    public $values = array();
    public $db = 0;

    public function __construct()
    {
    }
    //------------------------------------------
    // set db
    //------------------------------------------
    public function sql($db)
    {
        $this->db = $db;
    }
    //------------------------------------------
    // construct arrays to replace sql strings
    //------------------------------------------
    public function dic($dictionary_array)
    {
        $a = $dictionary_array;
        foreach ($a as $k => $v) {
            $this->keys[] = "<$k>";
            $this->values[] = $v;
        }
    }
    //------------------------------------------
    // read sql file
    //------------------------------------------
    public function run($file)
    {
        global $_APP;
        if (!$_APP) {
            $this->err("RunSQL needs yml file");
        }
        $content = file_get_contents($file);
        $content = str_replace($this->keys, $this->values, $content);
        $arr = explode(";", $content);
        for ($i = 0; $i < count($arr); $i++) {
            $lines = explode("\n", $arr[$i]);
            $comment = "";
            $cmd = "";
            for ($x = 0; $x <= count($lines); $x++) {
                if (isset($lines[$x])) {
                    if (substr($lines[$x], 0, 2) == "--") {
                        $comment .= $lines[$x] . "\n";
                    } else {
                        $cmd .= $lines[$x];
                    }
                }
            }
            //echo "<strong>$comment</strong>$cmd\n";
            if ($cmd) {
                echo "$cmd\n\n";
                if (jwquery($cmd, $this->db)) {
                    echo "••• DONE.\n\n";
                } else {
                    echo "### FAIL!\n\n";
                }
            }
        }
    }
}
