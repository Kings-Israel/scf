<?php

namespace App\Models;

use App\Http\Resources\InvoiceResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Permission\Traits\HasRoles;
use Oddvalue\LaravelDrafts\Concerns\HasDrafts;
use Illuminate\Notifications\Notifiable;

class Company extends Model
{
  use HasFactory, HasRoles, Notifiable, HasDrafts;

  protected $guarded = [];

  /**
   * The accessors to append to the model's array form.
   *
   * @var array
   */
  protected $appends = ['can_view', 'can_edit', 'can_activate', 'can_approve', 'can_block', 'default_currency'];

  /**
   * The attributes that should be cast to native types.
   *
   * @var array
   */
  protected $casts = [
    'is_blocked' => 'bool',
    'is_published' => 'bool',
    'is_current' => 'bool',
  ];

  protected static function booted()
  {
    static::created(function ($model) {
      $model->invoiceSetting()->create();
      $model->purchaseOrderSetting()->create();
    });
  }

  /**
   * Get the logo
   *
   * @param  string  $value
   * @return string
   */
  public function getLogoAttribute($value)
  {
    if ($value) {
      return config('app.url') . '/storage/company/logo/' . $value;
    }

    return null;
  }

  /**
   * Get the bank that owns the Company
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function bank(): BelongsTo
  {
    return $this->belongsTo(Bank::class);
  }

  /**
   * The users that belong to the Company
   */
  public function users(): BelongsToMany
  {
    return $this->belongsToMany(User::class, 'company_users', 'company_id', 'user_id');
  }

  /**
   * Get all of the documents for the Company
   */
  public function documents(): HasMany
  {
    return $this->hasMany(CompanyDocument::class);
  }

  /**
   * Get all of the requestedDocuments for the Company
   */
  public function requestedDocuments(): HasMany
  {
    return $this->hasMany(RequestDocument::class);
  }

  /**
   * Get all of the purchaseOrders for the Company
   */
  public function purchaseOrders(): HasMany
  {
    return $this->hasMany(PurchaseOrder::class);
  }

  /**
   * Get all of the invoices for the Company
   */
  public function invoices(): HasMany
  {
    return $this->hasMany(Invoice::class);
  }

  /**
   * Get all of the programDiscountDetails for the Program
   */
  public function programDiscountDetails(): HasMany
  {
    return $this->hasMany(ProgramVendorDiscount::class);
  }

  /**
   * Get all of the programFeeDetails for the Program
   */
  public function programFeeDetails(): HasMany
  {
    return $this->hasMany(ProgramVendorFee::class);
  }

  /**
   * Get all of the programConfigurations for the Program
   */
  public function programConfigurations(): HasMany
  {
    return $this->hasMany(ProgramVendorConfiguration::class);
  }

  /**
   * Get all of the programBankDetails for the Program
   */
  public function programBankDetails(): HasMany
  {
    return $this->hasMany(ProgramVendorBankDetail::class);
  }

  /**
   * Get all of the programContactDetails for the Program
   */
  public function programContactDetails(): HasMany
  {
    return $this->hasMany(ProgramVendorContactDetail::class);
  }

  /**
   * Get all of the roles for the Company
   */
  public function roles(): HasManyThrough
  {
    return $this->hasManyThrough(ProgramRole::class, ProgramCompanyRole::class, 'company_id', 'id', 'id', 'role_id');
  }

  /**
   * The programs that belong to the Company
   */
  public function programs(): BelongsToMany
  {
    return $this->belongsToMany(Program::class, 'program_company_roles', 'company_id', 'program_id');
  }

  /**
   * Get the pipeline associated with the Company
   */
  public function pipeline(): HasOne
  {
    return $this->hasOne(Pipeline::class, 'id', 'pipeline_id');
  }

  /**
   * Get the invoiceSetting associated with the Company
   */
  public function invoiceSetting(): HasOne
  {
    return $this->hasOne(CompanyInvoiceSetting::class);
  }

  /**
   * Get the purchaseOrderSetting associated with the Company
   */
  public function purchaseOrderSetting(): HasOne
  {
    return $this->hasOne(CompanyPurchaseOrderSetting::class);
  }

  /**
   * Get the purchaseOrderSetting associated with the Company
   */
  public function proposedUpdate(): HasOne
  {
    return $this->hasOne(CompanyChange::class);
  }

  /**
   * Get all of the relationshipManagers for the Company
   */
  public function relationshipManagers(): HasMany
  {
    return $this->hasMany(CompanyRelationshipManager::class);
  }

