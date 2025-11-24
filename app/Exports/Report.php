<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class Report implements FromCollection, WithHeadings, ShouldAutoSize
{
  public $headers = [];
  public $data = [];

  public function __construct(array $headers, array $data)
  {
    $this->headers = $headers;
    $this->data = $data;
  }

  public function headings(): array
  {
    return $this->headers;
  }

  /**
   * @return \Illuminate\Support\Collection
   */
  public function collection()
  {
    return collect($this->data);
  }
}
