<?php
if (!defined('APP_ROOT')) {
    require __DIR__ . '/includes/bootstrap.php';
    http_response_code(404);
}
require_once APP_ROOT . '/templates/partials/cards.php';

$PAGE = ['title' => t('err_404_title'), 'nav' => ''];
require APP_ROOT . '/templates/header.php';
?>

<section class="err-page">
  <div class="err-code">٤٠٤</div>
  <h1><?= e(t('err_404_title')) ?></h1>
  <p><?= e(t('err_404_text')) ?></p>
  <form method="get" action="<?= e(url('search.php')) ?>" style="max-width:420px;margin:0 auto 22px;display:flex;gap:10px">
    <input type="search" name="q" placeholder="<?= e(t('search_placeholder')) ?>" style="flex:1">
    <button class="btn btn-gold" type="submit"><?= e(t('btn_search')) ?></button>
  </form>
  <a class="btn btn-dark" href="<?= e(url('')) ?>"><?= e(t('err_404_home')) ?></a>
</section>

<?php require APP_ROOT . '/templates/footer.php'; ?>
