<?php
class Routes extends Arion
{
    public $error = false;
    public $error_code = false;
    public $res = false;
    // error return
    public function error($error = '', $error_code = 406)
    {
        $this->error = $error;
        $this->error_code = $error_code;
        return false;
    }
    // success return
    public function res($data = [])
    {
        $this->res = $data;
        return $this->res;
    }
    // controller return
    public function return($objectFromController) {
        if ($objectFromController->error) return $this->error($objectFromController->error);
        elseif ($objectFromController->res) return $this->res($objectFromController->res);
        else return false;
    }
}
class Controllers extends Arion
{
    public $error = false;
    public $error_code = false;
    public $res = false;
    //
    public function error($error = '')
    {
        $this->error = $error;
        return false;
    }
    public function res($data = [])
    {
        $this->res = $data;
        return $this->res;
    }
}
class Services extends Arion
{
    public $error = false;
    public $error_code = false;
    public $res = false;
    //
    public function error($error = '')
    {
        $this->error = $error;
        return false;
    }
    public function res($data = [])
    {
        $this->res = $data;
        return $this->res;
    }
}