<?php

declare(strict_types=1);

final class CartService
{
    public const MAX_PER_PRODUCT = 10;
    public const FREE_SHIPPING_THRESHOLD_CENTS = 18000;
    public const SHIPPING_CENTS = 1200;

    public function __construct(private ProductCatalog $catalog) {}

    /** @param array<string,int> $cart
     *  @return array<string,int>
     */
    public function normalize(array $cart): array
    {
        $normalized = [];

        foreach ($cart as $productId => $quantity) {
            $product = $this->catalog->find((string) $productId);
            if ($product === null) continue;

            $max = min(self::MAX_PER_PRODUCT, $product['stock']);
            $qty = max(0, min((int) $quantity, $max));

            if ($qty > 0) {
                $normalized[$product['id']] = $qty;
            }
        }

        ksort($normalized);
        return $normalized;
    }

    /** @param array<string,int> $cart
     *  @return array<string,int>
     */
    public function add(array $cart, string $productId, int $quantity = 1): array
    {
        $product = $this->catalog->find($productId);
        if ($product === null) {
            throw new InvalidArgumentException('Unknown product.');
        }

        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least one.');
        }

        $current = (int) ($cart[$productId] ?? 0);
        $max = min(self::MAX_PER_PRODUCT, $product['stock']);
        $cart[$productId] = min($current + $quantity, $max);

        return $this->normalize($cart);
    }

    /** @param array<string,int> $cart
     *  @return array<string,int>
     */
    public function update(array $cart, string $productId, int $quantity): array
    {
        if ($this->catalog->find($productId) === null) {
            throw new InvalidArgumentException('Unknown product.');
        }

        if ($quantity <= 0) {
            unset($cart[$productId]);
            return $this->normalize($cart);
        }

        $cart[$productId] = $quantity;
        return $this->normalize($cart);
    }

    /** @param array<string,int> $cart
     *  @return array<string,int>
     */
    public function remove(array $cart, string $productId): array
    {
        unset($cart[$productId]);
        return $this->normalize($cart);
    }

    /** @param array<string,int> $cart */
    public function itemCount(array $cart): int
    {
        return array_sum($this->normalize($cart));
    }

    /** @param array<string,int> $cart
     *  @return list<array{id:string,name:string,subtitle:string,category:string,price_cents:int,stock:int,quantity:int,line_total_cents:int}>
     */
    public function lines(array $cart): array
    {
        $lines = [];

        foreach ($this->normalize($cart) as $productId => $quantity) {
            $product = $this->catalog->find($productId);
            if ($product === null) continue;

            $lines[] = [
                ...$product,
                'quantity' => $quantity,
                'line_total_cents' => $product['price_cents'] * $quantity,
            ];
        }

        return $lines;
    }

    /** @param array<string,int> $cart
     *  @return array{subtotal_cents:int,shipping_cents:int,total_cents:int,free_shipping_remaining_cents:int}
     */
    public function totals(array $cart): array
    {
        $subtotal = array_sum(array_column($this->lines($cart), 'line_total_cents'));
        $shipping = $subtotal === 0 || $subtotal >= self::FREE_SHIPPING_THRESHOLD_CENTS
            ? 0
            : self::SHIPPING_CENTS;

        return [
            'subtotal_cents' => $subtotal,
            'shipping_cents' => $shipping,
            'total_cents' => $subtotal + $shipping,
            'free_shipping_remaining_cents' => max(
                0,
                self::FREE_SHIPPING_THRESHOLD_CENTS - $subtotal,
            ),
        ];
    }
}
