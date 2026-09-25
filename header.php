<?php
declare(strict_types=1);

$cartCount = isset($cartService) ? $cartService->itemCount(session_cart()) : 0;
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
?>
<header class="site-header">
  <a class="brand" href="/" aria-label="Atelier Cart home">
    <span class="brand-mark">AC</span>
    <span>
      <strong>Atelier Cart</strong>
      <small>Server-side PHP cart</small>
    </span>
  </a>

  <nav class="site-nav" aria-label="Primary">
    <a href="/" <?= $currentPath === '/' || $currentPath === '/index.php' ? 'aria-current="page"' : '' ?>>Shop</a>
    <a class="cart-link" href="/cart.php" <?= $currentPath === '/cart.php' ? 'aria-current="page"' : '' ?>>
      Cart
      <span class="cart-count" aria-label="<?= $cartCount ?> item<?= $cartCount === 1 ? '' : 's' ?> in cart"><?= $cartCount ?></span>
    </a>
  </nav>
</header>
