<?php

namespace App\Helpers;

use App\Models\Bank;
use App\Models\PaymentRequest;
use Carbon\Carbon;
use Config;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class Helpers
{
  public static function appClasses()
  {
    $data = config('custom.custom');

    // default data array
    $DefaultData = [
      'myLayout' => 'vertical',
      'myTheme' => 'theme-default',
      'myStyle' => 'light',
      'myRTLSupport' => true,
      'myRTLMode' => true,
      'hasCustomizer' => true,
      'showDropdownOnHover' => true,
      'displayCustomizer' => true,
      'menuFixed' => true,
      'menuCollapsed' => false,
      'navbarFixed' => true,
      'footerFixed' => false,
      'menuFlipped' => false,
      // 'menuOffcanvas' => false,
      'customizerControls' => [
        'rtl',
        'style',
        'layoutType',
        'showDropdownOnHover',
        'layoutNavbarFixed',
        'layoutFooterFixed',
        'themes',
      ],
      'defaultLanguage' => 'en',
    ];

    // if any key missing of array from custom.php file it will be merge and set a default value from dataDefault array and store in data variable
    $data = array_merge($DefaultData, $data);

    // All options available in the template
    $allOptions = [
      'myLayout' => ['vertical', 'horizontal', 'blank'],
      'menuCollapsed' => [true, false],
      'hasCustomizer' => [true, false],
      'showDropdownOnHover' => [true, false],
      'displayCustomizer' => [true, false],
      'myStyle' => ['light', 'dark'],
      'myTheme' => ['theme-default', 'theme-bordered', 'theme-semi-dark'],
      'myRTLSupport' => [true, false],
      'myRTLMode' => [true, false],
      'menuFixed' => [true, false],
      'navbarFixed' => [true, false],
      'footerFixed' => [true, false],
      'menuFlipped' => [true, false],
      // 'menuOffcanvas' => [true, false],
      'customizerControls' => [],
      'defaultLanguage' => ['en' => 'en', 'fr' => 'fr', 'de' => 'de', 'pt' => 'pt'],
    ];

    //if myLayout value empty or not match with default options in custom.php config file then set a default value
    foreach ($allOptions as $key => $value) {
      if (array_key_exists($key, $DefaultData)) {
        if (gettype($DefaultData[$key]) === gettype($data[$key])) {
          // data key should be string
          if (is_string($data[$key])) {
            // data key should not be empty
            if (isset($data[$key]) && $data[$key] !== null) {
              // data key should not be exist inside allOptions array's sub array
              if (!array_key_exists($data[$key], $value)) {
                // ensure that passed value should be match with any of allOptions array value
                $result = array_search($data[$key], $value, 'strict');
                if (empty($result) && $result !== 0) {
                  $data[$key] = $DefaultData[$key];
                }
              }
            } else {
              // if data key not set or
              $data[$key] = $DefaultData[$key];
            }
          }
        } else {
          $data[$key] = $DefaultData[$key];
        }
      }
    }
    //layout classes
    $layoutClasses = [
      'layout' => $data['myLayout'],
      'theme' => $data['myTheme'],
      'style' => $data['myStyle'],
      'rtlSupport' => $data['myRTLSupport'],
      'rtlMode' => $data['myRTLMode'],
      'textDirection' => $data['myRTLMode'],
      'menuCollapsed' => $data['menuCollapsed'],
      'hasCustomizer' => $data['hasCustomizer'],
      'showDropdownOnHover' => $data['showDropdownOnHover'],
      'displayCustomizer' => $data['displayCustomizer'],
      'menuFixed' => $data['menuFixed'],
      'navbarFixed' => $data['navbarFixed'],
      'footerFixed' => $data['footerFixed'],
      'menuFlipped' => $data['menuFlipped'],
      // 'menuOffcanvas' => $data['menuOffcanvas'],
      'customizerControls' => $data['customizerControls'],
    ];

    // sidebar Collapsed
    if ($layoutClasses['menuCollapsed'] == true) {
      $layoutClasses['menuCollapsed'] = 'layout-menu-collapsed';
    }

    // Menu Fixed
    if ($layoutClasses['menuFixed'] == true) {
      $layoutClasses['menuFixed'] = 'layout-menu-fixed';
    }

    // Navbar Fixed
    if ($layoutClasses['navbarFixed'] == true) {
      $layoutClasses['navbarFixed'] = 'layout-navbar-fixed';
    }

    // Footer Fixed
    if ($layoutClasses['footerFixed'] == true) {
      $layoutClasses['footerFixed'] = 'layout-footer-fixed';
    }

    // Menu Flipped
    if ($layoutClasses['menuFlipped'] == true) {
      $layoutClasses['menuFlipped'] = 'layout-menu-flipped';
    }

    // Menu Offcanvas
    // if ($layoutClasses['menuOffcanvas'] == true) {
    //   $layoutClasses['menuOffcanvas'] = 'layout-menu-offcanvas';
    // }

    // RTL Supported template
    if ($layoutClasses['rtlSupport'] == true) {
      $layoutClasses['rtlSupport'] = '/rtl';
    }

    // RTL Layout/Mode
    if ($layoutClasses['rtlMode'] == true) {
      $layoutClasses['rtlMode'] = 'rtl';
      $layoutClasses['textDirection'] = 'rtl';
    } else {
      $layoutClasses['rtlMode'] = 'ltr';
      $layoutClasses['textDirection'] = 'ltr';
    }

    // Show DropdownOnHover for Horizontal Menu
    if ($layoutClasses['showDropdownOnHover'] == true) {
      $layoutClasses['showDropdownOnHover'] = 'true';
    } else {
      $layoutClasses['showDropdownOnHover'] = 'false';
    }

    // To hide/show display customizer UI, not js
    if ($layoutClasses['displayCustomizer'] == true) {
      $layoutClasses['displayCustomizer'] = 'true';
    } else {
      $layoutClasses['displayCustomizer'] = 'false';
    }

    return $layoutClasses;
  }

  public static function updatePageConfig($pageConfigs)
  {
    $demo = 'custom';
    if (isset($pageConfigs)) {
      if (count($pageConfigs) > 0) {
        foreach ($pageConfigs as $config => $val) {
          Config::set('custom.' . $demo . '.' . $config, $val);
        }
      }
    }
  }

  public static function paginate($items, $perPage = 10, $page = null)
  {
    $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
    $total = count($items);
    $currentpage = $page;
    $offset = $currentpage * $perPage - $perPage;
    $itemstoshow = array_slice($items, $offset, $perPage);

    return new LengthAwarePaginator($itemstoshow, $total, $perPage, $page, ['path' => Request::fullUrl()]);
  }

  public static function generateUniqueNumber($model, $column, $min = 10000, $max = 99999)
  {
    $number = mt_rand($min, $max);

    if (self::numberExists($model, $column, $number)) {
      return self::generateUniqueNumber($model, $column);
    }

    return $number;
  }

  private static function numberExists($model, $column, $number)
  {
    return $model::where($column, $number)->exists();
  }

  public static function isExpiredPastTwoMinutes($expired_at, $expiry_period = 2)
  {
    // Create Carbon instances
    $now = Carbon::now();
    $expiredAt = Carbon::parse($expired_at);

    // Check if expired_at is more than two minutes ago
    return $expiredAt->lt($now->subMinutes($expiry_period));
  }

  public static function statusCss($status)
  {
    switch ($status) {
      case 'approved':
        $styleClass = 'success';
        break;
      case 'rejected':
        $styleClass = 'danger';
        break;
      case 'denied':
        $styleClass = 'danger';
        break;
      case 'pending':
        $styleClass = 'info';
        break;
      default:
        $styleClass = 'primary';
        break;
    }

    return $styleClass;
  }

  public static function importParseDate($value, $end_format = 'Y-m-d')
  {
    // Handle Excel's numeric serialized date
    if (is_numeric($value)) {
      try {
        return Date::excelToDateTimeObject($value)->format($end_format);
      } catch (\Exception $e) {
        // Fall through
        info($e);
      }
    }

    // Try common date formats
    $formats = [
      'Y-m-d',
      'd/m/Y',
      'm/d/Y',
      'd-m-Y',
      'm-d-Y',
      'd M Y',
      'M d, Y',
      'd.m.Y',
      'Y/m/d',

      'Y-m-d H:i:s',
      'd/m/Y H:i:s',
      'm/d/Y H:i:s',
      'd-m-Y H:i:s',
      'm-d-Y H:i:s',
      'd M Y H:i:s',
      'M d, Y H:i:s',
      'd.m.Y H:i:s',
      'Y/m/d H:i:s',

      'Y-m-d H:i',
      'd/m/Y H:i',
      'm/d/Y H:i',
      'd-m-Y H:i',
      'm-d-Y H:i',
      'd M Y H:i',
      'M d, Y H:i',
      'd.m.Y H:i',
      'Y/m/d H:i',

      'Y-m-d H:i A',
      'd/m/Y H:i A',
      'm/d/Y H:i A',
      'd-m-Y H:i A',
      'm-d-Y H:i A',
      'd M Y H:i A',
      'M d, Y H:i A',
      'd.m.Y H:i A',
      'Y/m/d H:i A',
    ];

    foreach ($formats as $format) {
      try {
        return Carbon::createFromFormat($format, $value)->format($end_format);
      } catch (\Exception $e) {
        continue;
      }
    }

    // Fallback: Try Carbon’s flexible parser
    try {
      return Carbon::parse($value)->format($end_format);
    } catch (\Exception $e) {
      info($e);
      return null; // Or handle invalid date
    }
  }

  public static function generateSequentialReferenceNumber($bank, $product_type, $product_code = [])
  {
    $latest_payment_reference_number = PaymentRequest::whereHas('invoice', function ($query) use (
      $bank,
      $product_type,
      $product_code
    ) {
      $query->whereHas('program', function ($query) use ($bank, $product_type, $product_code) {
        $query
          ->where('bank_id', $bank)
          ->whereHas('programType', function ($query) use ($product_type) {
            $query->where('name', $product_type);
          })
          ->when($product_code && count($product_code) > 0, function ($query) use ($product_code) {
            $query->whereHas('programCode', function ($query) use ($product_code) {
              $query->whereIn('name', $product_code);
            });
          });
      });
    })
      ->orderBy('id', 'DESC')
      ->first()
      ->reference_number;

    if (!$latest_payment_reference_number) {
      return $latest_payment_reference_number = 0;
    }

    $new_reference_number = (int) explode('000', $latest_payment_reference_number)[1] + 1;

    return $new_reference_number;
  }
}
