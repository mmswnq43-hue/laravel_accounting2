<?php

namespace App\Console\Commands;

use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Purchase;
use App\Support\InventoryMovementService;
use Illuminate\Console\Command;

class RebuildInventoryCommand extends Command
{
    protected $signature = 'inventory:rebuild';
    protected $description = 'Rebuild inventory movements and product stock quantities from transaction history';

    public function handle(InventoryMovementService $service): int
    {
        if (!$this->confirm('This will truncate inventory_movements and rebuild all stock levels. Do you want to proceed?')) {
            return 1;
        }

        $this->info('Clearing old movements...');
        InventoryMovement::truncate();

        $this->info('Syncing Purchases...');
        $purchases = Purchase::all();
        foreach ($purchases as $purchase) {
            if (in_array($purchase->status, ['pending', 'approved', 'partial', 'paid'])) {
                $this->line(" - Syncing Purchase #{$purchase->purchase_number}");
                $service->syncPurchase($purchase);
            }
        }

        $this->info('Syncing Invoices...');
        $invoices = Invoice::all();
        foreach ($invoices as $invoice) {
            if ($invoice->status !== 'draft' && $invoice->status !== 'cancelled') {
                $this->line(" - Syncing Invoice #{$invoice->invoice_number}");
                $service->syncInvoice($invoice);
            }
        }

        $this->info('Rebuilding Product Stock Quantities...');
        $products = Product::where('type', 'product')->get();
        foreach ($products as $product) {
            $in = InventoryMovement::where('product_id', $product->id)->where('direction', 'in')->sum('quantity');
            $out = InventoryMovement::where('product_id', $product->id)->where('direction', 'out')->sum('quantity');
            $newStock = $in - $out;

            $this->line(" - Product: {$product->name}, In: {$in}, Out: {$out}, New Stock: {$newStock}");
            $product->update(['stock_quantity' => $newStock]);
        }

        $this->info('Inventory rebuilt successfully!');
        return 0;
    }
}
