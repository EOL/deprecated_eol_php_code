<?php
namespace php_active_record;

class CyberSource
{
    ##################
    #  CyberSource Hosted Order Page library.  Inserts fields into the
    #  checkout form for posting data to the CyberSource Hosted Order
    #  Page.


    static function php_hmacsha1($data, $key) {
      $klen = strlen($key);
      $blen = 64;
      $ipad = str_pad("", $blen, chr(0x36));
      $opad = str_pad("", $blen, chr(0x5c));

      if ($klen <= $blen) {
        while (strlen($key) < $blen) {
          $key .= "\0";
        }				#zero-fill to blocksize
      } else {
        $key = self::cybs_sha1($key);	#if longer, pre-hash key
      }
      $key = str_pad($key, strlen($ipad) + strlen($data), "\0");

      return self::cybs_sha1(($key ^ $opad) . self::cybs_sha1($key ^ $ipad . $data));
    }

    # calculates SHA-1 digest of the input string
    # cleaned up from John Allen's "SHA in 8 lines of perl5"
    # at http://www.cypherspace.org/~adam/rsa/sha.html
    #
    # returns the hash in a (binary) string

    static function cybs_sha1($in) {

    if(function_exists('sha1')){
       return pack("H*", sha1($in));
    }

      $indx = 0;
      $chunk = "";

      $A = array(1732584193, 4023233417, 2562383102,  271733878, 3285377520);
      $K = array(1518500249, 1859775393, 2400959708, 3395469782);
      $a = $b = $c = $d = $e = 0;
      $l = $p = $r = $t = 0;

      do{
        $chunk = substr($in, $l, 64);
        $r = strlen($chunk);
        $l += $r;

        if ($r<64 && !$p++) {
          $r++;
          $chunk .= "\x80";
        }
        $chunk .= "\0\0\0\0";
        while (strlen($chunk) % 4 > 0) { 
          $chunk .= "\0";
        }
        $len = strlen($chunk) / 4;
        if ($len > 16) $len = 16;
        $fmt = "N" . $len;
        $W = array_values(unpack($fmt, $chunk));
        if ($r < 57 ) { 
          while (count($W) < 15) {
    	array_push($W, "\0");
          }
          $W[15] = $l*8;
        }

        for ($i = 16; $i <= 79; $i++) {
          $v1 = self::d($W, $i-3);
          $v2 = self::d($W, $i-8);
          $v3 = self::d($W, $i-14);
          $v4 = self::d($W, $i-16);
          array_push($W, L($v1 ^ $v2 ^ $v3 ^ $v4, 1));
        }

        list($a,$b,$c,$d,$e)=$A;

        for ($i = 0; $i<=79; $i++) {
          $t0 = 0;
          switch(intval($i/20)) {
    	case 1:
    	case 3:
    	$t0 = self::F1($b, $c, $d);
    	break;
    	case 2:
    	$t0 = self::F2($b, $c, $d);
    	break;
          default:
    	$t0 = self::F0($b, $c, $d);
    	break;
          }
          $t = self::M($t0 + $e  + self::d($W, $i) + self::d($K, $i/20) + self::L($a, 5));
          $e = $d;
          $d = $c;
          $c = self::L($b,30);
          $b = $a;
          $a = $t;
        }

        $A[0] = self::M($A[0] + $a);
        $A[1] = self::M($A[1] + $b);
        $A[2] = self::M($A[2] + $c);
        $A[3] = self::M($A[3] + $d);
        $A[4] = self::M($A[4] + $e);

      }while ($r>56);
      $v = pack("N*", $A[0], $A[1], $A[2], $A[3], $A[4]);
      return $v;
    }

    #### Ancillary routines used by sha1

    static function dd($x) {
      if (defined($x)) return $x;
      return 0;
    }

    static function d($arr, $x) {
      if ($x < count($arr)) return $arr[$x];
      return 0;
    }

    static function F0($b, $c, $d) {
      return $b & ($c ^ $d) ^ $d;
    }

    static function F1($b, $c, $d) {
      return $b ^ $c ^ $d;
    }

    static function F2($b, $c, $d) {
      return ($b | $c) & $d | $b & $c;
    }

    # ($num)
    static function M($x) {
      $m = 1+~0;
      if ($m == 0) return $x;
      return($x - $m * intval($x/$m));
    }

    # ($string, $count)
    static function L($x, $n) { 
      return ( ($x<<$n) | ((pow(2, $n) - 1) & ($x>>(32-$n))) );
    }

    ####
    #### end of HMAC SHA1 implementation #####




    ####
    #### HOP functions
    #### Copyright 2003, CyberSource Corporation.  All rights reserved.
    ####

    static function getmicrotime(){ 
      list($usec, $sec) = explode(" ",microtime());
      $usec = (int)((float)$usec * 1000);
      while (strlen($usec) < 3) { $usec = "0" . $usec; }
      return $sec . $usec;
    }


    static function hopHash($data, $key) {
        return base64_encode(self::php_hmacsha1($data, $key));
    }

    static function getMerchantID() { return CYBERSOURCE_MERCHANT_ID; }
    static function getPublicKey()  { return CYBERSOURCE_PUBLIC_KEY; }
    static function getPrivateKey() { return CYBERSOURCE_PRIVATE_KEY; }
    static function getSerialNumber() { return CYBERSOURCE_SERIAL_NUMBER; }

