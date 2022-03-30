<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JokulController extends Controller
{
    private $paymentJokulEndpoint="https://api-sandbox.doku.com/checkout/v1/payment";
    private $clientId = "<your_client_id>";
    private $secretKey="<your_secret_key>";
    private $baseWebHook="http://serverpunyakitayangbuatupdatestatus.com/webhook/jokul-payment";
    private $jokulEndpoint="/checkout/v1/payment";
    private $requestId;
    private $requestDate;
    

    public function payment(){
        $randomString = Str::uuid()->toString();
        $dateNow = \Carbon\Carbon::now('Asia/Jakarta');
        $requestId = $randomString;
        $this->requestId = $requestId;

        $requestDate=$dateNow->toIso8601ZuluString();
        $this->requestDate = $requestDate;

        $targetPath=$this->baseWebHook."/".$requestId;
        $this->targetPath = $targetPath;

        $requestBody = array(
            "order"=> array(
                "amount"=>  10500,
                "invoice_number"=> "INV-648623403",
                "currency"=> "IDR",
                "callback_url"=> "http://serverkita.com/webhook/jokul-payment",
                "line_items"=> array(
                    array(
                        "name"=> "DOKU T-Shirt",
                        "price"=> 10500,
                        "quantity"=> 1,
                    )
                )
            ),
            "payment"=> array(
                "payment_due_date"=> 60,
            ),
            "customer"=> array(
                "name"=> "Anton Budiman",
                "email"=> "anton@doku.com",
                "phone"=> "+6285694566147",
                "address"=> "Plaza Asia Office Park Unit 3",
                "country"=> "ID"
            )
        );

        $token = $this->getToken($requestBody);
        $obj = json_decode($token);
        if(isset($obj->error)){
            echo $token;
        } else if(isset($obj->response)){
            $responObj=$obj->response;
            if(!isset($responObj->payment)){
                return "No Payment key";
            }

            $paymentObj=$responObj->payment;
            if(!isset($paymentObj->url)){
                return "No url key";
            }

            //echo $paymentObj->url;
            return view('embed-jokul',['jokulUrl'=>$paymentObj->url]);
        }

        
    }

    public function getToken($requestBody){
        $requesttimestamp = "";
        $signature=$this->getSignature($requestBody);

        $componentHeader = array("Client-Id"=>$this->clientId,
                                "Request-Id"=>$this->requestId,
                                "Request-Timestamp"=>$this->requestDate,
                                "Signature"=>$signature,
                               // "Content-Type"=>"application/json",
                                "Accept"=>"*/*"
        );

        //Log::info(print_r($componentHeader,true));
        $dataJson = json_encode($requestBody,JSON_PRETTY_PRINT);
        $response = Http::withHeaders($componentHeader)
                    ->withBody($dataJson,"application/json")->post($this->paymentJokulEndpoint);

        //$obj = json_decode($response);
        return $response;
    }

    public function getSignature($requestBody){
        Log::info(json_encode($requestBody,JSON_PRETTY_PRINT));
        $digestSha=hash('sha256', json_encode($requestBody,JSON_PRETTY_PRINT));
        $digestValue = base64_encode(hash('sha256', json_encode($requestBody,JSON_PRETTY_PRINT), true));
        $componentSignature = "Client-Id:{$this->clientId}\n".
                                "Request-Id:{$this->requestId}\n".
                                "Request-Timestamp:{$this->requestDate}\n".
                                "Request-Target:{$this->jokulEndpoint}\n".
                                "Digest:{$digestValue}";

        $hmac=hash_hmac('sha256', $componentSignature, $this->secretKey, true);
        $signature = base64_encode($hmac);
        // Log::info("Digest sha256: ".$digestSha);
        // Log::info("Digest: ".$digestValue);
        // Log::info($componentSignature);
        // Log::info("Signature HMACSHA256: ".$hmac);

        return "HMACSHA256=".$signature;

    }


}
