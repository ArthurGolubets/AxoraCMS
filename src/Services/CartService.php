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
            ];
        }

        $this->saveCart($cart);

        return $cart[$itemKey];
    }

    /**
     * Удалить товар из корзины
     */
    public function removeFromCart(string $itemKey): bool
    {
        $cart = $this->getCart();

        if (isset($cart[$itemKey])) {
            unset($cart[$itemKey]);
            $this->saveCart($cart);
            return true;
        }

        return false;
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
    private function generateItemKey(int $productId, ?int $variantId = null, array $additionalData = []): string
    {
        $keyParts = [(string) $productId];

        if ($variantId !== null) {
            $keyParts[] = 'v' . $variantId;
        }

        if (!empty($additionalData)) {
            $keyParts[] = md5(json_encode($additionalData));
        }

        return implode('_', $keyParts);
    }
}
