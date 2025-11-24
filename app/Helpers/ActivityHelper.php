<?php

namespace App\Helpers;

use App\Models\Activity;

class ActivityHelper
{
  const MODULE_CRM = 'CRM';
  const MODULE_ADMIN = 'Admin';

  public static function logActivity(array $data, string $module)
  {
    Activity::create([
      'module' => $module,
      'subject_type' => $data['subject_type'] ?? 'None',
      'subject_id' => $data['subject_id'] ?? null,
      'stage' => $data['stage'] ?? null,
      'section' => $data['section'] ?? null,
      'pipeline_id' => $data['pipeline_id'] ?? null,
      'user_id' => $data['user_id'],
      'description' => $data['description'],
      'properties' => $data['properties'] ?? [],
      'device_info' => json_encode($data['device_info']) ?? '{}',
      'ip_address' => $data['ip_address'] ?? null,
      'url' => $data['url'] ?? null,
      'created_at' => $data['created_at'] ?? now(),
      'updated_at' => $data['updated_at'] ?? now(),
    ]);
  }

  public static function logCrmActivity(array $data)
  {
    self::logActivity($data, self::MODULE_CRM);
  }

  public static function logAdminActivity(array $data)
  {
    self::logActivity($data, self::MODULE_ADMIN);
  }
}
