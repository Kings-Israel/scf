<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MenuServiceProvider extends ServiceProvider
{
  /**
   * Register services.
   *
   * @return void
   */
  public function register()
  {
    //
  }

  /**
   * Bootstrap services.
   *
   * @return void
   */
  public function boot()
  {
    $verticalMenuJson = file_get_contents(base_path('resources/menu/verticalMenu.json'));
    $verticalMenuData = json_decode($verticalMenuJson);
    $horizontalMenuJson = file_get_contents(base_path('resources/menu/horizontalMenu.json'));
    $horizontalMenuData = json_decode($horizontalMenuJson);
    $anchorHorizontalMenuJson = file_get_contents(base_path('resources/menu/anchorHorizontalMenu.json'));
    $anchorHorizontalMenuData = json_decode($anchorHorizontalMenuJson);
    $anchorFactoringHorizontalMenuJson = file_get_contents(
      base_path('resources/menu/anchorFactoringHorizontalMenu.json')
    );
    $anchorFactoringHorizontalMenuData = json_decode($anchorFactoringHorizontalMenuJson);
    $anchorDealerHorizontalMenuJson = file_get_contents(base_path('resources/menu/anchorDealerHorizontalMenu.json'));
    $anchorDealerHorizontalMenuData = json_decode($anchorDealerHorizontalMenuJson);
    $vendorHorizontalMenuJson = file_get_contents(base_path('resources/menu/vendorHorizontalMenu.json'));
    $vendorHorizontalMenuData = json_decode($vendorHorizontalMenuJson);
    $buyerHorizontalMenuJson = file_get_contents(base_path('resources/menu/buyerHorizontalMenu.json'));
    $buyerHorizontalMenuData = json_decode($buyerHorizontalMenuJson);
    $buyerFactoringHorizontalMenuJson = file_get_contents(
      base_path('resources/menu/buyerFactoringHorizontalMenu.json')
    );
    $buyerFactoringHorizontalMenuData = json_decode($buyerFactoringHorizontalMenuJson);

    // Share all menuData to all the views
    \View::share('menuData', [
      $verticalMenuData,
      $horizontalMenuData,
      $anchorHorizontalMenuData,
      $anchorFactoringHorizontalMenuData,
      $vendorHorizontalMenuData,
      $buyerHorizontalMenuData,
      $buyerFactoringHorizontalMenuData,
      $anchorDealerHorizontalMenuData,
    ]);
  }
}
