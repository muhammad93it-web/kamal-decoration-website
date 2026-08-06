<?php
/** Site settings — tabbed. Logo/favicon are stored AS-IS (never recompressed). */
require __DIR__ . '/includes/admin-bootstrap.php';
require_once APP_ROOT . '/includes/uploads.php';
// set_setting() comes from includes/bootstrap.php

$TABS = [
    'general' => [
        'label' => t('a_set_general', 'گشتی'),
        'fields' => [
            'site_name' => ['type' => 'text', 'label' => t('a_set_site_name', 'ناوی ماڵپەڕ (کوردی)')],
            'site_name_latin' => ['type' => 'text', 'label' => t('a_set_site_name_latin', 'ناوی لاتینی'), 'dir' => 'ltr'],
            'tagline' => ['type' => 'text', 'label' => t('a_set_tagline', 'دروشم')],
            'announce_text' => ['type' => 'text', 'label' => t('a_set_announce', 'ڕاگەیاندنی سەرەوە (بەتاڵ = دەرناکەوێت)')],
            'currency_symbol' => ['type' => 'text', 'label' => t('a_set_currency', 'هێمای دراو')],
            'posts_per_page' => ['type' => 'number', 'label' => t('a_set_ppp', 'ژمارەی بابەت لە هەر پەڕەیەک')],
            'show_prices' => ['type' => 'check', 'label' => t('a_set_show_prices', 'پیشاندانی نرخەکان')],
            'maintenance' => ['type' => 'check', 'label' => t('a_set_maintenance', 'دۆخی چاککردنەوە (ماڵپەڕ دادەخرێت بۆ میوانەکان)')],
        ],
    ],
    'branding' => [
        'label' => t('a_set_branding', 'لۆگۆ و ناسنامە'),
        'fields' => [
            'logo_path' => ['type' => 'image_raw', 'label' => t('a_set_logo', 'لۆگۆ'), 'hint' => t('a_set_logo_hint', 'وێنەکە هەروەک خۆی هەڵدەگیرێت — هیچ گۆڕانکاری و پەستاندنێک تێیدا ناکرێت. PNG بە پاشبنەمای ڕوون باشترینە.')],
            'favicon_path' => ['type' => 'image_raw', 'label' => t('a_set_favicon', 'فاڤیکۆن (ئایکۆنی تاب)'), 'hint' => t('a_set_favicon_hint', 'وێنەی چوارگۆشە — 64×64 یان گەورەتر')],
            'color_accent' => ['type' => 'color', 'label' => t('a_set_color_accent', 'ڕەنگی زێڕین (Accent)')],
            'color_dark' => ['type' => 'color', 'label' => t('a_set_color_dark', 'ڕەنگی تاریک')],
            'color_bg' => ['type' => 'color', 'label' => t('a_set_color_bg', 'ڕەنگی پاشبنەما')],
        ],
    ],
    'contact' => [
        'label' => t('a_set_contact', 'پەیوەندی'),
        'fields' => [
            'phone' => ['type' => 'text', 'label' => t('contact_phone', 'ژمارەی مۆبایل'), 'dir' => 'ltr'],
            'whatsapp' => ['type' => 'text', 'label' => t('a_set_whatsapp', 'واتسئاپ (بە کۆدی وڵات، نموونە: 9647500244706)'), 'dir' => 'ltr'],
            'email' => ['type' => 'text', 'label' => t('a_set_email', 'ئیمەیل'), 'dir' => 'ltr'],
            'address' => ['type' => 'text', 'label' => t('contact_address', 'ناونیشان')],
            'maps_link' => ['type' => 'text', 'label' => t('a_set_maps', 'لینکی Google Maps'), 'dir' => 'ltr'],
            'working_hours' => ['type' => 'text', 'label' => t('contact_hours', 'کاتەکانی کارکردن')],
            'footer_about' => ['type' => 'textarea', 'label' => t('a_set_footer_about', 'کورتە دەربارە (ژێرپەڕە)')],
        ],
    ],
    'hero' => [
        'label' => t('a_set_hero', 'پەڕەی سەرەکی'),
        'fields' => [
            'hero_title' => ['type' => 'text', 'label' => t('a_set_hero_title', 'ناونیشانی گەورە')],
            'hero_subtitle' => ['type' => 'textarea', 'label' => t('a_set_hero_sub', 'ژێرنووس')],
            'hero_btn1_text' => ['type' => 'text', 'label' => t('a_set_btn', 'دوگمە') . ' ١ — ' . t('a_f_btn_text', 'نووسین')],
            'hero_btn1_url' => ['type' => 'text', 'label' => t('a_set_btn', 'دوگمە') . ' ١ — ' . t('a_f_btn_url', 'لینک'), 'dir' => 'ltr'],
            'hero_btn2_text' => ['type' => 'text', 'label' => t('a_set_btn', 'دوگمە') . ' ٢ — ' . t('a_f_btn_text', 'نووسین')],
            'hero_btn2_url' => ['type' => 'text', 'label' => t('a_set_btn', 'دوگمە') . ' ٢ — ' . t('a_f_btn_url', 'لینک'), 'dir' => 'ltr'],
            'hero_btn3_text' => ['type' => 'text', 'label' => t('a_set_btn', 'دوگمە') . ' ٣ (واتسئاپ) — ' . t('a_f_btn_text', 'نووسین')],
        ],
    ],
    'seo' => [
        'label' => 'SEO',
        'fields' => [
            'seo_title' => ['type' => 'text', 'label' => t('a_set_seo_title', 'ناونیشانی SEO')],
            'seo_description' => ['type' => 'textarea', 'label' => t('a_set_seo_desc', 'وەسفی SEO (150-160 پیت)')],
            'og_image' => ['type' => 'image', 'label' => t('a_set_og', 'وێنەی هاوبەشکردن (OG)'), 'hint' => '1200×630'],
            'site_url' => ['type' => 'text', 'label' => t('a_set_site_url', 'لینکی ماڵپەڕ (بۆ QR و سایتمەپ)'), 'dir' => 'ltr', 'hint' => 'https://example.com'],
        ],
    ],
];

