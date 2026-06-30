<?php
/**
 * Generator ikon situs / PWA dari satu file sumber.
 * Jalankan: php scripts/gen-icons.php
 * Sumber  : public/logo-source.(png|jpg|jpeg|webp)
 */

$pub = __DIR__ . '/../public';
$iconsDir = $pub . '/icons';
if (!is_dir($iconsDir)) mkdir($iconsDir, 0775, true);

// Cari file sumber
$src = null;
foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
    $cand = "$pub/logo-source.$ext";
    if (is_file($cand)) { $src = $cand; break; }
}
if (!$src) {
    fwrite(STDERR, "ERROR: file sumber tidak ditemukan. Simpan logo ke: public/logo-source.png\n");
    exit(1);
}

$data = file_get_contents($src);
$orig = imagecreatefromstring($data);
if (!$orig) { fwrite(STDERR, "ERROR: gambar tidak bisa dibaca.\n"); exit(1); }
$ow = imagesx($orig); $oh = imagesy($orig);

/** Buat kanvas persegi NxN berisi $orig (object-fit: cover), transparan. */
function square($orig, $ow, $oh, $n) {
    $canvas = imagecreatetruecolor($n, $n);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
    // cover: skala agar sisi terpendek memenuhi, lalu crop tengah
    $scale = max($n / $ow, $n / $oh);
    $nw = (int) round($ow * $scale); $nh = (int) round($oh * $scale);
    $dx = (int) (($n - $nw) / 2); $dy = (int) (($n - $nh) / 2);
    imagealphablending($canvas, true);
    imagecopyresampled($canvas, $orig, $dx, $dy, 0, 0, $nw, $nh, $ow, $oh);
    return $canvas;
}

/** Versi maskable: logo (contain) ~78% di tengah, latar solid biar aman dari crop. */
function maskable($orig, $ow, $oh, $n, $bg = [238, 241, 255]) {
    $canvas = imagecreatetruecolor($n, $n);
    $bgc = imagecolorallocate($canvas, $bg[0], $bg[1], $bg[2]);
    imagefill($canvas, 0, 0, $bgc);
    $inner = (int) round($n * 0.78);
    $scale = min($inner / $ow, $inner / $oh);
    $nw = (int) round($ow * $scale); $nh = (int) round($oh * $scale);
    $dx = (int) (($n - $nw) / 2); $dy = (int) (($n - $nh) / 2);
    imagecopyresampled($canvas, $orig, $dx, $dy, 0, 0, $nw, $nh, $ow, $oh);
    return $canvas;
}

function savePng($img, $path) { imagepng($img, $path, 9); echo "  ✓ " . basename($path) . "\n"; }

echo "Sumber: " . basename($src) . " ({$ow}x{$oh})\n";

// Ukuran PNG penuh (cover)
$sizes = [
    'logo.png'    => 256,  // dipakai di UI (sidebar/topbar/login)
    'icon-16.png' => 16,
    'icon-32.png' => 32,
    'icon-180.png'=> 180,  // apple-touch
    'icon-192.png'=> 192,
    'icon-512.png'=> 512,
];
foreach ($sizes as $name => $n) {
    $im = square($orig, $ow, $oh, $n);
    savePng($im, "$iconsDir/$name");
    imagedestroy($im);
}

// Maskable 512 (latar solid + padding)
$mk = maskable($orig, $ow, $oh, 512);
savePng($mk, "$iconsDir/icon-maskable-512.png");
imagedestroy($mk);

// favicon.ico (PNG-in-ICO: 16 + 32 + 48)
$icoImgs = [];
foreach ([16, 32, 48] as $n) {
    $im = square($orig, $ow, $oh, $n);
    ob_start(); imagepng($im); $png = ob_get_clean();
    imagedestroy($im);
    $icoImgs[] = ['size' => $n, 'png' => $png];
}
$count = count($icoImgs);
$header = pack('vvv', 0, 1, $count);       // reserved, type=1(ico), count
$offset = 6 + $count * 16;
$dir = ''; $body = '';
foreach ($icoImgs as $i) {
    $len = strlen($i['png']);
    $w = $i['size'] >= 256 ? 0 : $i['size'];
    $dir .= pack('CCCCvvVV', $w, $w, 0, 0, 1, 32, $len, $offset);
    $body .= $i['png'];
    $offset += $len;
}
file_put_contents("$pub/favicon.ico", $header . $dir . $body);
echo "  ✓ favicon.ico (16/32/48)\n";

imagedestroy($orig);
echo "Selesai.\n";
