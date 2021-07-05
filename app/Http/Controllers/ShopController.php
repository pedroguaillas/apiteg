<?php

namespace App\Http\Controllers;

use App\Shop;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Shop::select('shops.*', 'providers.name')
            ->join('providers', 'shops.provider_id', '=', 'providers.id')
            ->orderByRaw('serie')
            ->get();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $shop = new Shop;

        $shop->serie = $request->get('serie');

        $provider = $request->get('provider');
        $shop->provider_id = $provider['id'];
        $shop->date = $request->get('date');
        $shop->expiration_date = $request->get('expiration_date');
        $shop->no_iva = $request->get('no_iva');
        $shop->base0 = $request->get('base0');
        $shop->base12 = $request->get('base12');
        $shop->iva = $request->get('iva');
        $shop->sub_total = $request->get('sub_total');
        $shop->discount = $request->get('discount');
        $shop->total = $request->get('total');
        $shop->voucher_type = $request->get('voucher_type');
        $shop->pay_method = $request->get('pay_method');
        $shop->notes = $request->get('notes');
        $shop->paid = $request->get('paid');

        if ($shop->save()) {

            $products = $request->get('products');

            $shopitems = array();

            foreach ($products as $product) {
                array_push(
                    $shopitems,
                    [
                        'product_id' => $product["id"],
                        'price' => $product["price1"],
                        'quantity' => $product["quantity"]
                    ]
                );
                // $prod = Product::find($product["id"]);
                // $prod->stock = $prod->stock + $product["quantity"];
                // $prod->save();
            }
            $shop->shopitems()->createMany($shopitems);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Shop  $shop
     * @return \Illuminate\Http\Response
     */
    public function show(Shop $shop)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Shop  $shop
     * @return \Illuminate\Http\Response
     */
    public function edit(Shop $shop)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Shop  $shop
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Shop $shop)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Shop  $shop
     * @return \Illuminate\Http\Response
     */
    public function destroy(Shop $shop)
    {
        //
    }
}
