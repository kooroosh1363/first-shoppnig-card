<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/ProductCatalog.php';
require_once dirname(__DIR__) . '/src/CartService.php';

$tests = [];

function test(string $name, callable $callback): void {
    global $tests;
    $tests[] = [$name, $callback];
}

function expect_same(mixed $expected, mixed $actual): void {
    if ($expected !== $actual) {
        throw new RuntimeException(sprintf(
            'Expected %s, got %s.',
            var_export($expected, true),
            var_export($actual, true),
        ));
    }
}

function expect_true(bool $condition, string $message = 'Expected true.'): void {
    if (!$condition) throw new RuntimeException($message);
}

$catalog = new ProductCatalog();
$service = new CartService($catalog);

test('adds products and respects stock/max limits', function () use ($service): void {
    $cart = $service->add([], 'linen-overshirt', 2);
    expect_same(2, $cart['linen-overshirt']);

    $cart = $service->add($cart, 'linen-overshirt', 20);
    expect_same(8, $cart['linen-overshirt']);
});

test('rejects unknown products and invalid quantities', function () use ($service): void {
    $thrown = false;
    try {
        $service->add([], 'missing-product', 1);
    } catch (InvalidArgumentException) {
        $thrown = true;
    }
    expect_true($thrown);

    $thrown = false;
    try {
        $service->add([], 'everyday-tee', 0);
    } catch (InvalidArgumentException) {
        $thrown = true;
    }
    expect_true($thrown);
});

test('updates and removes cart lines', function () use ($service): void {
    $cart = $service->add([], 'everyday-tee', 3);
    $cart = $service->update($cart, 'everyday-tee', 2);
    expect_same(2, $cart['everyday-tee']);

    $cart = $service->update($cart, 'everyday-tee', 0);
    expect_same([], $cart);
});

test('calculates line totals with integer cents', function () use ($service): void {
    $cart = [
        'everyday-tee' => 2,
        'canvas-tote' => 1,
    ];
    $lines = $service->lines($cart);

    $totals = [];
    foreach ($lines as $line) {
        $totals[$line['id']] = $line['line_total_cents'];
    }

    expect_same(7600, $totals['everyday-tee']);
    expect_same(4200, $totals['canvas-tote']);
});

test('applies shipping below threshold and free shipping above it', function () use ($service): void {
    $low = $service->totals(['everyday-tee' => 1]);
    expect_same(3800, $low['subtotal_cents']);
    expect_same(CartService::SHIPPING_CENTS, $low['shipping_cents']);
    expect_same(5000, $low['total_cents']);

    $high = $service->totals([
        'tailored-trouser' => 1,
        'linen-overshirt' => 1,
    ]);
    expect_same(18500, $high['subtotal_cents']);
    expect_same(0, $high['shipping_cents']);
    expect_same(18500, $high['total_cents']);
});

test('item count reflects normalized cart quantities', function () use ($service): void {
    expect_same(5, $service->itemCount([
        'everyday-tee' => 3,
        'canvas-tote' => 2,
        'unknown' => 99,
    ]));
});

$failures = 0;
foreach ($tests as [$name, $callback]) {
    try {
        $callback();
        fwrite(STDOUT, "[pass] {$name}\n");
    } catch (Throwable $error) {
        $failures++;
        fwrite(STDERR, "[fail] {$name}: {$error->getMessage()}\n");
    }
}

fwrite(STDOUT, sprintf("\n%d test(s), %d failure(s).\n", count($tests), $failures));
exit($failures === 0 ? 0 : 1);
