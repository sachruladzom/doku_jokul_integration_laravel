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
        $data='
        {
            "service": {
                "id": "VIRTUAL_ACCOUNT"
            },
            "acquirer": {
                "id": "BANK_SYARIAH_MANDIRI"
            },
            "channel": {
                "id": "VIRTUAL_ACCOUNT_BANK_SYARIAH_MANDIRI"
            },
            "order": {
                "invoice_number": "INV-1649069542",
                "amount": 10500
            },
            "virtual_account_info": {
                "virtual_account_number": "6059000000004632"
            },
            "virtual_account_payment": {
                "date": "20220404175246",
                "systrace_number": "1523",
                "reference_number": "88779489",
                "channel_code": "6019",
                "identifier": [
                    {
                        "name": "PAY_TERMINAL_ID",
                        "value": null
                    },
                    {
                        "name": "BANK_REFERENCE",
                        "value": "88779489"
                    },
                    {
                        "name": "PAY_CHANNEL",
                        "value": "6019"
                    }
                ]
            },
            "transaction": {
                "status": "SUCCESS",
                "date": "2022-04-04T10:52:46Z",
                "original_request_id": "4c6ca18b-9775-400e-a016-948865a231f5"
            }
        }';

        $componentHeader = array("Client-Id"=>"MCH-1040-1640168489748",
                                "Request-Id"=>"VIRTUAL_ACCOUNT_BANK_SYARIAH_MANDIRI2226220404175246194107165199222005375399",
                                "Request-Timestamp"=>"2022-04-04T10:52:46Z",
                                "Signature"=>"HMACSHA256=naRhxVNAesU73Et3yDilGM4vqjP9vTudXvAOStQ6f0Q=",
        );


        $response = $this->post('api/payments/notifications',json_decode($data,true),$componentHeader);

        $response->assertStatus(200);
    }
}
