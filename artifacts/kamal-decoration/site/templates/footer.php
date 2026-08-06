<?php /** Public page footer. */ ?>
</main>

<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-col footer-about">
      <?php if (setting('logo_path') !== ''): ?>
        <img src="<?= e(upload_url(setting('logo_path'))) ?>" alt="<?= e(setting('site_name')) ?>" class="footer-logo">
      <?php else: ?>
        <div class="footer-brand"><?= e(setting('site_name', 'دیکۆراتی کەمال')) ?></div>
      <?php endif; ?>
      <p><?= e(setting('footer_about')) ?></p>
    </div>

    <div class="footer-col">
      <h4><?= e(t('footer_quick')) ?></h4>
      <ul class="footer-links">
        <li><a href="<?= e(url('products.php')) ?>"><?= e(t('nav_products')) ?></a></li>
        <li><a href="<?= e(url('palettes.php')) ?>"><?= e(t('nav_palettes')) ?></a></li>
        <li><a href="<?= e(url('projects.php')) ?>"><?= e(t('nav_projects')) ?></a></li>
        <li><a href="<?= e(url('posts.php')) ?>"><?= e(t('nav_posts')) ?></a></li>
        <li><a href="<?= e(url('about.php')) ?>"><?= e(t('nav_about')) ?></a></li>
        <li><a href="<?= e(url('contact.php')) ?>"><?= e(t('nav_contact')) ?></a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h4><?= e(t('footer_contact')) ?></h4>
      <ul class="footer-contact">
        <?php if (setting('phone') !== ''): ?>
          <li><a href="tel:<?= e(preg_replace('/[^\d+]/', '', '+964' . ltrim(setting('phone'), '0'))) ?>" dir="ltr"><?= e(setting('phone')) ?></a></li>
        <?php endif; ?>
        <?php if (setting('email') !== ''): ?>
          <li><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></li>
        <?php endif; ?>
        <?php if (setting('address') !== ''): ?>
          <li><?= e(setting('address')) ?></li>
        <?php endif; ?>
        <?php if (setting('working_hours') !== ''): ?>
          <li><?= e(setting('working_hours')) ?></li>
        <?php endif; ?>
      </ul>
    </div>

    <div class="footer-col">
      <h4><?= e(t('footer_follow')) ?></h4>
      <div class="footer-socials">
        <?php foreach (social_links('footer') as $l): ?>
          <a href="<?= e($l['url']) ?>" target="_blank" rel="noopener" title="<?= e($l['name']) ?>" aria-label="<?= e($l['name']) ?>"><?= social_icon($l['platform']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="footer-bottom">
    <div class="container">
      <span>© <?= date('Y') ?> <?= e(setting('site_name', 'دیکۆراتی کەمال')) ?> — <?= e(t('footer_rights')) ?></span>
      <span class="footer-bottom-links">
        <a href="<?= e(url('privacy.php')) ?>"><?= e(t('privacy_title')) ?></a>
        <a href="<?= e(url('terms.php')) ?>"><?= e(t('terms_title')) ?></a>
      </span>
    </div>
  </div>
</footer>

<?php $waFloat = social_links('floating'); ?>
<?php if (!empty($waFloat)): $wl = $waFloat[0]; ?>
  <a class="wa-float" href="<?= e($wl['platform'] === 'whatsapp' ? wa_link(t('wa_general')) : $wl['url']) ?>"
     target="_blank" rel="noopener" aria-label="<?= e(t('wa_float_label')) ?>">
    <?= social_icon($wl['platform']) ?>
  </a>
<?php endif; ?>

<div class="lightbox" id="lightbox" hidden>
  <button class="lightbox-close" id="lightboxClose" aria-label="<?= e(t('close')) ?>">✕</button>
  <button class="lightbox-prev" id="lightboxPrev" aria-label="«">‹</button>
  <img src="" alt="" id="lightboxImg">
  <button class="lightbox-next" id="lightboxNext" aria-label="»">›</button>
</div>

<script src="<?= e(asset('js/main.js')) ?>?v=1" defer></script>
</body>
</html>
