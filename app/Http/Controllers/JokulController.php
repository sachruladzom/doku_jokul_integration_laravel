<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class JokulController extends Controller
{
    private $paymentJokulEndpoint="https://api-sandbox.doku.com/checkout/v1/payment";
    private $clientId = "MCH-1040-1640168489748";
    private $secretKey="SK-XNHofeU4oqUhSO98812o";
    private $baseWebHook="http://serverkita.com/webhook/jokul-payment";
    private $jokulEndpoint="/doku-virtual-account/v2/payment-code";
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

        $requestBody = [
            "order"=> [
                "amount"=>  90000,
                "invoice_number"=> "INV-20210231-0001",
                "line_items"=> [
                    [
                        "name"=> "T-Shirt Red",
                        "price"=> 30000,
                        "quantity"=> 2,
                        "sku"=> "1101",
                        "category"=> "Shirt"
                    ]
                ],
                "currency"=> "IDR",
                "callback_url"=> $this->baseWebHook,
                
            ],
            "payment"=> [
                "payment_due_date"=> 60,
            ],
            "customer"=> [
                "id"=> "CUST-0001",
                "name"=> "Anton Budiman",
                "email"=> "anton@example.com",
                "phone"=> "6285694566147",
                "address"=> "Menara Mulia Lantai 8",
                "country"=> "ID"
            ]
            ];

        $token = $this->getToken($requestBody);
        echo $token;
    }

    public function getToken($requestBody){
        $requesttimestamp = "";
        $signature=$this->getSignature($requestBody);

        $componentHeader = array("Client-Id"=>$this->clientId,
                                "Request-Id"=>$this->requestId,
                                "Request-Timestamp"=>$this->requestDate,
                                "Signature"=>" HMACSHA256=" . $signature
        );

        //print_r($componentHeader);
        $response = Http::withHeaders($componentHeader)->post($this->paymentJokulEndpoint, $requestBody);

        return $response;
    }

    public function getSignature($requestBody){
        $digestValue = base64_encode(hash('sha256', json_encode($requestBody), true));
        $componentSignature = "Client-Id:{$this->clientId}\n".
                                "Request-Id:{$this->requestId}\n".
                                "Request-Timestamp:{$this->requestDate}\n".
                                "Request-Target:{$this->jokulEndpoint}\n".
                                "Digest:{$digestValue}";

        $signature = base64_encode(hash_hmac('sha256', $componentSignature, $this->secretKey, true));
        echo $componentSignature;
        return $signature;

    }


}
