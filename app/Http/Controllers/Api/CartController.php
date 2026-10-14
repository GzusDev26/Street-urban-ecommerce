<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CartItemRequest;
use App\Http\Requests\CartLinesRequest;
use App\Http\Requests\ConfirmCartRequest;
use App\Exceptions\CartStockException;
use App\Models\Customer;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutService $checkoutService,
    )
    {
    }

    public function validateCart(CartLinesRequest $request): JsonResponse
    {
        return response()->json($this->cartService->validateLines($request->validated('items')));
    }

    public function show(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        return response()->json($this->cartService->syncCustomerCart($customer, []));
    }

    public function sync(CartLinesRequest $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        return response()->json($this->cartService->syncCustomerCart($customer, $request->validated('items')));
    }

    public function store(CartItemRequest $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        return response()->json($this->cartService->add(
            $customer,
            (int) $request->validated('variant_id'),
            (int) $request->validated('quantity'),
        ));
    }

    public function update(CartItemRequest $request, int $variant): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        if ((int) $request->validated('variant_id') !== $variant) {
            return response()->json(['message' => 'La variante no coincide con la línea del carrito.'], 422);
        }

        return response()->json($this->cartService->update(
            $customer,
            $variant,
            (int) $request->validated('quantity'),
        ));
    }

    public function destroy(Request $request, int $variant): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        return response()->json($this->cartService->remove($customer, $variant));
    }

    public function confirm(ConfirmCartRequest $request): JsonResponse
    {
        try {
            return response()->json(
                $this->checkoutService->confirm($request->user('customer'), $request->validated()),
                201,
            );
        } catch (CartStockException $exception) {
            return response()->json([
                'valid' => false,
                'errors' => $exception->errors,
                'message' => $exception->getMessage(),
            ], 409);
        }
    }
}
