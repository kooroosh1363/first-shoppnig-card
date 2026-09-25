<?php

declare(strict_types=1);

final class ProductCatalog
{
    /** @var array<string,array{id:string,name:string,subtitle:string,category:string,price_cents:int,stock:int}> */
    private const PRODUCTS = [
        'linen-overshirt' => [
            'id' => 'linen-overshirt',
            'name' => 'Linen Overshirt',
            'subtitle' => 'Breathable layer with a relaxed cut.',
            'category' => 'Outerwear',
            'price_cents' => 8900,
            'stock' => 8,
        ],
        'everyday-tee' => [
            'id' => 'everyday-tee',
            'name' => 'Everyday Tee',
            'subtitle' => 'Heavyweight cotton with a clean neckline.',
            'category' => 'Essentials',
            'price_cents' => 3800,
            'stock' => 12,
        ],
        'tailored-trouser' => [
            'id' => 'tailored-trouser',
            'name' => 'Tailored Trouser',
            'subtitle' => 'Straight-leg trouser with a soft drape.',
            'category' => 'Bottoms',
            'price_cents' => 9600,
            'stock' => 6,
        ],
        'canvas-tote' => [
            'id' => 'canvas-tote',
            'name' => 'Canvas Tote',
            'subtitle' => 'Structured carry-all for everyday use.',
            'category' => 'Accessories',
            'price_cents' => 4200,
            'stock' => 10,
        ],
    ];

    /** @return list<array{id:string,name:string,subtitle:string,category:string,price_cents:int,stock:int}> */
    public function all(): array
    {
        return array_values(self::PRODUCTS);
    }

    /** @return array{id:string,name:string,subtitle:string,category:string,price_cents:int,stock:int}|null */
    public function find(string $id): ?array
    {
        return self::PRODUCTS[$id] ?? null;
    }
}
