<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class NotifTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_example()
    {
        $data='{
            "service": {
                "id": "VIRTUAL_ACCOUNT"
            },
            "acquirer": {
                "id": "BCA"
            },
            "channel": {
                "id": "VIRTUAL_ACCOUNT_BCA"
            },
            "transaction": {
                "status": "SUCCESS",
                "date": "2021-01-27T03:24:23Z",
                "original_request_id": "15022aab-444f-4b04-afa8-ddfce89432ec"
            },
            "order": {
                "invoice_number": "INV-20210124-0001",
                "amount": 150000
            },
            "virtual_account_info": {
                "virtual_account_number": "1900600000000046"
            },
            "virtual_account_payment": {
                "identifer": [
                    {
                        "name": "REQUEST_ID",
                        "value": "7892931"
                    },
                    {
                        "name": "REFERENCE",
                        "value": "6769200"
                    },
                    {
                        "name": "CHANNEL_TYPE",
                        "value": "6010"
                    }
                ]
            }
        }';

        $componentHeader = array("Client-Id"=>"MCH-1040-1640168489748",
                                "Request-Id"=>"4b522981-c02a-4ddd-9477-706c0a6322db",
                                "Request-Timestamp"=>"2022-04-04T10:22:00Z",
                                "Signature"=>"HMACSHA256=4gPtKGUEKyyuLOoa48KlIe1idhpviWl5j70CrKSxcLI=",
        );


        $response = $this->post('api/payments/notifications',json_decode($data,true),$componentHeader);

        $response->assertStatus(200);
    }
}
