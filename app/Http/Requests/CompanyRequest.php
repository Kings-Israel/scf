<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   *
   * @return bool
   */
  public function authorize()
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, mixed>
   */
  public function rules()
  {
    return [
      'name' => 'required',
      'unique_identification_number' => 'required',
      'business_identification_number' => 'required',
      'logo' => [
        'nullable',
        'mimes:png,jpg',
        'max:10000'
      ],
      'top_level_borrower_limit' => ['required'],
      'limit_expiry_date' => ['required'],
      // 'kra_pin' => ['required', 'unique:companies,kra_pin'],
    ];
  }
}
