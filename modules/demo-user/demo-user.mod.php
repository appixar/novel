<?php

class user
{
    public $error = false;
    public $return = false;
    //------------------------------------------------
    // POST
    //------------------------------------------------
    public function post($body)
    {
        die('aaa');
        // dyn db
        $my = new my();

        // util
        $ip = getIp();
        $now = date('Y-m-d H:i:s');

        // bday
        $bday = explode("/", $body['user_bday']);
        $bday = "{$bday[2]}-{$bday[1]}-{$bday[0]}";
        $body['user_bday'] = $bday;

        if (!@$body['user_name'] or !@$body['user_lname'] or !@$body['user_login'] or !@$body['user_cpf']) {
            $this->error = 'Desculpe, dados obrigatórios estão faltando.';
            return false;
        }

        // data
        $user_key = geraSenha(32, true, true, true);
        $data = array(
            'user_fname' => trim(mb_ucwords($body['user_fname'])),
            'user_lname' => trim(mb_ucwords($body['user_lname'])),
            'user_login' => $body['user_login'],
            'user_cpf' => clean($body['user_cpf']),
            'user_pass' => password_hash($body['user_pass'], PASSWORD_DEFAULT), // $verify = password_verify($plaintext_password, $hash);
            'user_gender' => @$body['user_genre'],
            'user_bday' => @$body['user_bday'],
            'user_phone' => clean(clean_spaces(@$body['user_phone'])),
            'user_status' => 1,
            // api key
            'user_key' => $user_key,
            'user_key_date_insert' => $now,
            'user_date_insert' => $now
        );

        // verify if user exists
        $check = $my->query('SELECT user_id FROM qmz_user WHERE user_cpf=:user_cpf OR user_login=:user_login OR user_phone=:user_phone', $data);
        if (@$check[0]) {
            $this->error = 'Desculpe, ocorreu uma falha na verificação dos dados únicos.';
            return false;
        }
        // save data
        $id = $my->insert('qmz_user', $data);
        if (!is_numeric($id)) {
            $this->error = 'Desculpe, ocorreu um erro ao inserir os dados.';
            return false;
        }
        $this->return = array('user_key' => $user_key, 'user_id' => $id);
        return $this->return;
    }
    //------------------------------------------------
    // GET user/mailcheck
    //------------------------------------------------
    public function mailcheckGet($body)
    {
        // db
        $my = new my();
        // query
        $res = $my->query('SELECT user_id FROM bp_user WHERE user_email = :user_email', $body)[0];
        if (@$res) $this->return = $res;
        else $this->return = true;
        // return
        return $this->return;
    }
    //------------------------------------------------
    // GET
    //------------------------------------------------
    public function get($body)
    {
        die('here');
        global $_AUTH;
        if (!@$_AUTH['adm'] or !@$body['user_id']) return false;

        $my = new my(dynamic());

        $res = $my->query("SELECT * FROM qmz_user WHERE user_id = :user_id", $body);
        if (!@$res[0]) {
            $this->error = 'E-mail não cadastrado.';
            return false;
        }
        unset($res[0]['user_pass']);
        $this->return = $res[0];
        return $this->return;
    }
}
