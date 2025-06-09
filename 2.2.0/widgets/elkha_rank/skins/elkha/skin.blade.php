@load ('style.min.css')

<div class="elkha_rank">
@if (count($widget_info->list ?? []))
	<table>
		<thead>
		<tr>
			<th scope="col">{{ $widget_info->lang_rank }}</th>
			<th scope="col">{$lang->nick_name}</th>
			<th scope="col">{{ $widget_info->lang_rank_name }}</th>
			<th scope="col">{{ $lang->signup_date }}</th>
		</tr>
		</thead>
		<tbody>
		@foreach ($widget_info->list as $key => $val)
			<tr>
				<td class="rank rank-{{ $key + 1 }}">{{ $key + 1 }}</td>
				<td class="nick_name">
					<a href="{{ isset($val[0]->member_srl) ? getUrl(['member_srl' => $val[0]->member_srl,'act'=>'dispMemberInfo']) : 'javascript:void(0);' }}" class="member_{{ $val[0]->member_srl ?? '0' }}" onclick="return false;">{{ $val[0]->nick_name ?? $widget_info->lang_left_member }}</a>
				</td>
				<td class="value">
					<span style="right:{{ 100 - floor($val[1] / $widget_info->max_count * 100) }}%;">{{ number_format($val[1]) }}</span>
				</td>
				<td class="regdate">{{ isset($val[0]->regdate) ? zdate($val[0]->regdate, 'Y-m-d') : '-' }}</td>
			</tr>
		@endforeach
		</tbody>
	</table>
@else
	<p>{{ $widget_info->lang_msg_not_founded }}</p>
@endif
</div>
