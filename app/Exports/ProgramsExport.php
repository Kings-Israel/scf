<?php

namespace App\Exports;

use App\Models\Bank;
use App\Models\Program;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Str;

class ProgramsExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize
{
  public function __construct(
    public Bank $bank,
    public $name = null,
    public $anchor = null,
    public $type = null,
    public $status = null
  ) {
  }

  public function map($row): array
  {
    return [
      $row->name,
      $row->anchor->name,
      $row->programType->name,
      $row->programCode ? $row->programCode->abbrev : 'DF',
      Str::title($row->status),
      number_format($row->program_limit, 2),
      number_format($row->utilized_amount, 2),
      number_format($row->pipeline_amount, 2),
      number_format($row->program_limit - $row->utilized_amount - $row->pipeline_amount, 2),
      User::find($row->created_by)?->name,
      $row->created_at->format('d/m/Y'),
    ];
  }

  public function headings(): array
  {
    return [
      'Name',
      'Company Name',
      'Product Type',
      'Product Code',
      'Status',
      'Total Program Limit',
      'Utilized Amount',
      'Pipeline Amount',
      'Available Limit',
      'Created By',
      'Created At',
    ];
  }

  /**
   * @return \Illuminate\Support\Collection
   */
  public function collection()
  {
    return Program::where('bank_id', $this->bank->id)
      ->withCount('proposedUpdate')
      ->when($this->name && $this->name != '' && $this->name != null, function ($query) {
        $query->where('name', 'LIKE', '%' . $this->name . '%');
      })
      ->when($this->anchor && $this->anchor != '' && $this->anchor != null, function ($query) {
        $query->whereHas('anchor', function ($query) {
          $query->where('companies.name', 'LIKE', '%' . $this->anchor . '%');
        });
      })
      ->when($this->type && $this->type != '', function ($query) {
        switch ($this->type) {
          case 'vendor_financing_receivable':
            $query->whereHas('programCode', function ($query) {
              $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
            });
            break;
          case 'factoring_with_recourse':
            $query->whereHas('programCode', function ($query) {
              $query->where('name', Program::FACTORING_WITH_RECOURSE);
            });
            break;
          case 'factoring_without_recourse':
            $query->whereHas('programCode', function ($query) {
              $query->where('name', Program::FACTORING_WITHOUT_RECOURSE);
            });
            break;
          case 'dealer_financing':
            $query->whereHas('programType', function ($query) {
              $query->where('name', Program::DEALER_FINANCING);
            });
            break;
          default:
            $query->whereHas('programCode', function ($query) {
              $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
            });
            break;
        }
      })
      ->when($this->status && $this->status != '' && $this->status != null, function ($query) {
        $query->where('account_status', $this->status);
      })
      ->orderBy('proposed_update_count', 'DESC')
      ->orderBy('name', 'ASC')
      ->get();
  }
}
