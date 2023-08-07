<?php

include('scraping-class.php');

$courier = $_GET['courier'];
$voucher = $_GET['voucher'];

/*echo $courier;
echo '</br>';
echo $voucher;*/

if(isset($courier) && isset($voucher)){
    $orderInfo = new Scraping($courier, $voucher);
    $orderInfo->chooseCourier();
}else{
    echo 'order not found';
}

?>