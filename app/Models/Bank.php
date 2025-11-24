<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notifiable;

class Bank extends Model
{
  use HasFactory, Notifiable;

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  /**
   * The accessors to append to the model's array form.
   *
   * @var array
   */
  protected $appends = ['default_currency', 'product_types'];

  /**
   * Get the has program pending configurations
   *
   * @param  string  $value
   * @return int
   */
  public function getPendingProgramConfigurationsCountAttribute()
  {
    $program_changes_count = Program::where('bank_id', $this->id)
      ->where(function ($query) {
        $query->orWhere('status', 'pending')->orWhereHas('proposedUpdate');
      })
      ->where('is_published', true)
      ->count();
    $programs_ids = Program::where('bank_id', $this->id)->pluck('id');
    $mapping_changes_count = ProgramMappingChange::whereIn('program_id', $programs_ids)->count();
    $new_mappings_count = ProgramVendorConfiguration::whereIn('program_id', $programs_ids)
      ->where('status', 'inactive')
      ->where('is_approved', false)
      ->where('deleted_at', null)
      ->count();

    return $program_changes_count + $mapping_changes_count + $new_mappings_count;
  }

  /**
   * Get the default currency
   *
   * @return string
   */
  public function getDefaultCurrencyAttribute()
  {
    $currency = '';
    if ($this->adminConfiguration) {
      if ($this->adminConfiguration->defaultCurrency) {
        $currency = Currency::find($this->adminConfiguration->defaultCurrency)->code;
      } else {
        $currency = 'KES';
      }
    } else {
      $currency = 'KES';
    }
    return $currency;
  }

  /**
   * The users that belong to the Bank
   */
  public function users(): BelongsToMany
  {
    return $this->belongsToMany(User::class, 'bank_users', 'bank_id', 'user_id');
  }

  /**
   * Get all of the convertionRates for the Bank
   */
  public function conversionRates(): HasMany
  {
    return $this->hasMany(BankConvertionRate::class);
  }

  /**
   * Get all of the companies for the Bank
   */
  public function companies(): HasMany
  {
    return $this->hasMany(Company::class);
  }

  /**
   * Get all of the programs for the Bank
   */
  public function programs(): HasMany
  {
    return $this->hasMany(Program::class);
  }

  /**
   * Get all of the requiredDocuments for the Bank
   */
  public function requiredDocuments(): HasMany
  {
    return $this->hasMany(BankDocument::class);
  }

  /**
   * Get all of the paymentAccounts for the Bank
   */
  public function paymentAccounts(): HasMany
  {
    return $this->hasMany(BankPaymentAccount::class);
  }

  /**
   * Get all of the withholdingTaxConfigurations for the Bank
   */
  public function withholdingTaxConfigurations(): HasMany
  {
    return $this->hasMany(BankWithholdingTaxConfiguration::class);
  }

  /**
   * Get all of the productsConfigurations for the Bank
   */
  public function productsConfigurations(): HasMany
  {
    return $this->hasMany(BankProductsConfiguration::class);
  }

  /**
   * Get all of the repaymentPriorities for the Bank
   */
  public function repaymentPriorities(): HasMany
  {
    return $this->hasMany(BankProductRepaymentPriority::class);
  }

  /**
   * Get all of the generalConfigurations for the Bank
   */
  public function generalConfigurations(): HasMany
  {
    return $this->hasMany(BankGeneralProductConfiguration::class);
  }

  /**
   * Get the adminConfiguration associated with the Bank
   */
  public function adminConfiguration(): HasOne
  {
    return $this->hasOne(AdminBankConfiguration::class);
  }

  /**
   * Get all of the baseRates for the Bank
   */
  public function baseRates(): HasMany
  {
    return $this->hasMany(BankBaseRate::class);
  }

  public function configurationChanges(): MorphMany
  {
    return $this->morphMany(ProposedConfigurationChange::class, 'modeable');
  }

  /**
   * Get all of the holidays for the Bank
   */
  public function holidays(): HasMany
  {
    return $this->hasMany(BankHoliday::class);
  }

  /**
   * Get all of the rejectionReasons for the Bank
   */
  public function rejectionReasons(): HasMany
  {
    return $this->hasMany(BankRejectionReason::class);
  }

  /**
   * Get the company user changes count
   *
   * @return int
   */
  public function getCompanyUserChangesCountAttribute()
  {
    $companies = $this->companies->pluck('id');
    $value = CompanyUser::whereIn('company_id', $companies)
      ->whereHas('user', function ($query) {
        $query->whereHas('changes');
      })
      ->count();
    $value += ProposedConfigurationChange::where('modeable_type', Company::class)
      ->whereIn('modeable_id', $companies)
      ->count();

    return $value;
  }

  public function getProductTypesAttribute(): array
  {
    $product_type = $this->adminConfiguration?->product_type;

    if (!$product_type) {
      return ['vendor_financing', 'dealer_financing', 'factoring'];
    }

    return explode(',', $product_type);
  }

  public function getVendors(): Collection
  {
    $vendor_role = ProgramRole::where('name', 'vendor')->first();

    $program_ids = Program::whereHas('programCode', function ($query) {
      $query->whereIn('name', ['Vendor Financing Receivable']);
    })
      ->where('bank_id', $this->id)
      ->pluck('id');

    $vendors_ids = ProgramCompanyRole::where('role_id', $vendor_role->id)
      ->whereIn('program_id', $program_ids)
      ->get()
      ->pluck('company_id');

    return Company::whereIn('id', $vendors_ids)
      ->where('bank_id', $this->id)
      ->get();
  }

  public function getBuyers(): Collection
  {
    $vendor_role = ProgramRole::where('name', 'buyer')->first();

    $program_ids = Program::whereHas('programCode', function ($query) {
      $query->whereIn('name', ['Factoring With Recourse', 'Factoring Without Recourse']);
    })
      ->where('bank_id', $this->id)
      ->pluck('id');

    $vendors_ids = ProgramCompanyRole::where('role_id', $vendor_role->id)
      ->whereIn('program_id', [$program_ids])
      ->get()
      ->pluck('company_id');

    return Company::whereIn('id', $vendors_ids)
      ->where('bank_id', $this->id)
      ->get();
  }

  public function getDealers(): Collection
  {
    $vendor_role = ProgramRole::where('name', 'dealer')->first();

    $program_ids = Program::whereHas('programType', function ($query) {
      $query->where('name', 'Dealer Financing');
    })
      ->where('bank_id', $this->id)
      ->pluck('id');

    $vendors_ids = ProgramCompanyRole::where('role_id', $vendor_role->id)
      ->whereIn('program_id', $program_ids)
      ->get()
      ->pluck('company_id');

    return Company::whereIn('id', $vendors_ids)
      ->where('bank_id', $this->id)
      ->get();
  }
}
