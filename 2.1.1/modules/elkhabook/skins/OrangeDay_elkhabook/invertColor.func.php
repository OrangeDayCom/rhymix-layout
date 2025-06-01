<?php
namespace elkhabook;
function invertColor(string $color) : string
{
	$color = trim($color);

	// HEX 색상 처리
	if (preg_match('/^#?([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $color)) {
		$color = ltrim($color, '#');
		if (strlen($color) == 3) {
			$color = preg_replace('/./', '$0$0', $color);
		}
		$rgb = array_map('hexdec', str_split($color, 2));
	}
	// RGB 색상 처리
	elseif (preg_match('/^rgb\(\s*(\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3})\s*\)$/i', $color, $matches)) {
		$rgb = [$matches[1], $matches[2], $matches[3]];
	}
	else {
		return $color; // 유효하지 않은 입력
	}

	// RGB 값 반전
	$inverted = array_map(function($c) { return 255 - $c; }, $rgb);

	return sprintf("#%02X%02X%02X", $inverted[0], $inverted[1], $inverted[2]);
}
