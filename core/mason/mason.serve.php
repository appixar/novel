<?php
class serve extends Mason
{
    public function __construct()
    {
        global $_APP;
        $port = @self::args()[1];
        if (!$port) $port = 8000;

        // check base url and find localhost port
        if (@$_APP['URL']) {
            $localhost = explode("http://localhost:", $_APP['URL']);
            if (@$localhost[1]) {
                $localhost_port = explode("/", $localhost[1])[0];
                if (is_numeric($localhost_port)) $port = $localhost_port;
            }
        }
        $this->run($port);
    }
    private function run($port)
    {
        //$public_path = realpath(__DIR__ . "/../../public");
        $server_router = realpath(__DIR__ . "/../src/server/router.php");
        $this->say("");
        $this->say("Xplend Web Server", true, "green");
        $this->say("Listening at http://localhost:$port");
        $this->say("-");
        //shell_exec("php -S localhost:$port -t $public_path");
        shell_exec("php -S localhost:$port $server_router");
    }
}