  /**
   * Get all of the taxes for the Company
   */
  public function taxes(): HasMany
  {
    return $this->hasMany(CompanyTax::class);
  }

  /**
   * Get all of the bankDetails for the Company
   */
  public function bankDetails(): HasMany
  {
    return $this->hasMany(CompanyBank::class);
  }

  /**
   * Get the anchorConfigurationChange associated with the Company
   */
  public function anchorConfigurationChange(): HasOne
  {
    return $this->hasOne(AnchorConfigurationChange::class);
  }

  /**
   * Get the utilized amount
   *
   * @param  string  $value
   * @return string
   */
  public function getUtilizedAmountAttribute($value)
  {
    if ($value < 0) {
      return 0;
    }

    return $value;
  }

  /**
   * Get the pipeline amount
   *
   * @param  string  $value
   * @return string
   */
  public function getPipelineAmountAttribute($value)
  {
    if ($value < 0) {
      return 0;
    }

    return $value;
  }

  /**
   * Scope a query to only include companies with anchor role
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeAnchor($query)
  {
    return $query
      ->where('approval_status', 'approved')
      ->where('status', 'active')
      ->whereHas('roles', function ($query) {
        $query->where('name', 'anchor');
      })
      ->whereHas('programs', function ($query) {
        $query->whereHas('programCode', function ($query) {
          $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
        });
      });
  }

  /**
   * Scope a query to only include companies in factoring programs
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeAnchorFactoring($query)
  {
    return $query
      ->where('approval_status', 'approved')
      ->where('status', 'active')
      ->whereHas('roles', function ($query) {
        $query->where('name', 'anchor');
      })
      ->whereHas('programs', function ($query) {
        $query->whereHas('programCode', function ($query) {
          $query->where('name', Program::FACTORING_WITH_RECOURSE)->orWhere('name', Program::FACTORING_WITHOUT_RECOURSE);
        });
      });
  }

  /**
   * Scope a query to only include companies with anchor role
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeVendor($query)
  {
    return $query
      ->where('approval_status', 'approved')
      ->where('status', 'active')
      ->whereHas('roles', function ($query) {
        $query->where('name', 'vendor');
      })
      ->whereHas('programs', function ($query) {
        $query->whereHas('programCode', function ($query) {
          $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
        });
      });
  }

  /**
   * Scope a query to only include companies in dealer financing programs
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeDealerFinancing($query)
  {
    return $query
      ->where('approval_status', 'approved')
      ->where('status', 'active')
      ->whereHas('roles', function ($query) {
        $query->where('name', 'anchor');
      })
      ->whereHas('programs', function ($query) {
        $query->whereHas('programType', function ($query) {
          $query->where('name', Program::DEALER_FINANCING);
        });
      });
  }

  /**
   * Scope a query to only include companies in dealer financing programs
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeAnchorDealer($query)
  {
    return $query
      ->where('approval_status', 'approved')
      ->where('status', 'active')
      ->whereHas('roles', function ($query) {
        $query->where('name', 'anchor');
      })
      ->whereHas('programs', function ($query) {
        $query->whereHas('programType', function ($query) {
          $query->where('name', Program::DEALER_FINANCING);
        });
      });
  }

  /**
   * Scope a query to only include companies with buyer role
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeBuyerFactoring($query)
  {
    return $query
      ->where('approval_status', 'approved')
      ->where('status', 'active')
      ->whereHas('roles', function ($query) {
        $query->where('name', 'buyer');
      })
      ->whereHas('programs', function ($query) {
        $query->whereHas('programCode', function ($query) {
          $query->where('name', Program::FACTORING_WITH_RECOURSE)->orWhere('name', Program::FACTORING_WITHOUT_RECOURSE);
        });
      });
  }

  /**
   * Scope a query to only include companies with dealer role
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeBuyer($query)
  {
    return $query
      ->where('approval_status', 'approved')
      ->where('status', 'active')
      ->whereHas('roles', function ($query) {
        $query->where('name', 'dealer');
      })
      ->whereHas('programs', function ($query) {
        $query->whereHas('programType', function ($query) {
          $query->where('name', Program::DEALER_FINANCING);
        });
      });
  }

  /**
   * Get the createdBy that owns the Company
   */
  public function createdBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by', 'id');
  }

  /**
   * Get the can view companies
   *
   * @param  string  $value
   * @return string
   */
  public function getCanViewAttribute()
  {
    if (auth()->check()) {
      return auth()
        ->user()
        ->hasPermissionTo('View Companies');
    }

    return false;
  }

  /**
   * Get the can edit companies
   *
   * @param  string  $value
   * @return string
   */
  public function getCanEditAttribute()
  {
    if (auth()->check()) {
      return auth()
        ->user()
        ->hasPermissionTo('Add/Edit Companies');
    }

    return false;
  }

  /**
   * Get the can activate companies
   *
   * @param  string  $value
   * @return string
   */
  public function getCanActivateAttribute()
  {
    if (auth()->check()) {
      return auth()
        ->user()
        ->hasPermissionTo('Activate/Deactivate Companies');
    }

    return false;
  }

  /**
   * Get the can activate companies
   *
   * @param  string  $value
   * @return string
   */
  public function getCanApproveAttribute()
  {
    if (auth()->check()) {
      return $this->approval_status == 'pending' &&
        auth()
          ->user()
          ->hasPermissionTo('Activate/Deactivate Companies') &&
        $this->created_by != auth()->id();
    }

    return false;
  }

  /**
   * Get the pipeline amount
   *
   * @param  string  $value
   * @return string
   */
  public function getTotalPipelineAmountAttribute()
  {
    $amount = 0;

    $invoices = InvoiceResource::collection(
      Invoice::where('company_id', $this->id)
        ->whereDate('due_date', '>=', now()->format('Y-m-d'))
        ->whereIn('financing_status', ['pending', 'submitted'])
        ->whereHas('paymentRequests', function ($query) {
          $query->whereIn('status', ['created']);
        })
        ->get()
    );

    foreach ($invoices as $invoice) {
      $amount += $invoice->invoice_total_amount;
    }

    return round($amount, 2);
  }

  /**
   * Get the utilized amount
   *
   * @param  string  $value
   * @return string
   */
  public function getTotalUtilizedAmountAttribute()
  {
    $amount = 0;

    $invoices = Invoice::where('company_id', $this->id)
      ->whereHas('paymentRequests', function ($query) {
        $query->whereIn('status', ['approved', 'paid']);
      })
      ->whereIn('financing_status', ['disbursed'])
      ->get();

    foreach ($invoices as $invoice) {
      if ($invoice->program->programType->name == Program::DEALER_FINANCING) {
        if ($invoice->disbursed_amount < $invoice->drawdown_amount) {
          $amount += $invoice->drawdown_amount - $invoice->paid_amount;
        } else {
          $amount += $invoice->disbursed_amount - $invoice->paid_amount;
        }
      } else {
        if ($invoice->disbursed_amount < $invoice->invoice_total_amount) {
          $amount += $invoice->invoice_total_amount - $invoice->paid_amount;
        } else {
          $amount += $invoice->disbursed_amount - $invoice->paid_amount;
        }
      }
    }

    return round($amount, 2);
  }

  /**
   * Get the can edit after rejection
   *
   * @param  string  $value
   * @return string
   */
  public function getCanEditAfterRejectionAttribute()
  {
    if (auth()->check() && $this->approval_status == 'rejected') {
      return auth()
        ->user()
        ->hasPermissionTo('Add/Edit Companies') && $this->created_by == auth()->id();
    }

    return false;
  }

  public function getProgramLimit(Program $program, Company $buyer = null)
  {
    if ($program->programType->name == Program::DEALER_FINANCING) {
      return ProgramVendorConfiguration::select('sanctioned_limit')
        ->where('program_id', $program->id)
        ->where('company_id', $this->id)
        ->first()->sanctioned_limit;
    } else {
      if ($program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        return ProgramVendorConfiguration::select('sanctioned_limit')
          ->where('program_id', $program->id)
          ->where('company_id', $this->id)
          ->first()->sanctioned_limit;
      } else {
        return ProgramVendorConfiguration::select('sanctioned_limit')
          ->where('program_id', $program->id)
          ->where('buyer_id', $buyer->id)
          ->first()->sanctioned_limit;
      }
    }
  }

  public function getUtilizedAmount(Program $program)
  {
    $amount = 0;

    if ($program->programType->name == Program::VENDOR_FINANCING) {
      if ($program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $invoices = Invoice::where('company_id', $this->id)
          ->where('program_id', $program->id)
          ->whereHas('paymentRequests', function ($query) {
            $query->whereIn('status', ['approved', 'paid']);
          })
          ->get();
      } else {
        $invoices = Invoice::where('buyer_id', $this->id)
          ->whereHas('paymentRequests', function ($query) {
            $query->whereIn('status', ['approved', 'paid']);
          })
          ->where('program_id', $program->id)
          ->get();
      }

      foreach ($invoices as $invoice) {
        if ($invoice->disbursed_amount < $invoice->invoice_total_amount) {
          $amount += $invoice->invoice_total_amount - $invoice->paid_amount;
        } else {
          $amount += $invoice->disbursed_amount - $invoice->paid_amount;
        }
      }
    } else {
      $invoices = Invoice::where('company_id', $this->id)
        ->where('program_id', $program->id)
        ->whereHas('paymentRequests', function ($query) {
          $query->whereIn('status', ['approved', 'paid']);
        })
        ->get();

      foreach ($invoices as $key => $invoice) {
        if ($invoice->disbursed_amount < $invoice->drawdown_amount) {
          $amount += $invoice->drawdown_amount - $invoice->paid_amount;
        } else {
          $amount += $invoice->disbursed_amount - $invoice->paid_amount;
        }
      }
    }

    return round($amount, 2);
  }

  public function resolveApprovalStatus(): string
  {
    switch ($this->approval_status) {
      case 'pending':
        return 'bg-label-primary';
        break;
      case 'approved':
        return 'bg-label-success';
        break;
      case 'rejected':
        return 'bg-label-danger';
        break;
      default:
        return 'bg-label-secondary';
        break;
    }
  }

  public function resolveStatus(): string
  {
    switch ($this->approval_status) {
      case 'active':
        return 'bg-label-primary';
        break;
      case 'inactive':
        return 'bg-label-secondary';
        break;
      default:
        return 'bg-label-primary';
        break;
    }
  }

  public function IsMappedToProgram(): bool
  {
    if ($this->roles()->exists()) {
      return true;
    }

    return false;
  }

  public function utilizedAmount(Program $program): float
  {
    $amount = 0;

    if ($program->programType->name == Program::VENDOR_FINANCING) {
      if ($program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $invoices = Invoice::where('company_id', $this->id)
          ->where('program_id', $program->id)
          ->whereIn('financing_status', ['submitted', 'financed', 'disbursed', 'pending'])
          ->whereHas('paymentRequests', function ($query) {
            $query->whereIn('status', ['paid', 'approved']);
          })
          ->get();
      } else {
        $invoices = Invoice::where('buyer_id', $this->id)
          ->where('program_id', $program->id)
          ->whereIn('financing_status', ['submitted', 'financed', 'disbursed', 'pending'])
          ->whereHas('paymentRequests', function ($query) {
            $query->whereIn('status', ['paid', 'approved']);
          })
          ->get();
      }
      foreach ($invoices as $invoice) {
        $amount += $invoice->eligibility
          ? ($invoice->eligibility / 100) * $invoice->invoice_total_amount - $invoice->paid_amount
          : $invoice->invoice_total_amount - $invoice->paid_amount;
      }
    } else {
      $invoices = Invoice::where('company_id', $this->id)
        ->where('program_id', $program->id)
        ->whereIn('financing_status', ['submitted',  'disbursed', 'pending'])
        ->whereHas('paymentRequests', function ($query) {
          $query->whereIn('status', ['paid', 'approved']);
        })
        ->get();

      foreach ($invoices as $key => $invoice) {
        $amount += $invoice->drawdown_amount - $invoice->paid_amount;
      }
    }

    return round($amount, 2);
  }

  public function utilizedPercentage(Program $program): float
  {
    // $invoices = Invoice::where('company_id', $this->id)
    //   ->where('program_id', $program->id)
    //   ->where('financing_status', ['financed', 'pending', 'disbursed', 'submitted'])
    //   ->sum('disbursed_amount');

    $amount_used = $this->utilizedAmount($program) + $this->pipelineAmount($program);
    if (
      $program->vendorConfigurations->where('company_id', $this->id)->first() &&
      $program->vendorConfigurations->where('company_id', $this->id)->first()->sanctioned_limit > 0
    ) {
      return round(
        ($amount_used / $program->vendorConfigurations->where('company_id', $this->id)->first()->sanctioned_limit) *
          100,
        2
      );
    }

    return 0;
  }

  public function pipelineAmount(Program $program): float
  {
    $amount = 0;

    if ($program->programType->name == Program::DEALER_FINANCING) {
      $amount += Invoice::where('company_id', $this->id)
        // ->whereDate('due_date', '>=', now()->format('Y-m-d'))
        ->whereIn('financing_status', ['pending', 'submitted'])
        ->where('program_id', $program->id)
        ->whereHas('paymentRequests', function ($query) {
          $query->whereIn('status', ['created']);
        })
        ->sum('calculated_total_amount');
    } else {
      if ($program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $amount += Invoice::where('company_id', $this->id)
          // ->whereDate('due_date', '>=', now()->format('Y-m-d'))
          ->whereIn('financing_status', ['pending', 'submitted'])
          ->where('program_id', $program->id)
          ->whereHas('paymentRequests', function ($query) {
            $query->whereIn('status', ['created']);
          })
          ->sum('calculated_total_amount');
      } else {
        $amount += Invoice::where('buyer_id', $this->id)
          // ->whereDate('due_date', '>=', now()->format('Y-m-d'))
          ->whereIn('financing_status', ['pending', 'submitted'])
          ->where('program_id', $program->id)
          ->whereHas('paymentRequests', function ($query) {
            $query->whereIn('status', ['created']);
          })
          ->sum('calculated_total_amount');
      }
    }

    return round($amount, 2);
  }

  public function daysPastDue(Program $program)
  {
    $days = 0;

    $invoices = Invoice::where(['program_id' => $program->id, 'company_id' => $this->id])
      ->whereDate('due_date', '<', now()->format('Y-m-d'))
      ->where('financing_status', 'financed')
      ->get();

    foreach ($invoices as $invoice) {
      $days += $invoice->days_past_due;
    }

    return $days;
  }

  public function overdueAmount(Program $program)
  {
    $amount = 0;

    $invoices = Invoice::where(['program_id' => $program->id, 'company_id' => $this->id])
      ->whereDate('due_date', '<', now())
      ->where('financing_status', 'financed')
      ->get();

    foreach ($invoices as $invoice) {
      $amount += $invoice->overdue_amount;
    }

    return round($amount, 2);
  }

  /**
   * Get the default currency from Bank
   * @return string
   */
  public function getDefaultCurrencyAttribute()
  {
    $currency = 'KES';

    if ($this->bank && $this->bank->adminConfiguration) {
      if ($this->bank->adminConfiguration->defaultCurrency) {
        $currency = Currency::find($this->bank->adminConfiguration->defaultCurrency)->code;
      } elseif ($this->bank->adminConfiguration->selectedCurrencyIds) {
        $currency = Currency::find(
          explode(',', str_replace("\"", '', $this->bank->adminConfiguration->selectedCurrencyIds))[0]
        )?->code;
      }
    }

    return $currency;
  }

  public function getTotalProgramLimitAttribute()
  {
    $total_amount = 0;

    $programs = $this->programs;

    if ($programs->count() > 0) {
      $config = ProgramVendorConfiguration::where(function ($query) {
        $query->where('company_id', $this->id)->orWhere('buyer_id', $this->id);
      })->count();

      if ($config > 0) {
        foreach ($programs as $program) {
          if ($program->programType->name == 'Vendor Financing') {
            if ($program->programCode->name == 'Vendor Financing Receivable') {
              $total_amount += ProgramVendorConfiguration::where('company_id', $this->id)
                ->where('program_id', $program->id)
                ->sum('sanctioned_limit');
            } else {
              $total_amount += ProgramVendorConfiguration::where('program_id', $program->id)
                ->where('buyer_id', $this->id)
                ->sum('sanctioned_limit');
            }
          } else {
            $total_amount += ProgramVendorConfiguration::where('company_id', $this->id)
              ->where('program_id', $program->id)
              ->sum('sanctioned_limit');
          }
        }
      }
    }

    return round($total_amount, 2);
  }

  public function getCanBlockAttribute()
  {
    if (auth()->check()) {
      if (
        auth()
          ->user()
          ->hasPermissionTo('Block/Unblock Companies')
      ) {
        return true;
      } else {
        return false;
      }
    } else {
      return false;
    }
  }

  public function hasOverdueInvoices()
  {
    $invoices = Invoice::where('company_id', $this->id)
      ->where('financing_status', 'financed')
      ->whereDate('due_date', '<', now()->format('Y-m-d'))
      ->count();

    return $invoices > 0 ? true : false;
  }

  /**
   * Get the has factoring programs
   *
   * @param  string  $value
   * @return string
   */
  public function getHasFactoringProgramsAttribute()
  {
    return Program::whereHas('anchor', function ($query) {
      $query->where('companies.id', $this->id);
    })
      ->whereHas('programCode', function ($query) {
        $query->whereIn('name', [Program::FACTORING_WITH_RECOURSE, Program::FACTORING_WITHOUT_RECOURSE]);
      })
      ->exists();
  }

  /**
   * Get the has factoring programs
   *
   * @param  string  $value
   * @return string
   */
  public function getHasDealerFinancingProgramsAttribute()
  {
    return Program::whereHas('anchor', function ($query) {
      $query->where('companies.id', $this->id);
    })
      ->whereHas('programType', function ($query) {
        $query->whereIn('name', [Program::DEALER_FINANCING]);
      })
      ->exists();
  }
}
