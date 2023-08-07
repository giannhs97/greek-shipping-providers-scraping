<?php

require 'simple_html_dom.php';

class Scraping{

    public $_voucher;
    public $_courier;
    public $speedexURL = 'http://www.speedex.gr/speedex/NewTrackAndTrace.aspx?number=';
    public $eltaURL = 'https://itemsearch.elta.gr/el-GR/Query/Direct/';
    public $courierCenterURL = 'https://www.courier.gr/track/result?tracknr=';
    public $easyMailURL = 'https://trackntrace.easymail.gr/';
    public $genikiTaxidromikiURL = 'https://www.taxydromiki.com/track/';
    public $skroutzURL = 'https://api.sendx.gr/user/hp/';

    function __construct($courier, $voucher){
        $this->_courier = $courier;
        $this->_voucher = $voucher;
    }

    function chooseCourier(){

        switch($this->_courier){
            case 'speedex':
                $this->speedexScraping();
                break;
            case 'elta-courier':
                $this->eltaScraping();
                break;
            case 'courier-center':
                $this->courierCenterScraping();
                break;
            case 'easy-mail':
                $this->easyMailScraping();
                break;
            case 'geniki-taxidromiki':
                $this->genikiTaxidromikiScraping();
                break;
            case 'skroutz':
                $this->skroutzScraping();
                break;
            default:
                echo $this->unsupportedCourier();
        }
    }

    //Speedex
    function speedexScraping(){
        $html = file_get_html( $this->speedexURL . $this->_voucher);
        $orderNotFound = $html->find('div.content-body div.container div.alert-warning');

        if($orderNotFound){
            echo $this->orderInfo('Speedex', $this->_voucher, array(), false, false, null);
        }else{
            $titles = $html->find('li.timeline-item div.card-header h4.card-title');
    
            for($i=0; $i<sizeof($titles); $i++){
                
                $orderTrackingLocation = $html->find('li.timeline-item div.card-header h4.card-title', $i)->plaintext;
                $placeAndTime = $html->find('li.timeline-item div.card-header p.card-subtitle span', $i)->plaintext;

                $splitedPlaceAndTime = explode(',', $placeAndTime);

                $updates[$i] = array(
                    'status' => $orderTrackingLocation,
                    'time' => $splitedPlaceAndTime[0],
                    'space' => $splitedPlaceAndTime[1]
                );
            }

            $deliveredTitle = $html->find('div.content-body div.container div.delivered-speedex h4.delivered-title p',0);
            $deliveredPlaceAndTime = $html->find('div.content-body div.container div.delivered-speedex p.delivered-subtitle span',0)->plaintext;
            
            if($deliveredTitle && $deliveredPlaceAndTime){

                $splitedDeliveredPlaceAndTime = explode(',', $deliveredPlaceAndTime);

                $deliveredInfo = array(
                    'status' => $deliveredTitle->plaintext,
                    'time' => $splitedDeliveredPlaceAndTime[0],
                    'space' => $splitedDeliveredPlaceAndTime[1]
                );

                $last = $deliveredInfo;
                $delivered = true;
            }else{
                $last = end($updates);
                $delivered = false;
            }

            echo $this->orderInfo('Speedex', $this->_voucher, $updates, true, $delivered, $last);
        }

    }

    //ELTA Courier
    function eltaScraping(){
        $html = file_get_html( $this->eltaURL . $this->_voucher);
        $orderNotFound = $html->find('div.searchResultItem table');

        if(!$orderNotFound){
            echo $this->orderInfo('ELTA Courier', $this->_voucher, array(), false, false, null);
        }else{
            $orderDetails = $html->find('div.searchResultItem table tbody tr');
    
            for($i=0; $i<sizeof($orderDetails); $i++){

                $orderRow = $html->find('div.searchResultItem table tbody tr',$i);

                $updates[$i] = array(
                    'status' => $html->find('div.searchResultItem table tbody tr td[data-title="Κατάσταση"]',$i)->plaintext,
                    'time' => $orderRow->children[0]->plaintext,
                    'space' => $html->find('div.searchResultItem table tbody tr td[data-title="Περιοχή"]',$i)->plaintext
                );
            }
            //var_dump($updates);

            $orderDelivered = $updates[0]['status'];
            $orderDelivered = explode(' ', $orderDelivered);

            if(in_array('παραδόθηκε', $orderDelivered)){
                $delivered = true;
            }else{
                $delivered = false;
            }

            echo $this->orderInfo('ELTA Courier', $this->_voucher, $updates, true, $delivered, $updates[0]);
        }
    }

    // Courier Center
    function courierCenterScraping(){
        $html = file_get_html( $this->courierCenterURL . $this->_voucher);
        $orderNotFound = $html->find('div.track-result h4.error');

        if($orderNotFound){
            echo $this->orderInfo('Courier Center', $this->_voucher, array(), false, false, null);
        }else{
            $orderDetails = $html->find('div.track-result div.track-table div.tr');
    
            for($i=0; $i<sizeof($orderDetails)-1; $i++){

                $date = $html->find('div.track-result div.track-table div.tr div.date',$i)->plaintext;
                $time = $html->find('div.track-result div.track-table div.tr div.time',$i)->plaintext;

                $updates[$i] = array(
                    'status' => $html->find('div.track-result div.track-table div.tr div.action',$i)->plaintext,
                    'time' => $date . ' ' . $time,
                    'space' => $html->find('div.track-result div.track-table div.tr div.area',$i)->plaintext
                );
            }
            //var_dump($updates);
            $deliveredText = $html->find('div.track-result div.track-table div.tr.mobileapi div.td.action',0)->plaintext;
            $deliveredText = explode(' ', $deliveredText);

            if(in_array('ΠΑΡΑΔΟΘΗΚΕ', $deliveredText)){
                $delivered = true;
            }else{
                $delivered = false;
            }

            echo $this->orderInfo('Courier Center', $this->_voucher, $updates, true, $delivered, $updates[0]);
        }
    }

