<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
  use HasApiTokens, HasFactory, Notifiable, HasRoles;

  protected $guarded = [];

  /**
   * The accessors to append to the model's array form.
   *
   * @var array
   */
  protected $appends = ['can_activate', 'can_edit', 'can_approve_changes'];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var array<int, string>
   */
  protected $hidden = ['password', 'remember_token'];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'email_verified_at' => 'datetime',
    'is_active' => 'bool',
    'applicable_product' => 'array',
  ];

  /**
   * Get the can active users
   *
   * @param  string  $value
   * @return string
   */
  public function getCanActivateAttribute()
  {
    if (auth()->check()) {
      return auth()
        ->user()
        ->hasPermissionTo('Activate/Deactivate Users');
    }

    return false;
  }

  /**
   * Get the can edit attribute
   *
   * @param  string  $value
   * @return string
   */
  public function getCanEditAttribute()
  {
    if (auth()->check()) {
      return auth()
        ->user()
        ->hasPermissionTo('Add/Edit Users');
    }

    return false;
  }

  /**
   * The banks that belong to the User
   */
  public function banks(): BelongsToMany
  {
    return $this->belongsToMany(Bank::class, 'bank_users', 'user_id', 'bank_id')->withPivot('active');
  }

  public function bankUser(): HasMany
  {
    return $this->hasMany(BankUser::class);
  }

  /**
   * The companies that belong to the User
   */
  public function companies(): BelongsToMany
  {
    return $this->belongsToMany(Company::class, 'company_users', 'user_id', 'company_id')
      ->where('active', true)
      ->withPivot('active');
  }

  /**
   * The activeCompanies that belong to the User
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
   */
  public function mappedCompanies(): BelongsToMany
  {
    return $this->belongsToMany(Company::class, 'company_users', 'user_id', 'company_id')->withPivot('active');
  }

  /**
   * The authorizationGroups that belong to the User
   */
  public function authorizationGroups(): BelongsToMany
  {
    return $this->belongsToMany(
      CompanyAuthorizationGroup::class,
      'company_user_authorization_groups',
      'user_id',
      'group_id'
    );
  }

  /**
   * Get all of the purchaseOrderApprovals for the User
   */
  public function purchaseOrderApprovals(): HasMany
  {
    return $this->hasMany(PurchaseOrderApproval::class);
  }

  public function anchorCompanies()
  {
    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $anchors_ids = ProgramCompanyRole::where('role_id', $anchor_role->id)
      ->get()
      ->pluck('company_id');

    $companies = CompanyUser::where('user_id', $this->id)
      ->get()
      ->pluck('company_id');

    return Company::whereIn('id', $anchors_ids)
      ->whereIn('id', $companies)
      ->get();
  }

  /**
   * Get the activeCompany associated with the User
   */
  public function activeAnchorCompany(): HasOne
  {
    return $this->hasOne(UserCurrentCompany::class)->where('platform', 'anchor');
  }

  /**
   * Get the activeFactoringCompany associated with the User
   */
  public function activeFactoringCompany(): HasOne
  {
    return $this->hasOne(UserCurrentCompany::class)->where('platform', 'anchor factoring');
  }

  /**
   * Get the activeAnchorDealerCompany associated with the User
   */
  public function activeAnchorDealerCompany(): HasOne
  {
    return $this->hasOne(UserCurrentCompany::class)->where('platform', 'anchor dealer');
  }

  /**
   * Get the activeCompany associated with the User
   */
  public function activeVendorCompany(): HasOne
  {
    return $this->hasOne(UserCurrentCompany::class)->where('platform', 'vendor');
  }

  /**
   * Get the activeCompany associated with the User
   */
  public function activeBuyerFactoringCompany(): HasOne
  {
    return $this->hasOne(UserCurrentCompany::class)->where('platform', 'buyer');
  }

  /**
   * Get the activeDealerCompany associated with the User
   */
  public function activeBuyerCompany(): HasOne
  {
    return $this->hasOne(UserCurrentCompany::class)->where('platform', 'buyer dealer');
  }

  /**
   * Get the activeBank associated with the User
   */
  public function activeBank(): HasOne
  {
    return $this->hasOne(UserCurrentBank::class);
  }

  public function changes(): HasOne
  {
    return $this->hasOne(UserChange::class);
  }

  public function getCanApproveChangesAttribute()
  {
    if (!auth()->check()) {
      return false;
    }

    if ($this->changes()->count() > 0) {
      if (auth()->id() == $this->changes()->first()->created_by) {
        return false;
      }

      return true;
    }

    return false;
  }

  /**
   * Get has any factoring permissions
   *
   * @param  string  $value
   * @return string
   */
  public function getHasAnyFactoringPermissionsAttribute()
  {
    $factoring_permissions = [
      'View Seller Purchase Orders',
      'View Seller Invoices',
      'View Seller Payments',
      'View Seller Cash Planner',
      'View Seller Finance Requests',
      'View Seller Funding Utilization Limits',
      'View Seller Financing Report',
      'View Seller IF - Payment Details Report',
      'View All Seller Invoices Report',
      'View Seller Configurations',
      'View Seller Funding Limit Utilization Report',
    ];

    return $this->hasAnyPermission($factoring_permissions);
  }

  /**
   * Get has any vendor financing permissions
   *
   * @param  string  $value
   * @return string
   */
  public function getHasAnyVendorFinancingPermissionsAttribute()
  {
    $permissions = [
      'View POs',
      'View Invoices',
      'View Configurations',
      'View Payments',
      'View PI Listing Report',
      'View Financing Usage',
      'View Limit Utilization Report',
      'View All Invoices Report',
    ];

    return $this->hasAnyPermission($permissions);
  }

  /**
   * Get has any dealer financing permissions
   *
   * @param  string  $value
   * @return string
   */
  public function getHasAnyDealerFinancingPermissionsAttribute()
  {
    $permissions = ['View Dealer POs', 'View Dealer Invoices', 'View DPD Invoices'];

    return $this->hasAnyPermission($permissions);
  }
}
