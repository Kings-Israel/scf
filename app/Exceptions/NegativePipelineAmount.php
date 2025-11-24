<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class NegativePipelineAmount extends Exception
{
  public function __construct(string $message = "Pipeline amount cannot go negative.", int $code = 0, ?Throwable $previous = null)
  {
      parent::__construct($message, $code, $previous);
  }

  public function render($request)
  {
      if ($request->wantsJson()) {
          return response()->json(['message' => $this->getMessage()], 400);
      }
      return back()->with('error', $this->getMessage());
  }
}
