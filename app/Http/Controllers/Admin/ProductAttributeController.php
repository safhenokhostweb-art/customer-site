<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductAttributeController extends Controller
{
    public function index()
    {
        $attributes = ProductAttribute::withCount('values')->orderBy('sort_order')->get();

        return view('admin.product-attributes.index', compact('attributes'));
    }

    public function create()
    {
        return view('admin.product-attributes.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'filterable' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['filterable'] = $request->boolean('filterable', true);
        $data['sort_order'] = $data['sort_order'] ?? ProductAttribute::max('sort_order') + 1;
        ProductAttribute::create($data);

        return redirect()->route('admin.product-attributes.index')->with('success', 'ویژگی ذخیره شد.');
    }

    public function edit(ProductAttribute $productAttribute)
    {
        $productAttribute->load('values');

        return view('admin.product-attributes.edit', compact('productAttribute'));
    }

    public function update(Request $request, ProductAttribute $productAttribute)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'filterable' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['filterable'] = $request->boolean('filterable', true);
        $productAttribute->update($data);

        $rows = (array) $request->input('values', []);
        $keep = [];
        foreach ($rows as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            $val = trim((string) ($row['value'] ?? ''));
            if ($val === '') {
                continue;
            }
            $slug = trim((string) ($row['slug'] ?? '')) ?: Str::slug($val);
            $payload = [
                'value' => $val,
                'slug' => $slug,
                'sort_order' => (int) ($row['sort_order'] ?? $i),
            ];
            $vid = isset($row['id']) ? (int) $row['id'] : 0;
            if ($vid > 0) {
                $v = ProductAttributeValue::query()->where('product_attribute_id', $productAttribute->id)->whereKey($vid)->first();
                if ($v) {
                    $v->update($payload);
                    $keep[] = $v->id;
                }
            } else {
                $keep[] = ProductAttributeValue::query()->create(array_merge($payload, [
                    'product_attribute_id' => $productAttribute->id,
                ]))->id;
            }
        }
        ProductAttributeValue::query()->where('product_attribute_id', $productAttribute->id)->whereNotIn('id', $keep)->delete();

        return redirect()->route('admin.product-attributes.index')->with('success', 'ویژگی به‌روزرسانی شد.');
    }

    public function destroy(ProductAttribute $productAttribute)
    {
        $productAttribute->delete();

        return redirect()->route('admin.product-attributes.index')->with('success', 'ویژگی حذف شد.');
    }
}
