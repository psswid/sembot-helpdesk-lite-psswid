<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as FakerFactory;

class TicketSeeder extends Seeder
{
    /**
     * Seed the tickets table with realistic data.
     */
    public function run(): void
    {
        $faker = FakerFactory::create('en_US');

        $reporter = User::where('email', 'reporter@example.com')->first();
        $agent = User::where('email', 'agent@example.com')->first();

        if (! $reporter) {
            // If reporter is missing (unlikely), fall back to any user
            $reporter = User::query()->first();
        }
        $priorities = ['low', 'medium', 'high'];
        $statuses = ['open', 'in_progress', 'resolved', 'closed'];
        $tagPool = ['bug', 'feature', 'ui', 'backend', 'urgent', 'payment', 'shipping', 'order', 'refund', 'inventory'];

        $templates = [
            [
                'title' => 'Order {order} not received',
                'priority' => 'high',
                'tags' => ['order', 'shipping'],
                'description' => "Customer placed order {order} for {product} but order status remains 'processing' and no tracking number was assigned. Expected: shipment with tracking. Reproduction steps: 1) Place order 2) Check order history 3) No tracking shown. Customer email: {email}.",
            ],
            [
                'title' => 'Payment declined on checkout (Order {order})',
                'priority' => 'high',
                'tags' => ['payment', 'order'],
                'description' => "Payment failed during checkout for order {order}. Payment gateway returned 'declined'. Card ending with {last4}. Customer reports funds available. Steps: attempt checkout > decline. Gateway reference: {gw_ref}.",
            ],
            [
                'title' => 'Unable to apply discount code "{code}"',
                'priority' => 'medium',
                'tags' => ['feature', 'order'],
                'description' => "Customer attempted to apply discount code {code} on product {product} but discount was not applied. Expected: price reduced by discount. Steps: add product to cart > apply code > total unchanged. Affected user: {email}.",
            ],
            [
                'title' => 'Refund not processed for returned item SKU {sku}',
                'priority' => 'high',
                'tags' => ['refund', 'order'],
                'description' => "Return received for SKU {sku} (order {order}) but refund has not been issued after 7 days. Expected: refund to original payment method. Return tracking: {return_tracking}.",
            ],
            [
                'title' => 'Checkout throws 500 error when submitting address',
                'priority' => 'high',
                'tags' => ['bug', 'backend', 'order'],
                'description' => "When submitting shipping address during checkout the server returns HTTP 500. Error occurs intermittently. Example payload: {payload_snippet}. Steps: fill address > submit > 500. Affected environment: production.",
            ],
            [
                'title' => 'Inventory mismatch for SKU {sku}',
                'priority' => 'medium',
                'tags' => ['inventory', 'backend'],
                'description' => "Product {product} shows stock 0 in storefront but warehouse reports 12 available. SKU: {sku}. Expected: consistent inventory across systems. Last sync: {last_sync}.",
            ],
            [
                'title' => 'Product images missing on product page {product}',
                'priority' => 'low',
                'tags' => ['ui', 'frontend'],
                'description' => "Several images for product {product} are not loading (404) on the product detail page. Affected browsers: Chrome/Firefox. Example image path: {image_path}.",
            ],
            [
                'title' => 'Subscription renewal failed for customer {email}',
                'priority' => 'high',
                'tags' => ['payment', 'subscription'],
                'description' => "Automated renewal for subscription id {sub_id} failed with gateway error 'insufficient_funds'. Customer notified but asks for manual retry. Recent invoices: {invoice_list}.",
            ],
            [
                'title' => 'Slow page load on category {category}',
                'priority' => 'medium',
                'tags' => ['performance', 'frontend'],
                'description' => "Category page {category} takes >6s to load for some users. Lighthouse shows large JS bundle. Steps to reproduce: visit category > measure load time. Affected segments: mobile users on 3G.",
            ],
            [
                'title' => 'Admin: Add new shipping zone for {country}',
                'priority' => 'low',
                'tags' => ['feature', 'admin'],
                'description' => "Request to add a new shipping zone for {country} with rates: {rates}. Reason: expanding delivery options for region. Example rate config: {rate_example}.",
            ],
        ];

        $cities = [
            'New York, NY', 'Los Angeles, CA', 'Chicago, IL', 'Houston, TX', 'Phoenix, AZ',
            'Philadelphia, PA', 'San Antonio, TX', 'San Diego, CA', 'Dallas, TX', 'San Jose, CA',
            'Austin, TX', 'Jacksonville, FL', 'Fort Worth, TX', 'Columbus, OH', 'Charlotte, NC',
            'San Francisco, CA', 'Indianapolis, IN', 'Seattle, WA', 'Denver, CO', 'Washington, DC',
            'Boston, MA', 'El Paso, TX', 'Nashville, TN', 'Detroit, MI', 'Oklahoma City, OK'
        ];

        $rows = [];
        $count = 12; // create a dozen realistic tickets

        for ($i = 0; $i < $count; $i++) {
            $tpl = $faker->randomElement($templates);

            $order = $faker->unique()->bothify('ORD-####');
            $product = ucfirst($faker->words($faker->numberBetween(1, 3), true));
            $email = $faker->safeEmail();
            $sku = strtoupper($faker->bothify('SKU-???###'));
            $last4 = $faker->numerify('####');
            $gwRef = strtoupper($faker->bothify('GWREF-####'));
            $code = strtoupper($faker->bothify('SAVE###'));
            $returnTracking = strtoupper($faker->bothify('RT-######'));
            $payload = '{"address":"' . $faker->streetAddress() . '"}';
            $lastSync = now()->subDays($faker->numberBetween(1, 10))->toDateString();
            $imagePath = '/storage/products/' . $faker->numberBetween(100, 999) . '/image.jpg';
            $subId = 'SUB-' . $faker->bothify('####');
            $invoiceList = json_encode([
                ['id' => $faker->bothify('INV-####'), 'amount' => $faker->randomFloat(2, 10, 200)],
            ]);
            $category = ucfirst($faker->word());
            $country = $faker->country();
            $rates = json_encode(['standard' => '10.00', 'express' => '25.00']);
            $rateExample = 'standard:10,express:25';

            $description = strtr($tpl['description'], [
                '{order}' => $order,
                '{product}' => $product,
                '{email}' => $email,
                '{sku}' => $sku,
                '{last4}' => $last4,
                '{gw_ref}' => $gwRef,
                '{code}' => $code,
                '{return_tracking}' => $returnTracking,
                '{payload_snippet}' => $payload,
                '{last_sync}' => $lastSync,
                '{image_path}' => $imagePath,
                '{sub_id}' => $subId,
                '{invoice_list}' => $invoiceList,
                '{category}' => $category,
                '{country}' => $country,
                '{rates}' => $rates,
                '{rate_example}' => $rateExample,
            ]);

            $tags = array_merge($tpl['tags'], $faker->randomElements($tagPool, $faker->numberBetween(0, 2)));

            $rows[] = [
                'title' => $tpl['title'] ? strtr($tpl['title'], ['{order}' => $order, '{code}' => $code, '{sku}' => $sku, '{product}' => $product, '{email}' => $email, '{category}' => $category, '{country}' => $country]) : ucfirst($faker->words($faker->numberBetween(3, 6), true)),
                'description' => $description,
                'priority' => $tpl['priority'] ?? $faker->randomElement($priorities),
                'status' => $faker->randomElement($statuses),
                'location' => $faker->randomElement($cities),
                'assignee_id' => $faker->boolean(70) && $agent ? $agent->id : null,
                'reporter_id' => $reporter?->id,
                'tags' => $tags ? json_encode(array_values($tags)) : null,
                'created_at' => now()->subDays($faker->numberBetween(0, 20)),
                'updated_at' => now(),
            ];
        }

        DB::table('tickets')->insert($rows);
    }
}
