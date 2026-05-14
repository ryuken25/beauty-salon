<?php
// Convert near-black pixels in logo.png to transparent.
// Run: php scripts/make_logo_transparent.php

$src = __DIR__ . '/../logo.png';
$dst = __DIR__ . '/../public/assets/img/logo.png';

if (! is_file($src)) {
    fwrite(STDERR, "Source not found: $src\n");
    exit(1);
}
@mkdir(dirname($dst), 0775, true);

$img = imagecreatefrompng($src);
if (! $img) { fwrite(STDERR, "Failed to read PNG\n"); exit(1); }

$w = imagesx($img);
$h = imagesy($img);

$out = imagecreatetruecolor($w, $h);
imagealphablending($out, false);
imagesavealpha($out, true);
$transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
imagefill($out, 0, 0, $transparent);

// Threshold: any pixel where max(R,G,B) < $thresh becomes fully transparent.
// In between (anti-alias edges) gets partial alpha to preserve smoothness.
$threshLow  = 30;   // below -> fully transparent
$threshHigh = 110;  // above -> fully opaque
for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $rgb = imagecolorat($img, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        $maxC = max($r, $g, $b);
        if ($maxC <= $threshLow) {
            continue; // leave transparent
        }
        if ($maxC >= $threshHigh) {
            $alpha = 0; // fully opaque
        } else {
            // smooth ramp 0..127 -> 127..0
            $alpha = (int) round(127 * (1 - ($maxC - $threshLow) / ($threshHigh - $threshLow)));
        }
        $col = imagecolorallocatealpha($out, $r, $g, $b, $alpha);
        imagesetpixel($out, $x, $y, $col);
    }
}

if (! imagepng($out, $dst, 9)) {
    fwrite(STDERR, "Failed to write $dst\n");
    exit(1);
}
imagedestroy($img);
imagedestroy($out);
echo "Wrote $dst (${w}x${h})\n";