$tab = (string)($_GET['tab'] ?? 'general');
if (!isset($TABS[$tab])) $tab = 'general';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $fields = $TABS[$tab]['fields'];
    foreach ($fields as $key => $f) {
        switch ($f['type']) {
            case 'check':
                set_setting($key, isset($_POST[$key]) ? '1' : '0');
                break;
            case 'number':
                set_setting($key, (string)max(1, (int)($_POST[$key] ?? 12)));
                break;
            case 'color':
                $v = strtoupper(trim((string)($_POST[$key] ?? '')));
                if (preg_match('/^#[0-9A-F]{6}$/', $v)) set_setting($key, $v);
                break;
            case 'image':
            case 'image_raw':
                $file = $_FILES['file_' . $key] ?? null;
                if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    try {
                        $rel = handle_upload($file, [
                            'subdir' => 'branding',
                            'no_recompress' => $f['type'] === 'image_raw',
                            'allow_svg' => $f['type'] === 'image_raw',
                        ]);
                        set_setting($key, $rel);
                    } catch (KDUploadException $ex) {
                        flash('error', $f['label'] . ': ' . $ex->getMessage());
                    }
                } elseif (isset($_POST['del_' . $key])) {
                    set_setting($key, '');
                }
                break;
            default:
                set_setting($key, mb_substr(trim((string)($_POST[$key] ?? '')), 0, 1000));
        }
    }
    log_activity('settings', $tab, null, implode(',', array_keys($fields)));
    flash('success', t('a_saved', 'پاشەکەوت کرا ✓'));
    redirect(admin_url('settings.php?tab=' . $tab));
}

$S = settings_all();

admin_header(t('a_settings', 'ڕێکخستنەکان'), 'settings');
?>

<div class="tabs">
  <?php foreach ($TABS as $k => $tcfg): ?>
    <a class="tab <?= $tab === $k ? 'active' : '' ?>" href="<?= e(admin_url('settings.php?tab=' . $k)) ?>"><?= e($tcfg['label']) ?></a>
  <?php endforeach; ?>
</div>

<form method="post" enctype="multipart/form-data" class="panel" style="max-width:860px">
  <?= csrf_field() ?>
  <div class="f-grid">
    <?php foreach ($TABS[$tab]['fields'] as $key => $f):
        $val = (string)($S[$key] ?? ''); ?>
      <div class="f-row <?= in_array($f['type'], ['textarea'], true) ? 'full' : '' ?>">
        <?php if ($f['type'] === 'check'): ?>
          <label class="f-check"><input type="checkbox" name="<?= e($key) ?>" <?= $val === '1' ? 'checked' : '' ?>> <?= e($f['label']) ?></label>

        <?php elseif ($f['type'] === 'textarea'): ?>
          <label><?= e($f['label']) ?></label>
          <textarea name="<?= e($key) ?>" rows="3"><?= e($val) ?></textarea>

        <?php elseif ($f['type'] === 'color'): ?>
          <label><?= e($f['label']) ?></label>
          <div class="hex-pair" data-hex-pair>
            <input type="color" value="<?= e($val ?: '#BFA05A') ?>">
            <input type="text" name="<?= e($key) ?>" value="<?= e($val) ?>" dir="ltr" maxlength="7">
          </div>

        <?php elseif ($f['type'] === 'image' || $f['type'] === 'image_raw'): ?>
          <label><?= e($f['label']) ?></label>
          <?php if ($val): ?>
            <img class="img-prev" src="<?= e(upload_url($val)) ?>" alt="" style="background:#eee">
            <label class="f-check" style="margin-bottom:8px"><input type="checkbox" name="del_<?= e($key) ?>"> <?= e(t('a_remove_image', 'وێنەکە لاببە')) ?></label>
          <?php endif; ?>
          <input type="file" name="file_<?= e($key) ?>" accept="image/*<?= $f['type'] === 'image_raw' ? ',.svg' : '' ?>">
          <?php if (!empty($f['hint'])): ?><div class="f-hint"><?= e($f['hint']) ?></div><?php endif; ?>

        <?php elseif ($f['type'] === 'number'): ?>
          <label><?= e($f['label']) ?></label>
          <input type="number" name="<?= e($key) ?>" value="<?= e($val) ?>" min="1">

        <?php else: ?>
          <label><?= e($f['label']) ?></label>
          <input type="text" name="<?= e($key) ?>" value="<?= e($val) ?>" <?= !empty($f['dir']) ? 'dir="' . e($f['dir']) . '"' : '' ?>>
          <?php if (!empty($f['hint'])): ?><div class="f-hint"><?= e($f['hint']) ?></div><?php endif; ?>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="form-foot">
    <button class="btn btn-gold" type="submit"><?= e(t('a_save', 'پاشەکەوتکردن')) ?></button>
  </div>
</form>

<?php admin_footer(); ?>
