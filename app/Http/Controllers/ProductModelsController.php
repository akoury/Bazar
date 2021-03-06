<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Attribute;
use App\Models\ProductModel;
use App\Http\Requests\ProductRequest;

class ProductModelsController extends Controller
{
    public function index($brandId)
    {
        $brand = Brand::findOrFail($brandId);
        $models = $brand->models()->wherePublished(true)->with('products')->get();

        return view('product-models.index', compact('brand', 'models'));
    }

    public function create($id)
    {
        $brand = auth()->user()->brands()->findOrFail($id);

        $attributes = Attribute::with('values')->get();

        return view('product-models.create', compact('brand', 'attributes'));
    }

    public function store($brandId, ProductRequest $request)
    {
        $brand = auth()->user()->brands()->findOrFail($brandId);

        $model = ProductModel::create([
            'name'        => $request->name,
            'description' => $request->description,
            'published'   => $request->published,
            'brand_id'    => $brand->id
        ]);

        $request->addProductsToModel($model);

        return response()->json($model->url(), 201);
    }

    public function edit($id)
    {
        $model = ProductModel::withRelationships($id);

        auth()->user()->brands()->findOrFail($model->brand_id);

        $attributes = Attribute::with('values')->get();

        $urls = $model->getMedia()->map->getUrl();

        return view('product-models.edit', compact('model', 'attributes', 'urls'));
    }

    public function update($id, ProductRequest $request)
    {
        $model = ProductModel::findOrFail($id);

        auth()->user()->brands()->findOrFail($model->brand_id);

        $model->update([
            'name'        => $request->name,
            'description' => $request->description,
            'published'   => $request->published,
        ]);

        $request->addProductsToModel($model);

        return response()->json($model->url(), 200);
    }

    public function destroy($id)
    {
        $model = ProductModel::findOrFail($id);

        auth()->user()->brands()->findOrFail($model->brand_id);

        $model->products->map(function ($product) {
            $product->setItemsRemaining(0);
            $product->delete();
        });

        $model->published = false;
        $model->save();

        $model->delete();

        return response()->json(route('product-models.index', $model->brand_id), 200);
    }
}
