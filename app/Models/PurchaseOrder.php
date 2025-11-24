<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PurchaseOrder extends Model
{
  use HasFactory;

  protected $appends = ['total_amount', 'user_has_approved', 'user_can_approve', 'approval_stage', 'can_flip'];

  protected $guarded = [];

  /**
   * Get the company that owns the PurchaseOrder
   */
  public function company(): BelongsTo
  {
    return $this->belongsTo(Company::class);
  }

  /**
   * Get the user that owns the PurchaseOrder
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by', 'id');
  }

  /**
   * Get the anchor that owns the PurchaseOrder
   */
  public function anchor(): BelongsTo
  {
    return $this->belongsTo(Company::class, 'anchor_id', 'id');
  }

  /**
   * Get all of the purchaseOrderItems for the PurchaseOrder
   */
  public function purchaseOrderItems(): HasMany
  {
    return $this->hasMany(PurchaseOrderItem::class);
  }

  /**
   * Get all of the approvals for the PurchaseOrder
   */
  public function approvals(): HasMany
  {
    return $this->hasMany(PurchaseOrderApproval::class);
  }

  /**
   * Get all of the invoices for the PurchaseOrder
   */
  public function invoices(): HasMany
  {
    return $this->hasMany(Invoice::class);
  }

  /**
   * Get the user has approved
   *
   * @return boolean
   */
  public function getUserHasApprovedAttribute()
  {
    $has_approved = $this->approvals()->where('user_id', auth()->id())->exists();

    if ($has_approved) return true;

    return false;
  }

  /**
   * Get the user can approve attribute
   *
   * @param  string  $value
   * @return string
   */
  public function getUserCanApproveAttribute()
  {
    $company = Company::find($this->anchor?->id);

    $approvals = $this->approvals()->pluck('user_id');

    if ($company) {
      if ($this->status === 'pending') {
        // If anchor company, check if maker/checker setting is enabled
        $purchase_order_settings = $company->purchaseOrderSetting;

        if ($purchase_order_settings && $purchase_order_settings->maker_checker_creating_updating) {
          if ($approvals->count() == 0) {
            return true;
          } elseif ($approvals->count() == 1 && !collect($approvals)->contains(auth()->id())) {
            return true;
          } else {
            return true;
          }
        } else {
          return true;
        }

        return true;
      } elseif ($this->status == 'pending_acceptance' && !collect($approvals)->contains(auth()->id())) {
        return true;
      }

      return false;
    }

    return false;
  }

  /**
   * Get the approval stage
   *
   * @return string
   */
  public function getApprovalStageAttribute()
  {
    $status = '';

    $company = Company::find($this->anchor?->id);

    $purchase_order_settings = $company?->purchaseOrderSetting;

    if ($company) {
      if ($this->status === 'pending') {
        // If anchor company, check if maker/checker setting is enabled
        if ($this->company_id != $company->id) {
          if ($purchase_order_settings && $purchase_order_settings->maker_checker_creating_updating) {
            $anchor_approvals_count = $this->approvals()->count();
            if ($anchor_approvals_count == 0) {
              $status = 'pending maker approval';
            } elseif ($anchor_approvals_count == 1) {
              $status = 'pending checker approval';
            } else {
              $status = 'pending acceptance';
            }
          } else {
            $status = 'pending acceptance';
          }
        } else {
          $status = $this->status;
        }
      } elseif ($status == 'pending_acceptance') {
        $status = 'pending acceptance';
      } else {
        $status = $this->status;
      }

      return Str::headline($status);
    }

    return Str::headline($status);
  }

  public function getTotalAmountAttribute()
  {
    $amount = 0;

    foreach ($this->purchaseOrderItems as $purchaseOrderItem) {
      $amount += $purchaseOrderItem->quantity * $purchaseOrderItem->price_per_quantity;
    }

    return $amount;
  }

  public function requiresCheckerApproval()
  {
    // Get active company
    $current_company = auth()->user()->activeAnchorCompany()->first();

    $company = Company::with(['users'])->select('id')->find($current_company->company_id);

    $users = $company->users->pluck('id');

    $users = $users->filter(fn($user) => $user != auth()->id());

    $checker = PurchaseOrderApproval::where('purchase_order_id', $this->id)->whereIn('user_id', $users)->exists();

    if ($users->count() > 1 && !$checker && $company->purchaseOrderSetting->maker_checker_creating_updating && !$this->getUserHasApprovedAttribute()) {
      return true;
    }

    return false;
  }

  public function getCanFlipAttribute()
  {
    if (auth()->check()) {
      if (auth()->user()->hasPermissionTo('Flip PO to Invoice')) {
        return true;
      } else {
        return false;
      }
    } else {
      return false;
    }
  }
}
