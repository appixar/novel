<?php
class database extends mason
{
    public function __construct()
    {
        mason::autoload($this);
    }
    public function up()
    {
        $argx = mason::argx();
        //
        $schema = new schema();
        $schema->up($argx);
    }
    public function dump()
    {
        global $_APP;
        $host = $_APP['MYSQL'][0]['HOST'];
        $name = $_APP['MYSQL'][0]['NAME'];
        $user = $_APP['MYSQL'][0]['USER'];
        $pass = $_APP['MYSQL'][0]['PASS'];
        //
        $fn = time() . '-' . $name . '.sql';
        $fp = self::DIR_DB . $fn;
        //exec("mysqldump --user=$user --password=$pass --host=$host --no-data $name > $fp");
        $this->say("* Generated: app/database/dump/<green>$fn</end>", true);
    }
}
