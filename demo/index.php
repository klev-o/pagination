<?php

use konstantinLev\pagination\Pagination;

require_once '../src/Pagination.php';

$pag = new Pagination('demo', [
    'countOnPage' => 10,
    'totalCount' => 100,
    'className' => 'test',
    'leftRightNum' => 2,
    'controls' => [
        '<span class="glyphicon glyphicon-arrow-left"></span>',
        '<span class="glyphicon glyphicon-arrow-right"></span>'
    ],
]);
$data = $pag->demo();
?>



<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <title>Demo Awesome Pag</title>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="text-center">
                <h2>Demo pagination features!</h2>
                <?php foreach ($data as $val){ ?>
                        <div class="block" style="display: inline-block;border: 1px solid gray;padding: 15px;">
                            <div class="id"><?=$val['title']?></div>
                        </div>
                <?php } ?>
                <br><br>
                <?=$pag->drawLinkPager()?>
            </div>
        </div>
    </div>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
</body>
</html>






