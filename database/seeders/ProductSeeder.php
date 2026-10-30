<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seeded catalog, quoted in USD (the reference currency).
     *
     * Fixed values instead of factory randomness: `products_name_unique` makes
     * `name` the natural key, and a random price on every run would mutate the
     * existing rows through `updateOrCreate` and invalidate the derived
     * `product_prices`.
     *
     * Rates mix the 16% ceiling, a lower bracket and 0 (tax exempt), so the
     * catalog exercises the whole `tax_cost` range the API has to expose.
     *
     * Each row is [name, description, price, tax_rate].
     *
     * @var list<array{0: string, 1: string, 2: float, 3: float}>
     */
    public const CATALOG = [
        ['Wireless Headphones', 'Over-ear Bluetooth headphones with active noise cancellation.', 129.99, 0.16],
        ['Mechanical Keyboard', 'Tenkeyless mechanical keyboard with hot-swappable switches.', 89.50, 0.16],
        ['USB-C Hub', 'Seven-port hub with HDMI, card reader and power delivery.', 45.00, 0.16],
        ['Portable SSD 1TB', 'External solid state drive with USB 3.2 Gen 2 interface.', 159.00, 0.08],
        ['Desk Lamp', 'Dimmable LED lamp with adjustable colour temperature.', 34.75, 0.00],
        ['Webcam 4K', 'Ultra HD webcam with dual microphones and privacy shutter.', 219.00, 0.16],
        ['Laptop Stand', 'Aluminium stand with six levels of height adjustment.', 59.90, 0.16],
        ['External Monitor 27"', '27-inch QHD monitor with 165 Hz refresh rate.', 549.00, 0.00],
        ['Wireless Mouse', 'Ergonomic mouse with silent switches and USB-C charging.', 39.99, 0.16],
        ['Trackball Mouse', 'Finger-operated trackball with adjustable DPI.', 74.50, 0.16],
        ['Vertical Mouse', 'Vertical grip mouse designed to reduce wrist strain.', 49.90, 0.16],
        ['Gaming Mouse', 'Lightweight mouse with a 16000 DPI optical sensor.', 69.00, 0.16],
        ['Mouse Pad XL', 'Extended cloth pad with stitched anti-fray edges.', 24.99, 0.16],
        ['Desk Mat Leather', 'Full-desk leather mat with non-slip backing.', 54.00, 0.08],
        ['Wrist Rest', 'Memory foam wrist rest for mechanical keyboards.', 19.50, 0.16],
        ['Monitor Arm', 'Gas-spring single monitor arm up to 32 inches.', 119.00, 0.16],
        ['Dual Monitor Arm', 'Gas-spring arm supporting two monitors side by side.', 179.00, 0.16],
        ['Laptop Sleeve 14"', 'Water-resistant padded sleeve with accessory pocket.', 29.99, 0.08],
        ['Laptop Backpack', 'Anti-theft backpack with a padded 16-inch compartment.', 89.00, 0.16],
        ['Cable Organizer Kit', 'Set of reusable ties, sleeves and adhesive clips.', 14.99, 0.16],
        ['Braided USB-C Cable', 'Two-metre 100 W braided charging cable.', 17.50, 0.16],
        ['HDMI Cable 2m', 'Certified 8K HDMI 2.1 cable with gold connectors.', 21.00, 0.16],
        ['DisplayPort Cable', 'Two-metre DisplayPort 1.4 cable, 8K ready.', 23.00, 0.16],
        ['USB-C Dock', 'Docking station with dual 4K output and Ethernet.', 189.00, 0.16],
        ['Thunderbolt Dock', 'Thunderbolt 4 dock with 96 W power delivery.', 299.00, 0.00],
        ['Power Strip', 'Six-outlet surge protector with USB ports.', 32.50, 0.16],
        ['Charging Station', 'Three-device wireless charging station.', 79.00, 0.16],
        ['USB Wall Charger 65W', 'Compact GaN charger with two USB-C ports.', 42.00, 0.16],
        ['Laptop Cooler Pad', 'Five-fan cooling pad with adjustable tilt.', 47.99, 0.16],
        ['Silent Fan 120mm', 'PWM case fan with fluid dynamic bearing.', 18.50, 0.16],
        ['Thermal Paste', 'High-conductivity paste for CPU and GPU.', 12.99, 0.16],
        ['CPU Air Cooler', 'Tower cooler with six heat pipes and a 120mm fan.', 65.00, 0.16],
        ['Mid Tower Case', 'ATX case with tempered glass and mesh front.', 109.00, 0.16],
        ['SFX Power Supply 650W', 'Modular 80 Plus Gold power supply.', 129.00, 0.16],
        ['Bluetooth Speaker', 'Portable waterproof speaker with 20-hour battery.', 89.99, 0.16],
        ['Soundbar', 'Two-channel soundbar with optical and HDMI input.', 149.00, 0.08],
        ['Studio Microphone', 'Cardioid condenser microphone with shock mount.', 139.00, 0.00],
        ['Microphone Arm', 'Spring-loaded boom arm with cable management.', 58.00, 0.16],
        ['Audio Interface', 'Two-channel USB audio interface with phantom power.', 179.00, 0.16],
        ['Headphone Amp', 'Desktop DAC and headphone amplifier.', 199.00, 0.00],
        ['In-Ear Monitors', 'Dual-driver in-ear monitors with detachable cable.', 99.50, 0.16],
        ['Earbuds Case', 'Hard shell case with a carabiner clip.', 15.99, 0.16],
        ['Webcam 1080p', 'Full HD webcam with auto light correction.', 79.00, 0.16],
        ['Ring Light', 'Ten-inch LED ring light with tripod stand.', 44.00, 0.16],
        ['Green Screen', 'Collapsible chroma key panel with travel bag.', 69.00, 0.08],
        ['Capture Card', 'HDMI capture card for 1080p60 streaming.', 129.00, 0.16],
        ['Stream Deck Mini', 'Six-key programmable controller for streaming.', 99.00, 0.16],
        ['Tablet Stand', 'Adjustable aluminium stand for tablets up to 13 inches.', 36.00, 0.16],
        ['Phone Holder', 'Foldable desk phone holder with a cable slot.', 12.50, 0.16],
        ['Travel Adapter', 'Universal travel adapter with two USB ports.', 26.99, 0.08],
        ['Ethernet Switch 8 Port', 'Unmanaged gigabit switch with metal housing.', 39.00, 0.16],
        ['Wi-Fi Router', 'Dual-band router with mesh support.', 149.99, 0.16],
        ['Wi-Fi Extender', 'Wall-plug range extender with a gigabit port.', 54.00, 0.16],
        ['Network Cable 5m', 'Cat 6 flat patch cable with snagless connectors.', 13.99, 0.16],
        ['NAS Enclosure', 'Two-bay network storage enclosure.', 219.00, 0.00],
        ['External HDD 4TB', 'USB 3.0 desktop drive with backup software.', 109.00, 0.16],
        ['MicroSD Card 128GB', 'UHS-I card with adapter for 4K video.', 24.00, 0.16],
        ['USB Flash Drive 64GB', 'Metal-body drive with a keyring loop.', 16.00, 0.16],
        ['Card Reader', 'USB-C reader for SD, microSD and CF cards.', 27.50, 0.16],
        ['Keyboard Cover', 'Silicone dust cover for tenkeyless keyboards.', 11.99, 0.16],
        ['Screen Cleaner Kit', 'Spray, microfibre cloth and brush set.', 13.50, 0.00],
        ['Document Scanner', 'Portable A4 scanner with OCR software.', 159.00, 0.00],
        ['Label Printer', 'Thermal label printer with Bluetooth.', 119.00, 0.16],
    ];

    /**
     * Seeds the catalog above in USD.
     *
     * Must run after CurrencySeeder: `currency_id` points at the seeded USD row
     * and ProductPriceSeeder converts from these base prices using the rates the
     * currency catalog publishes.
     *
     * Idempotent by `name`, which `products_name_unique` now enforces: the seeder
     * updates in place instead of letting a duplicate through. `tax_cost` is not
     * computed here - ProductFactory derives the amount from the final price and
     * the applied rate, so the arithmetic lives in one place.
     */
    public function run(): void
    {
        foreach (self::CATALOG as [$name, $description, $price, $taxRate]) {
            $product = Product::factory()
                ->priced($price)
                ->taxable($taxRate)
                ->make([
                    'name' => $name,
                    'description' => $description,
                ]);

            Product::query()->updateOrCreate(
                ['name' => $product->name],
                [
                    'description' => $product->description,
                    'price' => $product->price,
                    'currency_id' => $product->currency_id,
                    'tax_cost' => $product->tax_cost,
                    'manufacturing_cost' => $this->manufacturingCost((float) $product->price),
                ],
            );
        }
    }

    /**
     * Deterministic cost basis: a fixed share of the sale price, so re-seeding
     * never rewrites the column with a new random margin.
     */
    private function manufacturingCost(float $price): string
    {
        return number_format(round($price * 0.45, 2), 4, '.', '');
    }
}
