<?php

class order
{
    public $error = false;
    public $return = false;
    //------------------------------------------------
    // GET
    // -> $body [*order_code]
    //------------------------------------------------
    public function get($body)
    {
        global $_AUTH;
        if (!@$body['order_code']) return false;

        $fields = 'order_id, order_addr, pay_title, order_code, order_date, order_status, order_delivery, order_price_total, user_fname, user_lname, user_phone, user_cpf';
        $my = new my(dynamic());
        $res = $my->query("SELECT $fields FROM qmz_order o INNER JOIN qmz_user u ON u.user_id = o.user_id LEFT JOIN mirror_payment pay ON pay.pay_id = o.pay_id WHERE o.order_code = :order_code", $body);

        // check data
        if (!@$res[0]) return true;

        // process data
        $order_id = $res[0]['order_id'];
        $order_status = $res[0]['order_status'];
        $body['order_id'] = $order_id;

        // status
        $status_title = '';
        $status_class = '';
        $status_text = '';

        // order status
        switch ($order_status) {
            case 1:
                $status_title = 'em análise';
                $status_class = 'info';
                $status_text = 'Seu pedido foi confirmado e em breve será coletado para entrega. Por favor, aguarde.';
                break;
            case 2:
                $status_title = 'aprovado';
                $status_class = 'success';
                break;
            case 3:
                $status_title = 'saiu para entrega';
                $status_class = 'success';
                break;
            case 4:
                $status_title = 'disponível para retirada';
                $status_class = 'success';
                break;
            case 5:
                $status_title = 'concluído';
                $status_class = 'secondary';
                break;
            case -1:
                $status_title = 'cancelado pela loja';
                $status_class = 'danger';
                break;
            case -2:
                $status_title = 'cancelado pelo cliente';
                $status_class = 'warning';
                break;
        }
        $res[0]['status_title'] = $status_title;
        $res[0]['status_class'] = $status_class;
        $res[0]['status_text'] = $status_text;

        // Address
        $res[0]['order_addr'] = json_decode($res[0]['order_addr'], true);

        // Order Item
        $fields = "item_price, item_count, item_user_notes, pro_barcode, pro_src_id, pro_title, pro_weight, c.cat_title, pc.cat_title AS pcat_title ";
        $query = "SELECT $fields FROM qmz_order_item oi "
            . "INNER JOIN qmz_product p ON p.pro_id = oi.pro_id "
            . "INNER JOIN qmz_product_categ c ON p.cat_id = c.cat_id "
            . "INNER JOIN qmz_product_categ pc ON c.cat_parent_id = pc.cat_id "
            . "WHERE oi.order_id = :order_id";
        $item = $my->query($query, $body);
        $res[0]['item'] = $item;

        // RETURN
        $this->return = $res;
        return $this->return;
    }
    //------------------------------------------------
    // DELETE
    // -> $body [*order_id]
    //------------------------------------------------
    public function delete($body)
    {
        global $_AUTH;
        if (!$_AUTH) return false;
        if (!is_numeric($body['order_id'])) return false;

        $order_id = $body['order_id'];

        // check permiss
        $my = new my(dynamic());
        $res = $my->query("SELECT order_id FROM qmz_order WHERE order_id = '$order_id' AND user_id = '{$_AUTH['user']['user_id']}'");
        if (!@$res[0]) {
            $this->error = 'User ID != Order ID';
            return false;
        }
        // query
        $now = date('Y-m-d H:i:s');
        $upd = array(
            'order_date_delete' => $now,
            'order_date_update' => $now,
            'order_status' => -2
        );
        $my->update('qmz_order', $upd, array('order_id' => $order_id));
        return true;
    }
    //------------------------------------------------
    // GET BY USER ID
    // -> $body [*user_id]
    //------------------------------------------------
    public function getByUser($body)
    {
        global $_AUTH;
        if (!$_AUTH) return false;

        $my = new my(dynamic());
        if (@$_AUTH['user']) $res = $my->query("SELECT order_id,order_code,order_date,order_price_total,order_com_notes,order_status,order_delivery FROM qmz_order WHERE user_id = '{$_AUTH['user']['user_id']}' ORDER BY order_id DESC LIMIT 50");
        elseif (@$_AUTH['adm']) $res = $my->query("SELECT * FROM qmz_order WHERE user_id = '{$body['user_id']}' ORDER BY order_id DESC LIMIT 50");
        // check data
        if (!@$res[0]) return true;

        // process data
        $return = array(); // bugfix, separate 'active' and 'old history', easyest for frontend
        for ($i = 0; $i < @count($res); $i++) {

            $order_id = $res[$i]['order_id'];
            $order_status = $res[$i]['order_status'];

            // date BR
            $date = $res[$i]['order_date'];
            $date = dateBR($res[$i]['order_date']);
            $date = explode(" ", $date)[0];
            $res[$i]['order_date'] = $date;

            // status
            if ($order_status > 0 and $order_status < 7) $key = 'active';
            else $key = 'history';
            $status_title = '';
            $status_class = '';
            $status_text = '';

            // get item images
            $img = $my->query("SELECT p.pro_img FROM qmz_order_item i INNER JOIN qmz_product p ON i.pro_id = p.pro_id WHERE i.order_id = '$order_id'");
            $res[$i]['img'] = $img;

            // order status
            switch ($order_status) {
                case 1:
                    $status_title = 'em análise';
                    $status_class = 'info';
                    $status_text = 'Seu pedido foi recebido e está em análise. Por favor, aguarde.';
                    break;
                case 2:
                    $status_title = 'aprovado';
                    $status_class = 'success';
                    $status_text = 'Seu pedido foi aprovado e já está sendo coletado para entrega.';
                    break;
                case 3:
                    $status_title = 'saiu para entrega';
                    $status_class = 'success';
                    $status_text = 'Seu pedido está a caminho! Por favor, aguarde no endereço selecionado.';
                    break;
                case 4:
                    $status_title = 'disponível para retirada';
                    $status_class = 'success';
                    $status_text = 'Seu pedido já pode ser retirado na loja. Consulte os horários de funcionamento.';
                    break;
                case 5:
                    $status_title = 'concluído';
                    $status_class = 'secondary';
                    break;
                case -1:
                    $status_title = 'cancelado pela loja';
                    $status_class = 'danger';
                    break;
                case -2:
                    $status_title = 'cancelado pelo cliente';
                    $status_class = 'warning';
                    break;
            }
            $res[$i]['status_title'] = $status_title;
            $res[$i]['status_class'] = $status_class;
            $res[$i]['status_text'] = $status_text;

            // count 
            $res[$i]['count'] = count($img);
            $return[$key][] = $res[$i];
        }
        // RETURN
        $this->return = $return;
        return $this->return;
    }
    //------------------------------------------------
    // GET ALL
    //------------------------------------------------
    public function getAll()
    {
        global $_AUTH;
        if (!@$_AUTH['adm'] or !@$_AUTH['com']) return false;

        $my = new my(dynamic());
        $fields = 'order_id, order_addr, order_code, order_date, order_status, order_delivery, order_price_total, user_fname, user_lname';
        $res = $my->query("SELECT $fields FROM qmz_order o INNER JOIN qmz_user u ON u.user_id = o.user_id ORDER BY order_id DESC LIMIT 1000");

        // check data
        if (!@$res[0]) return true;

        // process data
        for ($i = 0; $i < count($res); $i++) {

            $order_id = $res[$i]['order_id'];
            $order_status = $res[$i]['order_status'];

            // date BR
            $date = $res[$i]['order_date'];
            $res[$i]['order_full_date'] = $date;
            $date = dateBR($res[$i]['order_date'], 1);
            $res[$i]['order_date'] = $date;

            // status
            $status_title = '';
            $status_class = '';
            $status_text = '';

            // order status
            switch ($order_status) {
                case 1:
                    $status_title = 'em andamento';
                    $status_class = 'info';
                    $status_text = 'Seu pedido foi confirmado e em breve será coletado para entrega. Por favor, aguarde.';
                    break;
                case 2:
                    $status_title = 'em coleta';
                    $status_class = 'success';
                    break;
                case 3:
                    $status_title = 'saiu para entrega';
                    $status_class = 'success';
                    break;
                case -2:
                    $status_title = 'cancelado pelo cliente';
                    $status_class = 'warning';
                    break;
                case -1:
                    $status_title = 'cancelado pela loja';
                    $status_class = 'danger';
                    break;
                case 7:
                    $status_title = 'entregue';
                    $status_class = 'secondary';
                    break;
            }
            $res[$i]['status_title'] = $status_title;
            $res[$i]['status_class'] = $status_class;
            $res[$i]['status_text'] = $status_text;

            // Address
            $addr = json_decode($res[$i]['order_addr'], true);
            $res[$i]['city'] = @$addr['city'];
            $res[$i]['district'] = @$addr['district'];
            unset($res[$i]['order_addr']);
        }
        // RETURN
        $this->return = $res;
        return $this->return;
    }
    //------------------------------------------------
    // POST
    // -> $body [*item]
    // -> $body [*addr]
    //------------------------------------------------
    public function post($body)
    {
        global $_AUTH;
        if (!$_AUTH) return false;

        $my = new my(dynamic());

        // Check items
        $order_price = 0;
        $item = $body['item'];
        if (@!$item[0]) {
            $this->error = "Produtos não encontrados.";
            return false;
        }
        for ($i = 0; $i < count($item); $i++) {
            //
            $pro_id = $item[$i]['pro_id'];
            $pro_title = $item[$i]['pro_title'];
            $pro_weight = @$item[$i]['pro_weight'];
            $pro_price = $item[$i]['pro_price'];
            $count = $item[$i]['count'];
            //
            $order_price += $pro_price;
            // query
            $res = $my->query("SELECT pro_stock,pro_title FROM qmz_product WHERE pro_id = :pro_id and pro_status = 1", $item[$i]);
            // check stock
            if (!@$res[0]) {
                $this->error = "Produto indisponível: $pro_title";
                return false;
            }
            if ($res[0]['pro_stock'] === 0) {
                $this->error = "Produto esgotado: $pro_title";
                return false;
            }
            if ($res[0]['pro_stock'] < $count) {
                $this->error = "<strong>$pro_title</strong> possui apenas {$res[0]['pro_stock']}$pro_weight disponíveis em estoque.";
                return false;
            }
        } // for
        // tax
        if ($body['delivery'] == 0) {
            $body['addr']['tax'] = 0;
            $body['addr']['addr_id'] = 'NULL';
        }
        $order_price_total = $order_price + $body['addr']['tax'];
        //
        // insert order
        $now = date('Y-m-d H:i:s');
        $order_code = date("ymd") . up(geraSenha(4, true, false));
        $ins = array(
            'order_code' => $order_code,
            'user_id' => $_AUTH['user']['user_id'],
            'addr_id' => @$body['addr']['addr_id'],
            'order_addr' => json_encode(@$body['addr']),
            'order_date' => $now,
            'order_price' => $order_price,
            'order_price_total' => $order_price_total, // with tax
            'order_tax' => $body['addr']['tax'],
            'order_user_notes' => @$body['notes'], // with tax
            'pay_id' => $body['pay']['pay_id'],
            'order_delivery' => $body['delivery'], // 1=entregar, 0=retirar
            'order_status' => 1, // em analise
            'order_json' => json_encode($body),
            'order_change' => @$body['pay_change'],
            'cart_id' => @$body['cart_id']
        );
        $order_id = $my->insert('qmz_order', $ins);
        // insert order item
        for ($i = 0; $i < count($item); $i++) {
            $ins = array(
                'order_id' => $order_id,
                'pro_id' => $item[$i]['pro_id'],
                'item_price' => $item[$i]['pro_price'],
                'item_count' => $item[$i]['count'],
                'item_user_notes' => @$item[$i]['item_user_notes'],
            );
            $item_id = $my->insert('qmz_order_item', $ins);
        }
        // decrease stock -1

        // return
        $this->return = $order_id;
        return true;
    }
    //------------------------------------------------
    // PUT
    // -> $body [*order_id]
    //------------------------------------------------
    public function put($body)
    {
        global $_AUTH;
        if (!@$_AUTH['adm']) return false;

        $my = new my(dynamic());
        $my->update('qmz_order', $body, array('order_id' => $body['order_id']));

        // return
        $this->return = true;
        return true;
    }
    //------------------------------------------------
    // GET PAYMENT OPTIONS
    //------------------------------------------------
    public function pay()
    {
        global $_AUTH, $_APP;
        if (!@$_AUTH['conf']) return false;

        $pay = json_decode(json_decode($_AUTH['conf']['conf_json_pay']));

        $return = array();

        $my = new my(dynamic());
        for ($i = 0; $i < count($pay); $i++) {
            $res = $my->query("SELECT * FROM mirror_payment WHERE pay_id = '{$pay[$i]}'");
            //$res[0]['pay_title'] = utf8_encode($res[0]['pay_title']); // utf8 fix
            $res[0]['pay_img'] = $_APP['URL'] . '/upload/pay/' . $res[0]['pay_img'];
            $return[] = $res[0];
        }

        // RETURN
        $this->return = $return;
        return $this->return;
    }
}
