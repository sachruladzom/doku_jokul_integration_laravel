<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JokulController extends Controller
{
    private $baseUrlJokul="https://api-sandbox.doku.com";
    private $paymentJokulEndpoint="/checkout/v1/payment";
    private $validatePaymentJokulEndpoint="/orders/v1/status/";
    private $clientId;
    private $secretKey;
    private $baseWebHook="http://serverpunyakitayangbuatupdatestatus.com/webhook/jokul-payment";
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
                "invoice_number"=> "INV-".$dateNow->timestamp,
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

            return view('embed-jokul',['jokulUrl'=>$paymentObj->url]);
        } else {
            Log::info("error response");
            Log::info($token);
            return view('embed-jokul',['jokulUrl'=>"error","message"=>$token]);
        }

        
    }

    public function getToken($requestBody){
        $requesttimestamp = "";
        $signature=$this->getSignature($requestBody);

        $componentHeader = array("Client-Id"=>$this->clientId,
                                "Request-Id"=>$this->requestId,
                                "Request-Timestamp"=>$this->requestDate,
                                "Signature"=>$signature,
                                "Accept"=>"*/*"
        );

        $dataJson = json_encode($requestBody,JSON_PRETTY_PRINT);
        $urljokul=$this->baseUrlJokul.$this->paymentJokulEndpoint;
        $response = Http::withHeaders($componentHeader)
                    ->withBody($dataJson,"application/json")->post($urljokul);

        return $response;
    }

    public function getSignature($requestBody){
        $digestValue = base64_encode(hash('sha256', json_encode($requestBody,JSON_PRETTY_PRINT), true));
        $componentSignature = "Client-Id:{$this->clientId}\n".
                                "Request-Id:{$this->requestId}\n".
                                "Request-Timestamp:{$this->requestDate}\n".
                                "Request-Target:{$this->paymentJokulEndpoint}\n".
                                "Digest:{$digestValue}";

        $hmac=hash_hmac('sha256', $componentSignature, $this->secretKey, true);
        $signature = base64_encode($hmac);
        // Log::info("Digest sha256: ".$digestSha);
        // Log::info("Digest: ".$digestValue);
        // Log::info($componentSignature);
        // Log::info("Signature HMACSHA256: ".$hmac);

        return "HMACSHA256=".$signature;

    }

    /*
    Name: bak_incomingNotifications()
    function yang ini gak dipake, soalnya create signature ga cocok terus ama signature dari jokul
    */
    public function bak_incomingNotifications(Request $request){
        $notificationHeader = $request->header();
        $notificationBody = $request->all();
        $notificationPath = 'api/payments/notifications'; // Adjust according to your notification path
        $secretKey = $this->secretKey; // Adjust according to your secret key

        $digest = base64_encode(hash('sha256', json_encode($notificationBody), true));
        $rawSignature = "Client-Id:" . $request->header('Client-Id') . "\n"
            . "Request-Id:" . $request->header('Request-Id') . "\n"
            . "Request-Timestamp:" . $request->header('Request-Timestamp') . "\n"
            . "Request-Target:" . $notificationPath . "\n"
            . "Digest:" . $digest;


        $signature = base64_encode(hash_hmac('sha256', $rawSignature, $secretKey, true));
        $finalSignature ='HMACSHA256=' . $signature;

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

    public function incomingNotifications(Request $request){
        $notificationBody = $request->all();
        $obj = $notificationBody;
        if(isset($obj['error'])){
            Log::info($notificationBody);
        } else if(isset($obj['order'])){
            $orderObj=$obj['error'];
            if(!isset($orderObj['invoice_number'])){
                return "No invoice_number key";
            }

            $this->validatePayment($orderObj->invoice_number);
        } else {
            
        }
    }

    public function validatePayment($invoice){
        $urlvalidate=$this->validatePaymentJokulEndpoint.$invoice;
        $randomString= Str::uuid()->toString();
        $dateNow = \Carbon\Carbon::now('Asia/Jakarta');
        $requestDate=$dateNow->toIso8601ZuluString();
        $componentSignature = "Client-Id:{$this->clientId}\n".
                                "Request-Id:{$randomString}\n".
                                "Request-Timestamp:{$requestDate}\n".
                                "Request-Target:{$urlvalidate}";

        $hmac=hash_hmac('sha256', $componentSignature, $this->secretKey, true);
        $signature = base64_encode($hmac);
        
        $componentHeader = array("Client-Id"=>$this->clientId,
                                "Request-Id"=>$randomString,
                                "Request-Timestamp"=>$requestDate,
                                "Signature"=>"HMACSHA256=".$signature
        );

        $urljokul=$this->baseUrlJokul.$urlvalidate;
        $response = Http::withHeaders($componentHeader)->get($urljokul);

        $obj = json_decode($response);
        if(isset($obj->error)){
            Log::info($response);
        } else if(isset($obj->transaction)){
            $transactionObj=$obj->transaction;
            if(!isset($transactionObj->status)){
                return "No status key";
            }

            $trx = array(
                "status"=>$transactionObj->status,
                "date"=>$transactionObj->date,
                "original_request_id"=>$transactionObj->original_request_id
            );
            Log::info(print_r($trx,true));

        } else {
            
        }
    }


}
