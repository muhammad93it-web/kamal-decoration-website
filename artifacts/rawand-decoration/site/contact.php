<?php
require __DIR__ . '/includes/bootstrap.php';
require APP_ROOT . '/templates/partials/cards.php';

$sent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        $error = t('csrf_invalid');
    } elseif (!hp_ok()) {
        // honeypot tripped — pretend success, log nothing
        $sent = true;
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $subject = trim((string)($_POST['subject'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));

        if (mb_strlen($name) < 2 || mb_strlen($message) < 5 || $phone === '') {
            $error = t('contact_err_required');
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = t('contact_err_email');
        } else {
            // rate limit: max 3 messages / 10 min per IP
            $ip = get_ip();
            $rl = db()->prepare('SELECT COUNT(*) FROM contact_messages WHERE ip = ? AND created_at > (NOW() - INTERVAL 10 MINUTE)');
            $rl->execute([$ip]);
            if ((int)$rl->fetchColumn() >= 3) {
                $error = t('contact_err_rate');
            } else {
                $ins = db()->prepare(
                    'INSERT INTO contact_messages (name, phone, email, subject, message, ip)
                     VALUES (?,?,?,?,?,?)'
                );
                $ins->execute([
                    mb_substr($name, 0, 120), mb_substr($phone, 0, 40),
                    mb_substr($email, 0, 190) ?: null, mb_substr($subject, 0, 190) ?: null,
                    mb_substr($message, 0, 5000), $ip,
                ]);
                $sent = true;
            }
        }
    }
}

track_page_view('contact');

$PAGE = ['title' => t('contact_title'), 'desc' => t('contact_sub'), 'nav' => 'contact'];
require APP_ROOT . '/templates/header.php';
?>

<section class="page-hero">
  <div class="container">
    <h1 class="page-title"><?= e(t('contact_title')) ?></h1>
    <p class="page-sub"><?= e(t('contact_sub')) ?></p>
  </div>
</section>
<?php render_breadcrumbs([['label' => t('nav_contact')]]); ?>

<section class="section">
  <div class="container contact-layout">
    <div class="contact-card">
      <?php if ($sent): ?>
        <div class="alert alert-success">✓ <?= e(t('contact_sent')) ?></div>
      <?php elseif ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= e(url('contact.php')) ?>" novalidate>
        <?= csrf_field() ?>
        <?= hp_field() ?>
        <div class="form-grid">
          <div class="field">
            <label for="cName"><?= e(t('contact_name')) ?> *</label>
            <input type="text" id="cName" name="name" required maxlength="120" value="<?= e($_POST['name'] ?? '') ?>">
          </div>
          <div class="field">
            <label for="cPhone"><?= e(t('contact_phone')) ?> *</label>
            <input type="tel" id="cPhone" name="phone" required maxlength="40" dir="ltr" value="<?= e($_POST['phone'] ?? '') ?>">
          </div>
          <div class="field">
            <label for="cEmail"><?= e(t('contact_email')) ?></label>
            <input type="email" id="cEmail" name="email" maxlength="190" dir="ltr" value="<?= e($_POST['email'] ?? '') ?>">
          </div>
          <div class="field">
            <label for="cSubject"><?= e(t('contact_subject')) ?></label>
            <input type="text" id="cSubject" name="subject" maxlength="190" value="<?= e($_POST['subject'] ?? '') ?>">
          </div>
          <div class="field full">
            <label for="cMsg"><?= e(t('contact_message')) ?> *</label>
            <textarea id="cMsg" name="message" required maxlength="5000"><?= e($_POST['message'] ?? '') ?></textarea>
          </div>
        </div>
        <div style="margin-top:18px">
          <button class="btn btn-gold" type="submit"><?= e(t('contact_send')) ?></button>
        </div>
      </form>
    </div>

    <div>
      <div class="contact-info-list">
        <?php
        $infoItems = [
            ['icon' => 'phone', 'label' => t('contact_phone'), 'value' => setting('phone'), 'href' => 'tel:' . preg_replace('/\s+/', '', setting('phone')), 'dir' => 'ltr'],
            ['icon' => 'whatsapp', 'label' => 'WhatsApp', 'value' => setting('whatsapp'), 'href' => wa_link(t('wa_general')), 'dir' => 'ltr'],
            ['icon' => 'maps', 'label' => t('contact_address'), 'value' => setting('address'), 'href' => setting('maps_link'), 'dir' => null],
            ['icon' => 'link', 'label' => t('contact_hours'), 'value' => setting('working_hours'), 'href' => null, 'dir' => null],
        ];
        foreach ($infoItems as $it): if (!$it['value']) continue; ?>
        <div class="ci-item">
          <span class="ci-icon"><?= social_icon($it['icon']) ?></span>
          <div>
            <div class="ci-label"><?= e($it['label']) ?></div>
            <div class="ci-value" <?= $it['dir'] ? 'dir="' . $it['dir'] . '"' : '' ?>>
              <?php if ($it['href']): ?><a href="<?= e($it['href']) ?>" <?= str_starts_with((string)$it['href'], 'http') ? 'target="_blank" rel="noopener"' : '' ?>><?= e($it['value']) ?></a>
              <?php else: ?><?= e($it['value']) ?><?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php $socials = social_links('contact'); if ($socials): ?>
        <div class="footer-socials" style="margin-top:22px">
          <?php foreach ($socials as $so): ?>
            <a style="background:var(--paper);border-color:var(--line-soft);color:var(--ink)" href="<?= e($so['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e($so['title'] ?: $so['platform']) ?>"><?= social_icon($so['platform']) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (setting('address')): ?>
      <div class="map-embed">
        <iframe src="https://maps.google.com/maps?q=<?= rawurlencode(setting('address')) ?>&output=embed" loading="lazy" title="Map"></iframe>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require APP_ROOT . '/templates/footer.php'; ?>
