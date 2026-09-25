<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid($_POST['_token'] ?? null)) {
        flash('notice', ['type' => 'error', 'message' => 'Your session expired. Please try again.']);
        redirect('/');
    }

    $productId = (string) ($_POST['product_id'] ?? '');
    $quantity = filter_var(
        $_POST['quantity'] ?? 1,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => CartService::MAX_PER_PRODUCT]],
    );

    try {
        $cart = $cartService->add(
            session_cart(),
            $productId,
            $quantity === false ? 1 : (int) $quantity,
        );
        save_session_cart($cart);

        $product = $productCatalog->find($productId);
        flash('notice', [
            'type' => 'success',
            'message' => ($product['name'] ?? 'Item') . ' added to your cart.',
        ]);
    } catch (InvalidArgumentException $error) {
        flash('notice', ['type' => 'error', 'message' => $error->getMessage()]);
    }

    redirect('/');
}

$notice = pull_flash('notice');
$products = $productCatalog->all();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="description" content="Atelier Cart is a server-side PHP shopping cart demo with stock limits, secure session state, and integer-cent money calculations.">
  <meta name="color-scheme" content="light dark">
  <title>Atelier Cart — PHP Shopping Cart</title>
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
  <a class="skip-link" href="#catalog">Skip to products</a>

  <div class="page-shell">
    <?php require __DIR__ . '/header.php'; ?>

    <main>
      <section class="hero">
        <div>
          <p class="eyebrow">Server-rendered commerce / PHP sessions</p>
          <h1>Cart logic that stays on the server.</h1>
          <p class="hero-copy">
            A compact storefront demo with stock-aware quantities, integer-cent pricing,
            CSRF-protected mutations, persistent session state, and no frontend framework.
          </p>
        </div>

        <div class="hero-stat" aria-label="Cart engineering highlights">
          <div><span>Money</span><strong>Integer cents</strong></div>
          <div><span>State</span><strong>Server session</strong></div>
          <div><span>Mutations</span><strong>POST + CSRF</strong></div>
        </div>
      </section>

      <?php if (is_array($notice)): ?>
        <div class="notice notice--<?= e((string)($notice['type'] ?? 'info')) ?>" role="status">
          <?= e((string)($notice['message'] ?? '')) ?>
        </div>
      <?php endif; ?>

      <section id="catalog" class="catalog-section" aria-labelledby="catalog-title">
        <div class="section-heading">
          <div>
            <p class="section-index">Catalog / 01</p>
            <h2 id="catalog-title">A small, server-owned product catalog.</h2>
          </div>
          <p>Prices and stock limits are defined on the server. The browser never decides what an item costs.</p>
        </div>

        <div class="product-grid">
          <?php foreach ($products as $index => $product): ?>
            <article class="product-card">
              <div class="product-art product-art--<?= ($index % 4) + 1 ?>" aria-hidden="true">
                <span><?= e(substr($product['category'], 0, 1)) ?></span>
              </div>

              <div class="product-meta">
                <div>
                  <p class="product-category"><?= e($product['category']) ?></p>
                  <h3><?= e($product['name']) ?></h3>
                  <p><?= e($product['subtitle']) ?></p>
                </div>
                <strong class="product-price"><?= money($product['price_cents']) ?></strong>
              </div>

              <form method="post" action="/" class="add-form">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="product_id" value="<?= e($product['id']) ?>">

                <label for="qty-<?= e($product['id']) ?>">Quantity</label>
                <select id="qty-<?= e($product['id']) ?>" name="quantity">
                  <?php for ($qty = 1; $qty <= min(CartService::MAX_PER_PRODUCT, $product['stock']); $qty++): ?>
                    <option value="<?= $qty ?>"><?= $qty ?></option>
                  <?php endfor; ?>
                </select>

                <button type="submit">Add to cart <span aria-hidden="true">→</span></button>
              </form>

              <p class="stock-note"><?= $product['stock'] ?> in stock · max <?= min(CartService::MAX_PER_PRODUCT, $product['stock']) ?> per cart</p>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    </main>

    <footer class="site-footer">
      <p>Atelier Cart / portfolio commerce demo</p>
      <p>No checkout or payment processing is simulated.</p>
    </footer>
  </div>
</body>
</html>
