<?php

$width = 240;
$height = 140;
$per_line = 24;

$im = imagecreatefrompng('dd_template.png');
/*
$im_text = imagecreatetruecolor($width, $height);
imagealphablending($im, false);
imagesavealpha($im, true);
*/

$bg = imagecolorallocate($im, 114, 150, 166);
$fg = imagecolorallocate($im, 0, 0, 0);
$text = urldecode($_GET['text']);
$x = 226;
$y = 110;

for ($i = 0; $i < strlen($text); $i += $per_line) {

	$sentence = substr($text, $i, $per_line);

	imagestring($im, 7, $x + 2, $y + 2, $sentence, $bg);
	imagestring($im, 7, $x, $y, $sentence, $fg);
	$x += 10;
	$y += 20;

}

#imagecopy($im, $im_text, $x, $y, 0, 0, $width, $height);

header('Content-type: image/png');
imagepng($im);
imagedestroy($im);
