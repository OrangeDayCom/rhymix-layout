@if (count($widget_info->list ?? []))
<ol>
	@foreach ($widget_info->list as $key => $val)
		<li>
			<a href="{{ isset($val[0]->member_srl) ? getUrl(['member_srl' => $val[0]->member_srl,'act'=>'dispMemberInfo']) : 'javascript:void(0);' }}" class="member_{{ $val[0]->member_srl ?? '0' }}" onclick="return false;">{{ $val[0]->nick_name ?? $widget_info->lang_left_member }}</a>
			{{ number_format($val[1]) }}
		</li>
	@endforeach
</ol>
@else
	<p>{{ $widget_info->msg_not_founded }}</p>
@endif
