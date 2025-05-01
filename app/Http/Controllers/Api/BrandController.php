<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\BrandApiResource;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    //
    public function index(Request $request)
    {
        $categories = Brand::withCount(['cosmetics']);

        if ($request->has('limit')) {
            $categories->limit($request->input('limit'));
        }

        return BrandApiResource::collection($categories->get());
    }

    public function show(Brand $category)
    {
        $category->load(['cosmetics', 'popularCosmetics']);
        $category->loadCount(['cosmetics',]);

        return new BrandApiResource($category);
    }
}
