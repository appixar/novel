<?php

class banner
{
    public $error = false;
    public $return = false;
    //------------------------------------------------
    // POST
    //------------------------------------------------
    public function post($body)
    {
        global $_AUTH;
        if (!@$_AUTH['adm']) return false;

        // NEXT ITEM SORT
        $my = new my(dynamic());
        $res = $my->query('SELECT banner_sort FROM qmz_banner WHERE banner_status = 1 ORDER BY banner_sort DESC LIMIT 1');
        $sort = $res[0]['banner_sort'] + 1; // next sort

        // DATA
        $ins = array(
            'banner_img' => $body['banner_img'],
            'banner_url' => $body['banner_url'],
            'banner_status' => 1,
            'banner_date_insert' => date("Y-m-d H:i:s"),
            'banner_sort' => $sort
        );

        // QUERY
        $banner_id = $my->insert('qmz_banner', $ins);

        // RETURN
        if (is_numeric($banner_id)) return true;
        else return false;
    }
    //------------------------------------------------
    // PUT
    //------------------------------------------------
    public function put($body)
    {
        global $_AUTH;
        if (!@$_AUTH['adm']) return false;

        // UPDATE 
        $banner_id = $body['banner_id'];
        $banner_sort = @$body['banner_sort'];
        $banner_url = @$body['banner_url'];

        // SORT
        $my = new my(dynamic());
        if ($banner_sort) {
            if ($banner_sort > 1) {
                $my->query("UPDATE qmz_banner SET banner_sort = banner_sort + 1 WHERE banner_sort > $banner_sort");
                $my->query("UPDATE qmz_banner SET banner_sort = banner_sort - 1 WHERE banner_sort <= $banner_sort");
            }
            if ($banner_sort == 1) {
                $my->query("UPDATE qmz_banner SET banner_sort = banner_sort + 1 WHERE banner_sort >= $banner_sort");
            }
            $my->update('qmz_banner', ['banner_sort' => $banner_sort], ['banner_id' => $banner_id]);

            // RE-SORT ITEMS (FIX)
            $res = $my->query('SELECT * FROM qmz_banner WHERE banner_status = 1 AND banner_sort >= 0 ORDER BY banner_sort');
            $sort = 0;
            for ($i = 0; $i < count($res); $i++) {
                $sort++;
                $my->update('qmz_banner', ['banner_sort' => $sort], ['banner_id' => $res[$i]['banner_id']]);
            }
        }

        // URL
        if ($banner_url) {
            $my->update('qmz_banner', ['banner_url' => $banner_url], ['banner_id' => $banner_id]);
        }

        return true;
    }
    //------------------------------------------------
    // GALLERY POST
    //------------------------------------------------
    public function galleryPost($body)
    {
        global $_AUTH;
        if (!@$_AUTH['adm']) return false;

        // DATA
        $ins = array(
            'gal_img' => $body['gal_img'],
            'gal_date_insert' => date("Y-m-d H:i:s")
        );

        // QUERY
        $my = new my(dynamic());
        $gal_id = $my->insert('qmz_banner_gallery', $ins);

        // RETURN
        if (is_numeric($gal_id)) return true;
        else return false;
    }
}
