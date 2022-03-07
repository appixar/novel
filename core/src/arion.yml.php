<?php
class yml_merge extends arion
{
    public function __construct($a0, $a1)
    {
        $res = array();
        echo "<pre>";
        foreach ($a0 as $k => $v) {
            // $a1 is not set
            if (!isset($a1[$k])) {
                $res[$k] = $v;
            }
            // $a1 is set, let's organize chaos
            else {
                // str value
                if (!is_array($v)) {
                    // same str value
                    if (isset($a1[$k]) and $v == $a1[$k]) {
                        $res[$k] = $v;
                    }
                    // diff str value
                    else {
                        $res[$k] = $a1[$k];
                    }
                }
                // array value
                else {
                    foreach ($v as $k_ => $v_) {
                        // same sub value
                        if (isset($a1[$k][$k_]) and $v_ == $a1[$k][$k_]) {
                            $res[$k][$k_] = $v;
                        }
                    }
                } // else (array val)
            } // else ($a1 is set)
        } // foreach 0
        //print_r($res);
        //exit;
    } // construct
}
