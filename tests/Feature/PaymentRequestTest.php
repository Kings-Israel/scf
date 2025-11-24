<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PaymentRequestTest extends TestCase
{
  // TEST: Bank can see payment request
  // TEST: Bank User can approve payment request
  // TEST: Bank User can reject payment request
  // TEST: Bank Checker user can approve payment request
  // TEST: Bank Checker user can reject payment request
  // TEST: Bank User can updated CBS Transaction to disburse and invoice financing status updates to disbursed
  // TEST: Bank User can reject a disbursal/mark as permanently failed

  public function test_example()
  {
    $response = $this->get('/');

    $response->assertStatus(200);
  }
}
