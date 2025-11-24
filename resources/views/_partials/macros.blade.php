@php
if ($configurations && $configurations->logo) {
  $brand_img = $configurations->logo;
}
@endphp
<img src="{{ $brand_img }}" alt="YoFInvoice">
