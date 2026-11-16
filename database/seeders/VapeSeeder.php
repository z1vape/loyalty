<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Store;

class VapeSeeder extends Seeder
{
    public function run(): void
    {
        // Find or create store
        $store = Store::firstOrCreate(
            ['name' => 'Toko Vape Z1'],
            ['address' => 'Jl. Vaping Utama No. 88, Bandung', 'phone' => '0812-3456-7890']
        );

        // Settings categories and variants
        $categories = ['Pod System', 'Box Mod', 'E-Liquid SaltNic', 'E-Liquid Freebase', 'Coil & Cartridge', 'Accessories'];
        foreach ($categories as $cat) {
            Setting::firstOrCreate([
                'type' => 'category',
                'value' => $cat,
                'store_id' => $store->id,
            ]);
        }

        $variants = ['Default', '3mg 60ml', '6mg 60ml', '30mg 30ml', '0.6 Ohm', '0.2 Ohm', 'Black Chrome', 'Neon Purple'];
        foreach ($variants as $v) {
            Setting::firstOrCreate([
                'type' => 'variant',
                'value' => $v,
                'store_id' => $store->id,
            ]);
        }

        // Vape Products Data
        $vapeProducts = [
            // Pod Systems
            [
                'sku' => 'VAP-POD-001',
                'name' => 'OXVA Xlim Pro 2 Pod Kit 1000mAh',
                'description' => 'Pod kit flagship dengan layar HD, daya 30W, baterai 1000mAh, dan top-fill cartridge teknologi terbaru anti bocor.',
                'purchase_price' => 250000,
                'selling_price' => 320000,
                'stock' => 24,
                'min_stock' => 5,
                'category' => 'Pod System',
                'variant' => 'Neon Purple',
                'store_id' => $store->id,
            ],
            [
                'sku' => 'VAP-POD-002',
                'name' => 'Uwell Caliburn G3 Pod Kit',
                'description' => 'Performa rasa maksimal dengan dual airflow system, OLED display, 900mAh battery, & kapasitas cartridge 2.5ml.',
                'purchase_price' => 220000,
                'selling_price' => 285000,
                'stock' => 18,
                'min_stock' => 5,
                'category' => 'Pod System',
                'variant' => 'Black Chrome',
                'store_id' => $store->id,
            ],
            [
                'sku' => 'VAP-POD-003',
                'name' => 'Vaporesso XROS 4 Mini Pod Kit',
                'description' => 'Desain compact aluminum alloy, COREX 2.0 heating technology, baterai 1000mAh dengan fast charging Type-C.',
                'purchase_price' => 200000,
                'selling_price' => 260000,
                'stock' => 3,
                'min_stock' => 5,
                'category' => 'Pod System',
                'variant' => 'Default',
                'store_id' => $store->id,
            ],
            [
                'sku' => 'VAP-POD-004',
                'name' => 'Voopoo Argus P2 Pod Kit',
                'description' => 'Futuristic cyber design dengan power mode adjustment, 1100mAh battery, & RGB LED indicator.',
                'purchase_price' => 240000,
                'selling_price' => 310000,
                'stock' => 0,
                'min_stock' => 5,
                'category' => 'Pod System',
                'variant' => 'Default',
                'store_id' => $store->id,
            ],

            // Box Mods
            [
                'sku' => 'VAP-MOD-001',
                'name' => 'Geekvape L200 (Aegis Legend 2) Box Mod',
                'description' => 'Legendary IP68 rating tri-proof mod (water, dust, shock proof), dual 18650, output maksimal 200W.',
                'purchase_price' => 520000,
                'selling_price' => 650000,
                'stock' => 12,
                'min_stock' => 3,
                'category' => 'Box Mod',
                'variant' => 'Default',
                'store_id' => $store->id,
            ],
            [
                'sku' => 'VAP-MOD-002',
                'name' => 'Lost Vape Centaurus M200 Mod',
                'description' => 'Desain premium aluminum alloy dengan tactile dial button 3-in-1 switch, dual 18650 battery, 200W output.',
                'purchase_price' => 460000,
                'selling_price' => 580000,
                'stock' => 8,
                'min_stock' => 2,
                'category' => 'Box Mod',
                'variant' => 'Default',
                'store_id' => $store->id,
            ],

            // E-Liquids
            [
                'sku' => 'VAP-LIQ-001',
                'name' => 'Oat Drips V1 Original 60ml 3mg',
                'description' => 'Liquid freebase legendaris rasa sereal gandum manis lembut dipadu susu oat gurih nikmat.',
                'purchase_price' => 110000,
                'selling_price' => 145000,
                'stock' => 45,
                'min_stock' => 10,
                'category' => 'E-Liquid Freebase',
                'variant' => '3mg 60ml',
                'store_id' => $store->id,
            ],
            [
                'sku' => 'VAP-LIQ-002',
                'name' => 'Liquid Lunar Sweet Mango 30ml 30mg',
                'description' => 'Liquid SaltNic rasa mangga harum manis dingin menyegarkan dengan throat hit yang pas.',
                'purchase_price' => 80000,
                'selling_price' => 110000,
                'stock' => 30,
                'min_stock' => 8,
                'category' => 'E-Liquid SaltNic',
                'variant' => '30mg 30ml',
                'store_id' => $store->id,
            ],
            [
                'sku' => 'VAP-LIQ-003',
                'name' => 'Ala Carte Banana Cream 60ml 6mg',
                'description' => 'Freebase dessert e-liquid rasa banana pastry puding pisang creamy yang manis gurih.',
                'purchase_price' => 115000,
                'selling_price' => 150000,
                'stock' => 4,
                'min_stock' => 5,
                'category' => 'E-Liquid Freebase',
                'variant' => '6mg 60ml',
                'store_id' => $store->id,
            ],
            [
                'sku' => 'VAP-LIQ-004',
                'name' => 'Foom Salt Ice Tea 30ml 30mg',
                'description' => 'SaltNic rasa es teh manis klasik menyegarkan khas Indonesia dengan sensasi dingin mantap.',
                'purchase_price' => 85000,
                'selling_price' => 115000,
                'stock' => 50,
                'min_stock' => 10,
                'category' => 'E-Liquid SaltNic',
                'variant' => '30mg 30ml',
                'store_id' => $store->id,
            ],
            [
                'sku' => 'VAP-LIQ-005',
                'name' => 'Golden Ticket Chocolate Milk 60ml 3mg',
                'description' => 'Freebase creamy susu cokelat murni kaya rasa yang memanjakan lidah setiap puff.',
                'purchase_price' => 105000,
                'selling_price' => 140000,
                'stock' => 22,
                'min_stock' => 5,
                'category' => 'E-Liquid Freebase',
                'variant' => '3mg 60ml',
                'store_id' => $store->id,
            ],

            // Coils & Accessories
            [
                'sku' => 'VAP-ACC-001',
                'name' => 'OXVA Xlim Top Fill Cartridge 0.6 Ohm',
                'description' => 'Cartridge 2ml top fill anti leak untuk pod OXVA Xlim series, memberikan kejelasan rasa yang sempurna.',
                'purchase_price' => 30000,
                'selling_price' => 42000,
                'stock' => 60,
                'min_stock' => 15,
                'category' => 'Coil & Cartridge',
                'variant' => '0.6 Ohm',
                'store_id' => $store->id,
            ],
            [
                'sku' => 'VAP-ACC-002',
                'name' => 'Caliburn G3 Integrated Coil Cartridge 0.6 Ohm',
                'description' => 'Cartridge pengganti Uwell Caliburn G3 dengan mesh coil terintegrasi presisi tinggi.',
                'purchase_price' => 32000,
                'selling_price' => 45000,
                'stock' => 40,
                'min_stock' => 10,
                'category' => 'Coil & Cartridge',
                'variant' => '0.6 Ohm',
                'store_id' => $store->id,
            ],
            [
                'sku' => 'VAP-ACC-003',
                'name' => 'Cotton Bacon Prime Organic Cotton',
                'description' => 'Kapas organik murni khusus vape buatan USA, tanpa pemutih, menyerap e-liquid 33% lebih cepat.',
                'purchase_price' => 45000,
                'selling_price' => 65000,
                'stock' => 35,
                'min_stock' => 8,
                'category' => 'Accessories',
                'variant' => 'Default',
                'store_id' => $store->id,
            ],
            [
                'sku' => 'VAP-ACC-004',
                'name' => 'Geekvape Z Series Mesh Coil 0.2 Ohm',
                'description' => 'Mesh replacement coil untuk Geekvape Zeus Sub-Ohm tank, uap tebal flavor manis tajam.',
                'purchase_price' => 35000,
                'selling_price' => 50000,
                'stock' => 0,
                'min_stock' => 5,
                'category' => 'Coil & Cartridge',
                'variant' => '0.2 Ohm',
                'store_id' => $store->id,
            ]
        ];

        foreach ($vapeProducts as $prodData) {
            Product::updateOrCreate(
                ['sku' => $prodData['sku']],
                $prodData
            );
        }
    }
}
