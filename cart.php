<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid($_POST['_token'] ?? null)) {
        flash('notice', ['type' => 'error', 'message' => 'Your session expired. Please try again.']);
        redirect('/cart.php');
    }

    $action = (string) ($_POST['action'] ?? '');
    $productId = (string) ($_POST['product_id'] ?? '');

    try {
        $cart = session_cart();

        if ($action === 'update') {
            $quantity = filter_var(
                $_POST['quantity'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0, 'max_range' => CartService::MAX_PER_PRODUCT]],
            );
            if ($quantity === false) {
                throw new InvalidArgumentException('Quantity must be a whole number.');
            }
            $cart = $cartService->update($cart, $productId, (int) $quantity);
            flash('notice', ['type' => 'success', 'message' => 'Cart quantity updated.']);
        } elseif ($action === 'remove') {
            $cart = $cartService->remove($cart, $productId);
            flash('notice', ['type' => 'success', 'message' => 'Item removed from cart.']);
        } elseif ($action === 'clear') {
            $cart = [];
            flash('notice', ['type' => 'success', 'message' => 'Cart cleared.']);
        } else {
            throw new InvalidArgumentException('Unknown cart action.');
        }

        save_session_cart($cart);
    } catch (InvalidArgumentException $error) {
        flash('notice', ['type' => 'error', 'message' => $error->getMessage()]);
    }

    redirect('/cart.php');
}

$notice = pull_flash('notice');
$cart = session_cart();
$lines = $cartService->lines($cart);
$totals = $cartService->totals($cart);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="description" content="Review and update the Atelier Cart server-side PHP shopping cart.">
  <meta name="color-scheme" content="light dark">
  <title>Your Cart — Atelier Cart</title>
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
  <a class="skip-link" href="#cart-content">Skip to cart</a>

  <div class="page-shell">
    <?php require __DIR__ . '/header.php'; ?>

    <main id="cart-content" class="cart-layout">
      <section class="cart-main">
        <div class="section-heading section-heading--cart">
          <div>
            <p class="section-index">Cart / 02</p>
            <h1>Your selected pieces.</h1>
          </div>
          <a class="text-link" href="/">Continue shopping</a>
        </div>

        <?php if (is_array($notice)): ?>
          <div class="notice notice--<?= e((string)($notice['type'] ?? 'info')) ?>" role="status">
            <?= e((string)($notice['message'] ?? '')) ?>
          </div>
        <?php endif; ?>

        <?php if ($lines === []): ?>
          <div class="empty-cart">
            <span aria-hidden="true">0</span>
            <h2>Your cart is empty.</h2>
            <p>Add something from the catalog to exercise the server-side cart flow.</p>
            <a class="primary-link" href="/">Browse products →</a>
          </div>
        <?php else: ?>
          <div class="cart-lines">
            <?php foreach ($lines as $line): ?>
              <article class="cart-line">
                <div class="cart-thumb" aria-hidden="true"><?= e(substr($line['category'], 0, 1)) ?></div>

                <div class="cart-line-info">
                  <p class="product-category"><?= e($line['category']) ?></p>
                  <h2><?= e($line['name']) ?></h2>
                  <p><?= e($line['subtitle']) ?></p>
                </div>

                <form method="post" action="/cart.php" class="quantity-form">
                  <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="product_id" value="<?= e($line['id']) ?>">
                  <label for="cart-qty-<?= e($line['id']) ?>">Qty</label>
                  <input
                    id="cart-qty-<?= e($line['id']) ?>"
                    name="quantity"
                    type="number"
                    min="0"
                    max="<?= min(CartService::MAX_PER_PRODUCT, $line['stock']) ?>"
                    value="<?= $line['quantity'] ?>"
                  >
                  <button type="submit">Update</button>
                </form>

                <strong class="line-price"><?= money($line['line_total_cents']) ?></strong>

                <form method="post" action="/cart.php">
                  <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="action" value="remove">
                  <input type="hidden" name="product_id" value="<?= e($line['id']) ?>">
                  <button class="remove-button" type="submit">Remove</button>
                </form>
              </article>
            <?php endforeach; ?>
          </div>

          <form method="post" action="/cart.php" class="clear-form">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="clear">
            <button type="submit">Clear cart</button>
          </form>
        <?php endif; ?>
      </section>

      <aside class="order-summary" aria-labelledby="summary-title">
        <p class="section-index">Summary / server-calculated</p>
        <h2 id="summary-title">Order summary</h2>

        <dl>
          <div><dt>Items</dt><dd><?= $cartService->itemCount($cart) ?></dd></div>
          <div><dt>Subtotal</dt><dd><?= money($totals['subtotal_cents']) ?></dd></div>
          <div><dt>Shipping</dt><dd><?= $totals['shipping_cents'] === 0 ? 'Free' : money($totals['shipping_cents']) ?></dd></div>
          <div class="summary-total"><dt>Total</dt><dd><?= money($totals['total_cents']) ?></dd></div>
        </dl>

        <?php if ($totals['free_shipping_remaining_cents'] > 0 && $totals['subtotal_cents'] > 0): ?>
          <p class="shipping-progress">
            Add <?= money($totals['free_shipping_remaining_cents']) ?> more for free shipping.
          </p>
        <?php elseif ($totals['subtotal_cents'] >= CartService::FREE_SHIPPING_THRESHOLD_CENTS): ?>
          <p class="shipping-progress">Free shipping unlocked.</p>
        <?php endif; ?>

        <div class="checkout-boundary">
          <strong>Checkout intentionally omitted.</strong>
          <p>This demo stops at cart totals. No fake payment flow or order placement is presented.</p>
        </div>
      </aside>
    </main>

    <footer class="site-footer">
      <p>Atelier Cart / server-side PHP</p>
      <p>Prices and stock are server-owned.</p>
    </footer>
  </div>
</body>
</html>
