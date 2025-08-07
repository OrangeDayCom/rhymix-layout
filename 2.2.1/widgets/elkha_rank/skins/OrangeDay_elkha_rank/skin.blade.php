@load ('style.min.css')
<div class="wrap_contentex rank">
	<div class="rank_ti"><i class='bx bxs-bar-chart-square' ></i> {$widget_info->lang_rank_name}</div>
	<div class="elkha_rank">
	@if (count($widget_info->list ?? []))
		<table>
			<tbody>
			@foreach ($widget_info->list as $key => $val)
				<tr>
					<td class="rank rank-{{ $key + 1 }}"><span>{{ $key + 1 }}</span></td>
					<td class="nick_name">
						<a href="{{ isset($val[0]->member_srl) ? getUrl(['member_srl' => $val[0]->member_srl,'act'=>'dispMemberInfo']) : 'javascript:void(0);' }}" class="member_{{ $val[0]->member_srl ?? '0' }}" onclick="return false;">{{ $val[0]->nick_name ?? $widget_info->lang_left_member }}</a>
					</td>
					<td class="value">
					@if ($widget_info->max_count > 0)
						<div style="width:{{ floor($val[1] / $widget_info->max_count * 100) }}%;">
					@else
						<div style="width:0%;">
					@endif
						<div></div></div>
						<span>{{ number_format($val[1]) }}</span>
					</td>
				</tr>
			@endforeach
			</tbody>
		</table>
	@else
		<p>{{ $widget_info->lang_msg_not_founded }}</p>
	@endif
	</div>
</div>
