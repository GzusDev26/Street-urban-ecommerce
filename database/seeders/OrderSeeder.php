<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first() ?? User::create([
            'name' => 'Cliente Prueba',
            'email' => 'cliente@example.com',
            'password' => bcrypt('password'),
        ]);

        $products = Product::take(3)->get();

        for ($i = 1; $i <= 3; $i++) {
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => 'ORD-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'status' => ['pending', 'confirmed', 'shipped'][$i - 1],
                'total' => 0,
                'tax' => 0,
                'shipping' => 5.00,
                'shipping_address' => 'Calle Principal 123, Ciudad',
                'billing_address' => 'Calle Principal 123, Ciudad',
                'payment_method' => 'credit_card',
            ]);

            $total = 0;
            foreach ($products as $product) {
                $qty = random_int(1, 3);
                $subtotal = $product->price * $qty;
                $total += $subtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $product->price,
                    'subtotal' => $subtotal,
                ]);
            }

            $order->update(['total' => $total + 5.00]);
        }
    }
}
