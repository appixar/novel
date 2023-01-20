<?php
// Default dynamic MySQL config
function dynamic()
{
    global $_AUTH;
    if (@$_AUTH['api']) $com_code = $_AUTH['api']['com_code'];
    else $com_code = $_AUTH['com']['com_code'];
    return array(
        'id' => 'dynamic',
        'wildcard' => $com_code
    );
}
