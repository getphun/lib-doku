<?php
/**
 * doku service
 * @package lib-doku
 * @version 0.0.1
 * @upgrade true
 */

namespace LibDoku\Service;

class Doku {
    
    private $uries = [
        'development' => [
            'request-payment' => 'https://staging.doku.com/Suite/Receive',
            'check-status'    => 'https://staging.doku.com/Suite/CheckStatus'
        ],
        'production' => [
            'request-payment' => 'https://pay.doku.com/Suite/Receive',
            'check-status'    => 'https://pay.doku.com/Suite/CheckStatus'
        ]
    ];
    
    private $currs = [
        'IDR' => 360,
        'MYR' => 458,
        'PHP' => 608,
        'SGD' => 702,
        'USD' => 840
    ];
    
    private $mallid;
    private $sharedkey;
    private $chainmerchant;
    private $transid;
    
    /**
     * Convert currency to numeric
     * @param string curr The currency string
     * @return integer currency in numeric representation
     */
    private function currencyToNum($curr){
        return $this->currs[$curr];
    }
    
    /**
     * Generate WORDS for payment request
     * @param array opts
     *  @param integer amount Total payment.
     *  @param mixed transid Transaction ID.
     *  @param string currency Payment currency.
     * @return string WORDS
     */
    private function genRPWords($opts){
        $str = $opts['amount']
             . $this->mallid
             . $this->sharedkey
             . $opts['transid'];
        
        if($opts['currency'] != 'IDR')
            $str.= $this->currencyToNum($opts['currency']);
        
        return sha1($str);
    }
    
    /**
     * Generate WORDS for notify request
     * @param array opts
     *  @param integer amount Total payment.
     *  @param mixed transid Transaction ID.
     *  @param string currency Payment currency.
     * @return string WORDS
     */
    private function genNotifyWord($opts){
        $dis = \Phun::$dispatcher;
        
        $resultmsg = $dis->req->getPost('RESULTMSG');
        $verifystatus = $dis->req->getPost('VERIFYSTATUS');
        
        $str = $opts['amount']
             . $this->mallid
             . $this->sharedkey
             . $opts['transid']
             . $resultmsg
             . $verifystatus;
        
        if($opts['currency'] != 'IDR')
            $str.= $this->currencyToNum($opts['currency']);
        
        return sha1($str);
    }
    
    /**
     * Generate WORDS for payment request
     * @param array opts
     *  @param integer amount Total payment.
     *  @param mixed transid Transaction ID.
     *  @param string currency Payment currency.
     * @return string WORDS
     */
    public function genPSWords($opts){
        $str = $this->mallid
             . $this->sharedkey
             . $opts['transid'];
        
        if($opts['currency'] != 'IDR')
            $str.= $this->currencyToNum($opts['currency']);
        
        return sha1($str);
    }
    
    /**
     * Generate WORDS for payment status response
     * @param array opts
     *  @param integer amount
     * @return string WORDS
     */
    private function genRPSWords($opts){
        $str = $opts['amount']
             . $this->mallid
             . $this->sharedkey
             . $opts['transid']
             . $opts['resultmsg']
             . $opts['verifystatus'];
        
        if($opts['currency'] != 'IDR')
            $str.= $this->currencyToNum($opts['currency']);
        
        return sha1($str);
    }
    
    public function __construct(){
        $dis = \Phun::$dispatcher;
        $config = $dis->config->doku;
        
        $this->mallid        = $config['mallid'];
        $this->sharedkey     = $config['sharedkey'];
        $this->chainmerchant = $config['chainmerchant'];
        
        if($dis->req->method == 'POST')
            $this->transid = $dis->req->getPost('TRANSIDMERCHANT');
    }
    
