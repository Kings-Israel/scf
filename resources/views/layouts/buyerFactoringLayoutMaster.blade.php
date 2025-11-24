@isset($pageConfigs)
{!! Helper::updatePageConfig($pageConfigs) !!}
@endisset
@php
$configData = Helper::appClasses();
@endphp

@isset($configData["layout"])
@include((( $configData["layout"] === 'horizontal') ? 'layouts.buyerFactoringHorizontalLayout' :
(( $configData["layout"] === 'blank') ? 'layouts.blankLayout' : 'layouts.buyerFactoringContentNavbarLayout') ))
@endisset
