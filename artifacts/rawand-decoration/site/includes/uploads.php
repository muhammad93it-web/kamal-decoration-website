<?php
/** Safe image upload pipeline + GD thumbnails + media library records. */

class KDUploadException extends Exception {}

const KD_ALLOWED_MIME = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
];

/**
 * Handle one uploaded image.
 * $opts: subdir (default 'media'), no_recompress (bool, e.g. logo), alt (string),
 *        date_folders (bool, default true), max_width (int, default 1920)
 * Returns uploads-relative path.
 */
function handle_upload(array $file, array $opts = []): string
{
    $subdir = preg_replace('/[^a-z0-9_-]/', '', $opts['subdir'] ?? 'media') ?: 'media';
    $maxMb = 15;

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new KDUploadException('هەڵە لە بارکردنی فایلەکە — دووبارە هەوڵ بدە');
    }
    if (($file['size'] ?? 0) > $maxMb * 1024 * 1024) {
        throw new KDUploadException("قەبارەی فایلەکە زۆرە — گەورەترین قەبارە {$maxMb}MB یە");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($file['tmp_name']);
    if (!isset(KD_ALLOWED_MIME[$mime])) {
        throw new KDUploadException('تەنها وێنە ڕێگەپێدراوە: JPG، PNG، WebP، GIF');
    }
    $dims = @getimagesize($file['tmp_name']);
    if ($dims === false) {
        throw new KDUploadException('فایلەکە وێنەیەکی دروست نییە');
    }

    $ext = KD_ALLOWED_MIME[$mime];
    $useDateFolders = $opts['date_folders'] ?? true;
    $folder = $subdir . ($useDateFolders ? '/' . date('Y/m') : '');
    $absFolder = UPLOAD_DIR . '/' . $folder;
    if (!is_dir($absFolder) && !@mkdir($absFolder, 0755, true)) {
        throw new KDUploadException('نەتوانرا فۆڵدەری وێنەکان دروست بکرێت');
    }

    $name = date('Ymd-His') . '-' . substr(bin2hex(random_bytes(4)), 0, 6) . '.' . $ext;
    $rel = $folder . '/' . $name;
    $abs = $absFolder . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $abs)) {
        throw new KDUploadException('نەتوانرا فایلەکە پاشەکەوت بکرێت');
    }
    @chmod($abs, 0644);

    $noRecompress = !empty($opts['no_recompress']);
    if (!$noRecompress) {
        kd_limit_width($abs, $mime, (int)($opts['max_width'] ?? 1920));
        kd_make_thumb($abs, UPLOAD_DIR . '/thumbnails/' . str_replace('/', '_', $rel), $mime);
    }

    // media library record
    try {
        $dims2 = @getimagesize($abs) ?: [null, null];
        $st = db()->prepare(
            'INSERT INTO media (filename, path, thumb_path, mime, size_bytes, width, height, alt_text, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $thumbRel = $noRecompress ? null : 'thumbnails/' . str_replace('/', '_', $rel);
        $st->execute([
            $name, $rel, $thumbRel, $mime, (int)filesize($abs),
            $dims2[0], $dims2[1],
            mb_substr((string)($opts['alt'] ?? ''), 0, 255),
            current_user()['id'] ?? null,
        ]);
    } catch (Throwable $e) { /* media record is best-effort */ }

    return $rel;
}

/** Open an image resource by mime. */
function kd_img_open(string $abs, string $mime)
{
    return match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($abs),
        'image/png'  => @imagecreatefrompng($abs),
        'image/webp' => @imagecreatefromwebp($abs),
        'image/gif'  => @imagecreatefromgif($abs),
        default => false,
    };
}

/** Downscale an image in place if wider than $maxW (keeps type). */
function kd_limit_width(string $abs, string $mime, int $maxW = 1920): void
{
    $info = @getimagesize($abs);
    if (!$info || $info[0] <= $maxW) return;
    $src = kd_img_open($abs, $mime);
    if (!$src) return;

    $w = $info[0]; $h = $info[1];
    $nw = $maxW; $nh = (int)round($h * $maxW / $w);
    $dst = imagecreatetruecolor($nw, $nh);
    if (in_array($mime, ['image/png', 'image/webp', 'image/gif'], true)) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    match ($mime) {
        'image/jpeg' => imagejpeg($dst, $abs, 85),
        'image/png'  => imagepng($dst, $abs, 8),
        'image/webp' => imagewebp($dst, $abs, 85),
        'image/gif'  => imagegif($dst, $abs),
    };
    imagedestroy($src);
    imagedestroy($dst);
}

/** Create a thumbnail (max edge 480px). Thumbs are always JPEG-quality-friendly originals' type. */
function kd_make_thumb(string $absSrc, string $absDst, string $mime, int $max = 480): void
{
    $info = @getimagesize($absSrc);
    if (!$info) return;
    $src = kd_img_open($absSrc, $mime);
    if (!$src) return;

    $w = $info[0]; $h = $info[1];
    $scale = min(1, $max / max($w, $h));
    $nw = max(1, (int)round($w * $scale));
    $nh = max(1, (int)round($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    if (in_array($mime, ['image/png', 'image/webp', 'image/gif'], true)) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    if (!is_dir(dirname($absDst))) @mkdir(dirname($absDst), 0755, true);
    match ($mime) {
        'image/jpeg' => imagejpeg($dst, $absDst, 82),
        'image/png'  => imagepng($dst, $absDst, 8),
        'image/webp' => imagewebp($dst, $absDst, 82),
        'image/gif'  => imagegif($dst, $absDst),
    };
    imagedestroy($src);
    imagedestroy($dst);
}
