@php
	$oKrzipModel = KrzipModel::getInstance();
@endphp

{!! $oKrzipModel->getKrzipCodeSearchHtml($input_name, $value) !!}
<style>
.krzip-jibunAddress, .krzip-extraAddress { margin-left:2px !important;}
.krzip-postcode { padding:0 !important; text-align:center}
</style>