    // Easy Mail
    function easyMailScraping(){
        $html = file_get_html($this->easyMailURL . $this->_voucher);
        $orderNotFound = $html->find('div.container-fluid div.alert');

        if($orderNotFound){
            echo $this->orderInfo('Easy Mail', $this->_voucher, array(), false, false, null);
        }else{
            $orderDetailsTable = $html->find('div.container-fluid div.row.mt-3 table.table-hover tbody tr');

            for($i=0; $i<sizeof($orderDetailsTable); $i++){

                $orderDetailsRow = $html->find('div.container-fluid div.row.mt-3 table.table-hover tbody tr',$i);

                $updates[$i] = array(
                    'status' => $orderDetailsRow->children[2]->plaintext,
                    'time' => $orderDetailsRow->children[1]->plaintext,
                    'space' => $orderDetailsRow->children[3]->plaintext
                );
            }

            if($updates[0]['status'] == 'Παραδόθηκε'){
                $delivered = true;
            }else{
                $delivered = false;
            }

            echo $this->orderInfo('Easy Mail', $this->_voucher, $updates, true, $delivered, $updates[0]);
        }
    }

    // Geniki Taxidromiki
    function genikiTaxidromikiScraping(){
        $html = file_get_html($this->genikiTaxidromikiURL . $this->_voucher);
        $orderNotFound = $html->find('div.result-tracking-message div.empty-text');

        if($orderNotFound){
            echo $this->orderInfo('Geniki Taxidromiki', $this->_voucher, array(), false, false, null);
        }else{
            $orderDetails = $html->find('div.result-tracking-message div.tracking-checkpoint');
            $strongs = $html->find('div.result-tracking-message div.tracking-checkpoint strong');
            $strongs->outertext = '';
            
            for($i=0; $i<sizeof($orderDetails); $i++){

                $statusStrong = $html->find('div.result-tracking-message div.tracking-checkpoint div.checkpoint-status strong',$i)->plaintext;
                $dateStrong = $html->find('div.result-tracking-message div.tracking-checkpoint div.checkpoint-date strong',$i)->plaintext;
                $timeStrong = $html->find('div.result-tracking-message div.tracking-checkpoint div.checkpoint-time strong',$i)->plaintext;
                $spaceStrong = $html->find('div.result-tracking-message div.tracking-checkpoint div.checkpoint-location strong',$i)->plaintext;

                $status = $html->find('div.result-tracking-message div.tracking-checkpoint div.checkpoint-status',$i)->plaintext;
                $date = $html->find('div.result-tracking-message div.tracking-checkpoint div.checkpoint-date',$i)->plaintext;
                $time = $html->find('div.result-tracking-message div.tracking-checkpoint div.checkpoint-time',$i)->plaintext;
                $space = $html->find('div.result-tracking-message div.tracking-checkpoint div.checkpoint-location',$i)->plaintext;

                $trimedDate = str_replace($dateStrong, '', $date);
                $trimedTime = str_replace($timeStrong, '', $time);
                $date_and_time = $trimedDate .' ' . $trimedTime;

                $updates[$i] = array(
                    'status' => str_replace($statusStrong, '', $status),
                    'time' => $date_and_time,
                    'space' => str_replace($spaceStrong, '', $space)
                );
            }
            

            $delivered = $html->find('div.result-tracking-message div.tracking-checkpoint.tracking-delivery');

            if($delivered){
                $delivered = true;
            }else{
                $delivered = false;
            }

            echo $this->orderInfo('Geniki Taxidromiki', $this->_voucher, $updates, true, $delivered, end($updates));
        }
    }

    // Scroutz
    function skroutzScraping(){
        $externalLink = $this->skroutzURL . $this->_voucher;
        $chExternal = curl_init();
        curl_setopt($chExternal, CURLOPT_RETURNTRANSFER, true); // Will return the response, if false it print the response
        curl_setopt($chExternal, CURLOPT_URL,$externalLink); // Set the url
        $courierInfo=curl_exec($chExternal); // Execute
        curl_close($chExternal); // Closing
        $courierInfoDocoded = json_decode($courierInfo, true);

        if($courierInfoDocoded['success'] === false){
            echo $this->orderInfo('Skroutz', $this->_voucher, array(), false, false, null);
        }else{
            $i = 0;
            foreach($courierInfoDocoded['trackingDetails'] as $info){
                $updates[$i] = array(
                    'status' => $info['description_gr'],
                    'time' => $info['updatedAt'],
                    'space' => ''
                );
        
                $i++;
            }
            
            $delivered = end($updates)['status'];

            if($delivered == 'Παραδόθηκε'){
                $delivered = true;
            }else{
                $delivered = false;
            }

            echo $this->orderInfo('Skroutz', $this->_voucher, $updates, true, $delivered, end($updates));
        }
    }

    function orderInfo($courier, $voucher, $updates, $found, $delivered, $last){

        $orderInfo = array(
            'courier' => $courier,
            'tracking_number' => $voucher,
            'updates' => $updates,
            'found' => $found,
            'delivered' => $delivered,
            'last' => $last
        );

        return json_encode($orderInfo, JSON_UNESCAPED_UNICODE );
    }

    function unsupportedCourier(){

        $message = array(
            'courier' => 'This courier is currently anavailable!',
            'tracking_number' => $this->_voucher,
            'updates' => [ ],
            'found' => false,
            'delivered' => false,
            'last' => null
        );

        return json_encode($message, JSON_UNESCAPED_UNICODE );
    }
}
?>