    /**
     * Get payment status
     * @param array opts
     *  @param integer amount Total payment total.
     *  @param mixed transid Transaction ID.
     *  @param string currency Payment currency. Default IDR.
     *  @param string session Self unique id?
     * @return array payment status.
     */
    public function getPaymentStatus($opts){
        $opts['currency'] = $opts['currency'] ?? 'IDR';
        $currency = $this->currencyToNum($opts['currency']);
        
        $post_fields = [
            'MALLID'            => $this->mallid,
            'CHAINMERCHANT'     => $this->chainmerchant,
            'TRANSIDMERCHANT'   => $opts['transid'],
            'SESSIONID'         => $opts['session'],
            'WORDS'             => $this->genPSWords($opts),
            'CURRENCY'          => $currency,
            'PURCHASECURRENCY'  => $currency
        ];
        
        $ch = curl_init($this->uries[ENVIRONMENT]['check-status']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        $xml  = simplexml_load_string($result);
        $json = json_encode($xml);
        $data = json_decode($json);
        
        $dok_words = $data->WORDS;
        $exp_words = $this->genRPSWords([
            'amount'        => $opts['amount'],
            'currency'      => $opts['currency'],
            'transid'       => $opts['transid'],
            'resultmsg'     => $data->RESULTMSG,
            'verifystatus'  => $data->VERIFYSTATUS
        ]);
        
        if($dok_words != $exp_words)
            return false;
        return $data;
    }
    
    /**
     * Get form action and list of form field to do payment request
     * @param array opts
     *  @param integer amount Total payment total.
     *  @param mixed transid Transaction ID.
     *  @param string payment.type Payment type. Default SALE.
     *  @param string currency Payment currency. Default IDR.
     *  @param string session Self unique id?
     *  @param string user.name The name of requester user.
     *  @param string user.email The email of requester user.
     *  @param string basket The basket info.
     *      [LABEL0],[PRICE0],[QTY0],[TOTAL0];[LABEL1],[PRICE1],[QTY1],[TOTAL1]
     * @return array form for payment request
     */
    public function getRPForm($opts){
        $opts['currency'] = $opts['currency'] ?? 'IDR';
        
        $currency = $this->currencyToNum($opts['currency']);
        
        $result = [
            'action' => $this->uries[ENVIRONMENT]['request-payment'],
            'fields' => [
                'MALLID'            => $this->mallid,
                'CHAINMERCHANT'     => $this->chainmerchant,
                'AMOUNT'            => $opts['amount'],
                'PURCHASEAMOUNT'    => $opts['amount'],
                'TRANSIDMERCHANT'   => $opts['transid'],
                'PAYMENTTYPE'       => $opts['payment.type'] ?? 'SALE',
                'WORDS'             => $this->genRPWords($opts),
                'REQUESTDATETIME'   => date('YmdHis'),
                'CURRENCY'          => $currency,
                'PURCHASECURRENCY'  => $currency,
                'SESSIONID'         => $opts['session'],
                'NAME'              => $opts['user.name'],
                'EMAIL'             => $opts['user.email'],
                'BASKET'            => $opts['basket']
            ]
        ];
        
        return $result;
    }
    
    /**
     * Get request transaction id based on POST[TRANSIDMERCHANT]
     * @return string transaction id
     */
    public function getTransId(){
        return $this->transid;
    }
    
    /**
     * Check if payment success
     * @return boolean true on success false otherwise.
     */
    public function isPaymentSuccess(){
        $dis = \Phun::$dispatcher;
        
        $result = $dis->req->getPost('RESULTMSG');
        return $result == 'SUCCESS';
    }
    
    /**
     * Verify current notif request
     * @param array opts List of options
     *  @param integer amount Payment request amount
     *  @param int|str transid Transaction id.
     * @return boolean true on verified false otherwise.
     */
    public function verifyNotif($opts){
        $dis = \Phun::$dispatcher;
        
        $req_words = $dis->req->getPost('WORDS');
        $sel_words = $this->genNotifyWord([
            'amount'    => $opts['amount'],
            'transid'   => $opts['transid'],
            'currency'  => $opts['currency']
        ]);
        
        return $req_words == $sel_words;
    }
}