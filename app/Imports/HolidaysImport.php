<?php

namespace App\Imports;

use App\Helpers\Helpers;
use Carbon\Carbon;
use App\Models\Bank;
use App\Models\BankHoliday;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeImport;
use App\Models\ProposedConfigurationChange;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class HolidaysImport implements ToCollection, WithHeadingRow, SkipsEmptyRows, SkipsOnFailure, WithEvents, WithMapping
{
  use Importable, SkipsFailures;

  public $data = 0;
  public $total_rows = 0;

  public function __construct(public Bank $bank, public int $bank_users_count)
  {
  }

  public function registerEvents(): array
  {
    return [
      BeforeImport::class => function (BeforeImport $event) {
        $this->total_rows = $event->getReader()->getTotalRows();
      },
    ];
  }

  public function map($row): array
  {
    return [
      'name' => $row['occasion'],
      'date' => Helpers::importParseDate($row['date_ddmmyyyy']),
    ];
  }

  /**
   * @param Collection $collection
   */
  public function collection(Collection $collection)
  {
    foreach ($collection as $key => $holiday) {
      $exists = BankHoliday::where('bank_id', $this->bank->id)
        ->whereDate('date', $holiday['date'])
        ->first();
      if ($holiday['name'] && $holiday['date'] && !$exists) {
        $bank_holiday = BankHoliday::create([
          'bank_id' => $this->bank->id,
          'name' => $holiday['name'],
          'date' => $holiday['date'],
          'status' => $this->bank_users_count > 0 ? 'inactive' : 'active',
        ]);

        if ($this->bank_users_count > 0) {
          ProposedConfigurationChange::create([
            'user_id' => auth()->id(),
            'modeable_type' => Bank::class,
            'modeable_id' => $this->bank->id,
            'configurable_type' => BankHoliday::class,
            'configurable_id' => $bank_holiday->id,
            'old_value' => 'inactive',
            'new_value' => 'active',
            'field' => 'status',
            'description' => 'Added new holiday ' . $bank_holiday->name . ' on date ' . Carbon::parse($bank_holiday->date)->format($this->bank->adminConfiguration?->date_format),
          ]);
        }

        $this->data++;
      }
    }
  }
}