    #### HOP integration function
    static function InsertSignature($amount, $currency) {
      if(!isset($amount)){ $amount = "0.00"; }
      if(!isset($currency)){ $currency = "usd"; }
      $merchantID = self::getMerchantID();
      $timestamp = self::getmicrotime();
      $data = $merchantID . $amount . $currency . $timestamp;
      $pub = self::getPublicKey();
      $serialNumber = self::getSerialNumber();
      $pub_digest = self::hopHash($data, $pub);

      echo('<input type="hidden" name="amount" value="' . $amount . '">' . "\n");
      echo('<input type="hidden" name="currency" value="' . $currency . '">' . "\n");
      echo('<input type="hidden" name="orderPage_timestamp" value="' . $timestamp . '">' . "\n");
      echo('<input type="hidden" name="merchantID" value="' . $merchantID . '">' . "\n");
      echo('<input type="hidden" name="orderPage_signaturePublic" value="' . $pub_digest . '">' . "\n");
      echo('<input type="hidden" name="orderPage_version" value="4">' . "\n");
      echo('<input type="hidden" name="orderPage_serialNumber" value="' . $serialNumber . '">' . "\n");
    }

    static function InsertSignature3($amount, $currency, $orderPage_transactionType) {
      if(!isset($amount)){ $amount = "0.00"; }
      if(!isset($currency)){ $currency = "usd"; }
      $merchantID = self::getMerchantID();
      $timestamp = self::getmicrotime();
      $data = $merchantID . $amount . $currency . $timestamp . $orderPage_transactionType;
      $pub = self::getPublicKey();
      $serialNumber = self::getSerialNumber();
      $pub_digest = self::hopHash($data, $pub);

      echo('<input type="hidden" name="orderPage_transactionType" value="' . $orderPage_transactionType . '">' . "\n");
      echo('<input type="hidden" name="amount" value="' . $amount . '">' . "\n");
      echo('<input type="hidden" name="currency" value="' . $currency . '">' . "\n");
      echo('<input type="hidden" name="orderPage_timestamp" value="' . $timestamp . '">' . "\n");
      echo('<input type="hidden" name="merchantID" value="' . $merchantID . '">' . "\n");
      echo('<input type="hidden" name="orderPage_signaturePublic" value="' . $pub_digest . '">' . "\n");
      echo('<input type="hidden" name="orderPage_version" value="4">' . "\n");
      echo('<input type="hidden" name="orderPage_serialNumber" value="' . $serialNumber . '">' . "\n");
    }

    static function InsertSubscriptionSignature($subscriptionAmount, 
        $subscriptionStartDate, 
        $subscriptionFrequency, 
        $subscriptionNumberOfPayments,
        $subscriptionAutomaticRenew){
      if(!isset($subscriptionFrequency)){ return; }
      if(!isset($subscriptionAmount)){ $subscriptionAmount = "0.00"; }
      if(!isset($subscriptionStartDate)){ $subscriptionStartDate = "00000000"; }
      if(!isset($subscriptionNumberOfPayments)){ $subscriptionNumberOfPayments = "0"; }
      if(!isset($subscriptionAutomaticRenew)){ $subscriptionAutomaticRenew = "true"; }
      $data = $subscriptionAmount . $subscriptionStartDate . $subscriptionFrequency . $subscriptionNumberOfPayments . $subscriptionAutomaticRenew;
      $pub = self::getPublicKey();
      $pub_digest = self::hopHash($data, $pub);
      echo('<input type="hidden" name="recurringSubscriptionInfo_amount" value="' . $subscriptionAmount . '">' . "\n");
      echo('<input type="hidden" name="recurringSubscriptionInfo_numberOfPayments" value="' . $subscriptionNumberOfPayments . '">' . "\n");
      echo('<input type="hidden" name="recurringSubscriptionInfo_frequency" value="' . $subscriptionFrequency . '">' . "\n");
      echo('<input type="hidden" name="recurringSubscriptionInfo_automaticRenew" value="' . $subscriptionAutomaticRenew . '">' . "\n");
      echo('<input type="hidden" name="recurringSubscriptionInfo_startDate" value="' . $subscriptionStartDate . '">' . "\n");
      echo('<input type="hidden" name="recurringSubscriptionInfo_signaturePublic" value="' . $pub_digest . '">' . "\n");
    }
    
    static function InsertSubscriptionIDSignature($subscriptionID){
      if(!isset($subscriptionID)){ return; }
      $pub = self::getPublicKey();
      $pub_digest = self::hopHash($subscriptionID, $pub);
      echo('<input type="hidden" name="paySubscriptionCreateReply_subscriptionID" value="' . $subscriptionID . '">' . "\n");
      echo('<input type="hidden" name="paySubscriptionCreateReply_subscriptionIDPublicSignature" value="' . $pub_digest . '">' . "\n");
    }

    static function VerifySignature($data, $signature) {
        $pub = self::getPublicKey();
        $pub_digest = self::hopHash($data, $pub);
        return strcmp($pub_digest, $signature) == 0;
    }

    static function VerifyTransactionSignature($message) {
        $fields = split(',', $message['signedFields']);
        $data = '';
        foreach($fields as $field) {
            $data .= $message[$field];
        }
        return self::VerifySignature($data, $message['transactionSignature']);
    }

    #
    #
    #  end CyberSource Hosted Order Page library.
    ##################
    
}

?>