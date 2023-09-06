<?php
class serve extends Mason
{
    public function __construct()
    {
        $port = @self::args()[1];
        if (!$port) $port = 8000;
        $this->run($port);
    }
    private function run($port)
    {
        //$public_path = realpath(__DIR__ . "/../../public");
        $server_router = realpath(__DIR__ . "/../src/server/router.php");
        $this->say("");
        $this->say("Novel Web Server", true, "green");
        $this->say("Listening at http://localhost:$port");
        $this->say("-");
        //shell_exec("php -S localhost:$port -t $public_path");
        shell_exec("php -S localhost:$port $server_router");
    }
}
