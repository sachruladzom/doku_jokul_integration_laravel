<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JokulController extends Controller
{
    private $paymentJokulEndpoint="https://api-sandbox.doku.com/checkout/v1/payment";
    private $clientId;
    private $secretKey;
    private $baseWebHook="http://serverpunyakitayangbuatupdatestatus.com/webhook/jokul-payment";
    private $jokulEndpoint="/checkout/v1/payment";
    private $requestId;
    private $requestDate;
    
    public function __construct(){
        $this->clientId =env("JOKUL_CLIENT_ID",null);
        $this->secretKey=env("JOKUL_SECRET_KEY",null);
    }
    

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
                "callback_url"=> "https://www.krasmart.com/",
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

    public function notifications(Request $request){
        $notificationHeader = $request->header();
        $notificationBody = json_encode($request->all(),JSON_PRETTY_PRINT);
        $notificationPath = '/payments/notifications'; // Adjust according to your notification path
        $secretKey = $this->secretKey; // Adjust according to your secret key

        $digest = base64_encode(hash('sha256', $notificationBody, true));
        $rawSignature = "Client-Id:" . $request->header('Client-Id') . "\n"
            . "Request-Id:" . $request->header('Request-Id') . "\n"
            . "Request-Timestamp:" . $request->header('Request-Timestamp') . "\n"
            . "Request-Target:" . $notificationPath . "\n"
            . "Digest:" . $digest;


        //Log::info($rawSignature);
        $signature = base64_encode(hash_hmac('sha256', $rawSignature, $secretKey, true));
        $finalSignature = 'HMACSHA256=' . $signature;

        if ($finalSignature == $request->header('Signature')) {
            // TODO: Process if Signature is Valid
            Log::info("signature ok");
            return response('OK', 200)->header('Content-Type', 'text/plain');

            // TODO: Do update the transaction status based on the `transaction.status`
        } else {
            Log::info("Invalid Signature");
            Log::info("{$finalSignature} == {$request->header('Signature')}");
            // TODO: Response with 400 errors for Invalid Signature
            return response('Invalid Signature', 400)->header('Content-Type', 'text/plain');
        }
    }


}
