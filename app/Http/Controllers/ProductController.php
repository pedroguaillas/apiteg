<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResources;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\ChartAccount;
use App\Category;
use App\Company;
use App\Product;
use App\Unity;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        $products = Product::leftJoin('categories', 'categories.id', 'products.category_id')
            ->leftJoin('unities', 'unities.id', 'products.unity_id')
            ->where('products.branch_id', $branch->id)
            ->select('products.*', 'categories.category', 'unities.unity');

        return ProductResources::collection($products->paginate());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create()
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        $unities = Unity::where('branch_id', $branch->id)->get();
        $categories = Category::where('branch_id', $branch->id)->get();
        //Falta restringir que el plan de cuentas sea solo de esa compania
        $accounts = ChartAccount::where('type', $company->type)->get();

        return response()->json([
            'unities' => $unities,
            'categories' => $categories,
            'accounts' => $accounts
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        try {
            // $product = Product::create($request->all());
            $product = $branch->products()->create($request->all());
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                return response()->json(['message' => 'KEY_DUPLICATE'], 405);
            }
        }
    }

    public function import(Request $request)
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        $products = $request->get('products');

        $newProducts = [];
        foreach ($products as $product) {
            array_push($newProducts, [
                'code' => $product['code'],
                'type_product' => $product['type_product'],
                'name' => $product['name'],
                'unity_id' => strlen($product['unity_id']) ? $product['unity_id'] : null,
                'price1' => strlen($product['price1']) ? $product['price1'] : null,
                'price2' => strlen($product['price2']) ? $product['price2'] : null,
                'price3' => strlen($product['price3']) ? $product['price3'] : null,
                'iva' => $product['iva']
            ]);
        }
        $product = $company->branches->first()->products()->createMany($newProducts);

        $products = Product::leftJoin('categories', 'categories.id', 'products.category_id')
            ->leftJoin('unities', 'unities.id', 'products.unity_id')
            ->where('products.branch_id', $branch->id)
            ->select('products.*', 'categories.category', 'unities.unity');

        return ProductResources::collection($products->latest()->paginate());
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        $unities = Unity::where('branch_id', $branch->id)->get();
        $categories = Category::where('branch_id', $branch->id)->get();
        //Falta restringir que el plan de cuentas sea solo de esa compania
        $accounts = ChartAccount::where('economic_activity', $company->economic_activity)->get();

        return response()->json([
            'product' => Product::find($id),
            'unities' => $unities,
            'categories' => $categories,
            'accounts' => $accounts
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        return Product::find($request->get('id'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $product->update($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
