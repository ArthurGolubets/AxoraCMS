<?php

namespace HolartWeb\AxoraCMS\Services;

use Illuminate\Support\Facades\Session;

class CartService
{
    private const SESSION_KEY = 'holart_cart';

    /**
     * Добавить товар в корзину
     */
    public function addToCart(int $productId, string $productName, float $price, int $quantity = 1, ?int $variantId = null, ?array $variantData = null, array $additionalData = []): array
    {
        $cart = $this->getCart();

        $itemKey = $this->generateItemKey($productId, $variantId, $additionalData);

        if (isset($cart[$itemKey])) {
            $cart[$itemKey]['quantity'] += $quantity;
        } else {
            $cart[$itemKey] = [
                'product_id' => $productId,
                'product_name' => $productName,
                'price' => $price,
                'quantity' => $quantity,
                'variant_id' => $variantId,
                'variant_data' => $variantData,
                'additional_data' => $additionalData,
                'set_group' => null,
                'set_role' => 'single',
            ];
        }

        $this->saveCart($cart);

        return $cart[$itemKey];
    }

    /**
     * Добавить в корзину набор: основной товар + сопутствующие товары.
     *
     * Каждый элемент массива имеет вид:
     *   ['product_id' => int, 'product_name' => string, 'price' => float,
     *    'quantity' => int, 'variant_id' => ?int, 'variant_data' => ?array,
     *    'additional_data' => array]
     *
     * @param  array<string, mixed>  $main
     * @param  array<int, array<string, mixed>>  $companions
     * @return array{set_group: string, items: array<int, array<string, mixed>>}
     */
    public function addSetToCart(array $main, array $companions = []): array
    {
        $cart = $this->getCart();
        $setGroup = 'set_'.bin2hex(random_bytes(8));
        $added = [];

        foreach (array_merge([['role' => 'parent', 'data' => $main]], array_map(
            fn ($c) => ['role' => 'child', 'data' => $c],
            $companions
        )) as $entry) {
            $data = $entry['data'];
            $productId = (int) ($data['product_id'] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $variantId = isset($data['variant_id']) ? (int) $data['variant_id'] : null;
            $additionalData = $data['additional_data'] ?? [];
            $itemKey = $this->generateItemKey($productId, $variantId, $additionalData, $setGroup);

            $cart[$itemKey] = [
                'product_id' => $productId,
                'product_name' => $data['product_name'] ?? '',
                'price' => (float) ($data['price'] ?? 0),
                'quantity' => max(1, (int) ($data['quantity'] ?? 1)),
                'variant_id' => $variantId,
                'variant_data' => $data['variant_data'] ?? null,
                'additional_data' => $additionalData,
                'set_group' => $setGroup,
                'set_role' => $entry['role'],
            ];

            $added[] = $cart[$itemKey];
        }

        $this->saveCart($cart);

        return ['set_group' => $setGroup, 'items' => $added];
    }

    /**
     * Удалить набор целиком (основной товар и все сопутствующие).
     */
    public function removeSet(string $setGroup): bool
    {
        $cart = $this->getCart();
        $removed = false;

        foreach ($cart as $key => $item) {
            if (($item['set_group'] ?? null) === $setGroup) {
                unset($cart[$key]);
                $removed = true;
            }
        }

        if ($removed) {
            $this->saveCart($cart);
        }

        return $removed;
    }

    /**
     * Содержимое корзины, сгруппированное по наборам.
     *
     * @return array{sets: array<string, array<int, array<string, mixed>>>, items: array<int, array<string, mixed>>}
     */
    public function getGroupedCart(): array
    {
        $sets = [];
        $items = [];

        foreach ($this->getCart() as $key => $item) {
            $item['item_key'] = $key;
            if (! empty($item['set_group'])) {
                $sets[$item['set_group']][] = $item;
            } else {
                $items[] = $item;
            }
        }

        return ['sets' => $sets, 'items' => $items];
    }

    /**
     * Удалить товар из корзины
     */
    public function removeFromCart(string $itemKey): bool
    {
        $cart = $this->getCart();

        if (! isset($cart[$itemKey])) {
            return false;
        }

        $item = $cart[$itemKey];

        // Removing the main item of a set removes the whole set.
        if (! empty($item['set_group']) && ($item['set_role'] ?? null) === 'parent') {
            return $this->removeSet($item['set_group']);
        }

        unset($cart[$itemKey]);
        $this->saveCart($cart);

        return true;
    }

    /**
     * Обновить количество товара
     */
    public function updateQuantity(string $itemKey, int $quantity): bool
    {
        $cart = $this->getCart();

        if (isset($cart[$itemKey])) {
            if ($quantity <= 0) {
                return $this->removeFromCart($itemKey);
            }

            $cart[$itemKey]['quantity'] = $quantity;
            $this->saveCart($cart);

            return true;
        }

        return false;
    }

    /**
     * Очистить корзину
     */
    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Получить содержимое корзины
     */
    public function getCart(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    /**
     * Получить количество товаров в корзине
     */
    public function getItemsCount(): int
    {
        $cart = $this->getCart();
        $count = 0;

        foreach ($cart as $item) {
            $count += $item['quantity'];
        }

        return $count;
    }

    /**
     * Получить общую сумму корзины
     */
    public function getTotalPrice(): float
    {
        $cart = $this->getCart();
        $total = 0.0;

        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return round($total, 2);
    }

    /**
     * Получить товар из корзины
     */
    public function getItem(string $itemKey): ?array
    {
        $cart = $this->getCart();

        return $cart[$itemKey] ?? null;
    }

    /**
     * Проверить, есть ли товар в корзине
     */
    public function hasItem(string $itemKey): bool
    {
        $cart = $this->getCart();

        return isset($cart[$itemKey]);
    }

    /**
     * Сохранить корзину в сессию
     */
    private function saveCart(array $cart): void
    {
        Session::put(self::SESSION_KEY, $cart);
    }

    /**
     * Сгенерировать уникальный ключ для товара
     */
    private function generateItemKey(int $productId, ?int $variantId = null, array $additionalData = [], ?string $setGroup = null): string
    {
        $keyParts = [(string) $productId];

        if ($variantId !== null) {
            $keyParts[] = 'v'.$variantId;
        }

        if (! empty($additionalData)) {
            $keyParts[] = md5(json_encode($additionalData));
        }

        if ($setGroup !== null) {
            $keyParts[] = $setGroup;
        }

        return implode('_', $keyParts);
    }
}
