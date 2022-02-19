<?php

class company
{
    public $error = false;
    public $return = false;
    
    // UPDATE ADDR
    public function get($com_id)
    {
        // check addr_id owner
        $res = jwquery("SELECT * FROM qmz_company WHERE com_id='$com_id' and com_status = 1");
        if (!@$res[0]) {
            $this->error = 'Company not found';
            return false;
        }
        // return
        $this->return = $res[0];
        return true;
    }
}
