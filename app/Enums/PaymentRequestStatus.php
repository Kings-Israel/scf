<?php

namespace App\Enums;

enum PaymentRequestStatus: string
{
  case CREATED = 'created';
  case PENDING_MAKER = 'pending_maker';
  case PENDING_CHECKER = 'pending_checker';
  case APPROVED = 'approved';
  case REJECTED = 'rejected';
}
