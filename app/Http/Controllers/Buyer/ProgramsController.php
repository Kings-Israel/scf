<?php

namespace App\Http\Controllers\Buyer;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProgramsController extends Controller
{
  public function programs()
  {
    return view('content.factoring.program.programs');
  }
}
