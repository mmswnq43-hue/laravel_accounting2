<?php

namespace App\Imports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductsImport implements ToCollection, WithHeadingRow
{
    private int $companyId;
    public int $importedCount = 0;
    public int $updatedCount = 0;

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Ensure necessary fields exist in the row, using Arabic or English headers
            $code = $row['code'] ?? $row['الرمز'] ?? $row['الكود'] ?? null;
            $name = $row['name'] ?? $row['الاسم'] ?? $row['اسم_المنتج'] ?? null;
            
            if (empty($name)) {
                continue; // Skip without name
            }

            $type = $this->determineType($row['type'] ?? $row['النوع'] ?? 'product');
            $costPrice = (float) ($row['cost_price'] ?? $row['سعر_التكلفة'] ?? 0);
            $sellingPrice = (float) ($row['selling_price'] ?? $row['سعر_البيع'] ?? 0);
            
            // Try to find by code first
            $product = null;
            if (!empty($code)) {
                $product = Product::where('company_id', $this->companyId)->where('code', $code)->first();
            }

            if ($product) {
                // Update
                $product->update([
                    'name' => $name,
                    'type' => $type,
                    'cost_price' => $costPrice,
                    'selling_price' => $sellingPrice,
                ]);
                $this->updatedCount++;
            } else {
                // Create
                $isSystem = strtolower($name) === 'خصم مكتسب' || strtolower($name) === 'خصم مسموح به';
                
                Product::create([
                    'company_id' => $this->companyId,
                    'code' => $code,
                    'name' => $name,
                    'type' => $type,
                    'cost_price' => $costPrice,
                    'selling_price' => $sellingPrice,
                    'is_active' => true,
                    'is_system' => $isSystem,
                ]);
                $this->importedCount++;
            }
        }
    }

    private function determineType(?string $rawType): string
    {
        $rawType = strtolower(trim((string) $rawType));
        if (in_array($rawType, ['service', 'خدمة', 'خدمات'])) {
            return 'service';
        }
        return 'product';
    }
}
