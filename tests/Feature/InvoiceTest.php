<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
  // TEST: Vendor can see invoice creation page
  // TEST: Vendor can create invoice
  // TEST: Anchor approve invoice
  // TEST: Anchor can reject invoice
  // TEST: Anchor can update invoice due date
  // TEST: Vendor can request for financing
  public function test_example()
  {
    $response = $this->get('/');

    $response->assertStatus(200);
  }
}
