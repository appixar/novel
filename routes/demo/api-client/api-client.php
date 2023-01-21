<?php
// ALL CATEGS
$_PAGE = [
    "title_main" => "Produtos",
    "title" => "Categorias"
];
// ONE CATEG
if (@$_PAR[0]) $_PAGE["title"] = "Categoria";

// API
$api = new api();

// REQUEST METHOD
$res = @$api->get('/product/categ', ['all' => true])['data'];

// REQUEST FREE QUERY
$query = "SELECT * FROM qmz_banner WHERE banner_sort >= -1";
$res = @$api->get('/query', ['query' => $query])['data'];