<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\ChartAccount;
use App\Category;
use App\Company;
use App\Http\Resources\ProductResources;
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
            ->select('products.*', 'categories.category', 'unities.unity')->get();

        return ProductResources::collection($products);
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
