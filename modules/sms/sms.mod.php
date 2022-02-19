<?php

class sms
{
    public $error = false;
    //------------------------------------------------
    // CHECK SMS CODE
    // -> $body [*user_phone]
    // -> $body [*sms_code]
    //------------------------------------------------
    public function check($body)
    {
        global $_AUTH;

        // Check code
        $code = @$body['sms_code'];
        if (!is_numeric($code) or strlen($code) != 4) http::die(400, "Formato inválido: $code");

        // Check phone
        $phone = clean(clean_spaces(@$body['user_phone']));
        if (!is_numeric($phone) or strlen($phone) != 11) http::die(400, "Telefone inválido: $phone");

        // check last req from ip
        $my = new my(dynamic());
        $res = $my->query("SELECT sms_id FROM qmz_user_sms WHERE sms_target = '$phone' AND sms_code = '$code' AND sms_status = 1 LIMIT 1");
        if (!@$res[0]) {
            $this->error = 'O código não existe ou expirou.';
            return false;
        }

        // code OK
        $my->update("qmz_user_sms", array("sms_status" => 2), array("sms_id" => $res[0]['sms_id']));

        // user exists? auth him
        $res = $my->query("SELECT user_key,user_fname FROM qmz_user WHERE user_phone = '$phone'");
        if (@$res[0]) {
            $this->return = $res[0];
            return $this->return;
        } else {
            $this->return = true;
            return $this->return;
        }
    }
    //------------------------------------------------
    // POST (SEND SMS)
    // -> $body [*user_phone]
    //------------------------------------------------
    public function post($body)
    {
        global $_AUTH;
        if (!@$_AUTH['conf']) http::die(406, "Company conf not found");

        // DATA
        $phone = clean(clean_spaces(@$body['user_phone']));
        if (!is_numeric($phone) or strlen($phone) != 11) http::die(400, "Telefone inválido: $phone");

        // UTIL
        $ip = getIp();
        $now = date('Y-m-d H:i:s');

        // CHECK LAST REQ. FROM IP
        $my = new my(dynamic());
        $res = $my->query("SELECT sms_date FROM qmz_user_sms WHERE sms_ip = '$ip' ORDER BY sms_id DESC LIMIT 1");
        if ($this->diff(@$res[0]['sms_date'], $now) <= 1) {
            //$this->error = 'Por favor, aguarde.';
            //return false;
        }

        // CHECK LAST REQ. TO TARGET
        $res = $my->query("SELECT sms_date FROM qmz_user_sms WHERE sms_target = '$phone' ORDER BY sms_id DESC LIMIT 1");
        if ($this->diff(@$res[0]['sms_date'], $now) <= 1) {
            $this->error = 'Por favor, aguarde. (2)';
            return false;
        }

        // RAND CODE
        $r = rand(1000, 9999);

        // DATA
        $ins = array(
            'sms_code' => $r,
            'sms_target' => $phone,
            'sms_date' => $now,
            'sms_status' => 1,
            'sms_ip' => $ip
        );

        // API
        $title = $_AUTH['conf']['conf_title'];
        $msg = "[$title] Use este codigo no app: $r";
        $msg = urlencode($msg);
        $send = file_get_contents("https://sms.comtele.com.br/api/v2/customsend/620a9e9f96bc?origem=58822&destino=55$phone&m=$msg");
        $send = json_decode($send, true);
        if (@!$send['Success']) {
            //$ins['sms_status'] = 0;
            //$this->error = 'Desculpe, ocorreu um erro ao enviar o SMS.';
        }

        // QUERY
        $id = $my->insert('qmz_user_sms', $ins);
        if (!is_numeric($id)) {
            $this->error = 'Desculpe, ocorreu um erro ao criar o código.';
            return false;
        }

        // RETURN
        return true;
        //if ($this->error) return false;
        //else return true;
    }
    // diff in minutes
    private function diff($dt0, $dt1)
    {
        $dt1 = strtotime($dt1);
        $dt0 = strtotime($dt0);
        return round(abs($dt1 - $dt0) / 60, 2);
    }
}
