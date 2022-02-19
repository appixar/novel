<?php

class home
{
    public $error = false;
    public $return = false;
    //------------------------------------------------
    // GET APP DATA: OFFERS & CATEG LIST 
    //------------------------------------------------
    public function homeApp()
    {
        global $_AUTH;

        //========================
        // CATEG SIDEBAR
        //========================
        $qr = ""
            . "SELECT cat.cat_id,cat.cat_title,cat.cat_parent_id,cat_parent.cat_title cat_parent_title FROM qmz_product pro "
            . "INNER JOIN qmz_product_categ cat ON cat.cat_id = pro.cat_id " // sub categ
            . "INNER JOIN qmz_product_categ cat_parent ON cat.cat_parent_id = cat_parent.cat_id " // parent categ
            . "WHERE cat.cat_status = 1 AND pro.pro_status = 1 "
            . "GROUP BY cat.cat_id ORDER BY cat_parent_title ASC, cat.cat_title ASC";

        // QUERY
        $my = new my(dynamic());
        $res = $my->query($qr);
        if (!@$res[0]) goto productJump;

        // PROCESS
        $cat = array();
        $subcat = array();
        $subcat_cat = array();
        for ($i = 0; $i < count($res); $i++) {
            $id = $res[$i]['cat_id'];
            $title = mb_ucwords($res[$i]['cat_title']);
            $parent_id = $res[$i]['cat_parent_id'];
            $parent_title = mb_ucwords($res[$i]['cat_parent_title']);
            //
            $cat[$parent_title] = $parent_id;
            $subcat[$title] = $id;
            $subcat_cat[$id] = $parent_id;
        }
        $return = array(
            'cat' => $cat,
            'subcat' => $subcat,
            'subcat_cat' => $subcat_cat
        );
        //========================
        // OFFERS
        //========================
        $qr = ""
            . "SELECT off.off_id, cat.cat_title, oi.oi_price, oi.oi_super_offer, pro.pro_id, pro.pro_stock, pro.pro_title, pro.pro_price, pro.pro_img, pro.pro_attach, pro.cat_id, pro.pro_price_discount, pro.pro_discount_min, pro.pro_discount_max, pro.pro_notes, pro.pro_weight "
            . "FROM qmz_offer off "
            . "INNER JOIN qmz_offer_item oi ON oi.off_id = off.off_id AND oi.oi_date_delete IS NULL AND oi.oi_show_home = 1 "
            . "INNER JOIN qmz_product pro ON oi.pro_id = pro.pro_id "
            . "INNER JOIN qmz_product_categ cat ON cat.cat_id = pro.cat_id "
            . "WHERE off.off_status = 1 AND off_date_delete IS NULL ORDER BY oi.oi_super_offer DESC, oi.oi_price DESC, pro.pro_title";
        $res = $my->query($qr);
        // PROCESS
        arion::module('product');
        $res = product::fixData($res); 
        if (@$res[0]) $return['offer'] = $res;

        productJump:
        //========================
        // HOME BANNERS
        //========================
        $res = $my->query("SELECT banner_img,banner_url FROM qmz_banner WHERE banner_status = 1 AND banner_sort > 0 ORDER BY banner_sort");
        if (@$res[0]) $return['banner'] = $res;

        //========================
        // HOME NEWS
        //========================
        $res = $my->query("SELECT * FROM qmz_news WHERE news_home > 0 AND news_status = 1 ORDER BY news_home DESC");
        /*for ($i = 0; $i < count($res); $i++) {
            $res[$i]['news_title'] = utf8_encode($res[$i]['news_title']);
            $res[$i]['news_text'] = utf8_encode($res[$i]['news_text']);
        }*/
        if (@$res[0]) $return['news'] = $res;

        //========================
        // DELIVERY AREAS
        //========================
        $res = $my->query("SELECT city_id, district_id, del_tax FROM qmz_delivery WHERE del_date_delete IS NULL");
        if (@$res[0]) $return['delivery'] = $res;

        //========================
        // HOME TOP CATEG MENU
        //========================
        $res = $my->query("SELECT cat_id,menu_title,menu_sort FROM qmz_product_categ_menu ORDER BY menu_sort ASC, menu_title");
        if (@$res[0]) $return['top'] = $res;

        // RETURN
        $this->return = $return;
        return $this->return;
    }
    //------------------------------------------------
    // GET DASHBOARD DATA
    //------------------------------------------------
    public function homeDash()
    {
        
    }
    
}
