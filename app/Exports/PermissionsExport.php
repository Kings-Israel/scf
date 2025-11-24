<?php

namespace App\Exports;

use App\Models\RoleType;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PermissionsExport implements FromView, ShouldAutoSize
{
  public $permissions;

  public function __construct(array $permissions)
  {
    $this->permissions = $permissions;
  }

  public function view(): View
  {
    return view('content.bank.exports.permissions', ['permissions' => $this->permissions]);
  }

  // public function array(): array
  // {
  //   $data = array();
  //   foreach ($this->permissions as $permission) {
  //     $section = [];

  //     $data[$permission['name']] = [];

  //     foreach ($permission['groups'] as $group) {
  //       foreach ($group['access_groups'] as $access_group) {
  //         array_push($data[$permission['name']], $access_group['name']);
  //       }
  //     }
  //     array_push($data, $section);
  //   }

  //   return $data;
  // }
}
