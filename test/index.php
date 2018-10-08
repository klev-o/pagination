<?php

use konstantinLev\pagination\Pagination;

require_once 'DB.php';
require_once '../src/Pagination.php';

$db = DB::getInstance();
$query = 'select * from test';
$result = $db->select($query);
$countAll = count($result);
$pag = new Pagination($query, ['countOnPage' => 3, 'totalCount' => $countAll]);
$queryNew = $pag->getQuery();
$params = $pag->getParams();
$resultNew = $db->select($queryNew, $params);
var_dump($resultNew);
$pag->drawLinkPager();