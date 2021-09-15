<?php

namespace App\Http\Controllers;

use App\Company;
use App\Shop;
use App\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShopController extends Controller
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

        $shops = Shop::join('providers AS p', 'p.id', 'provider_id')
            ->select('shops.*', 'p.name')
            ->where('p.branch_id', $branch->id)
            ->get();

        return response()->json(['shops' => $shops]);
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

        return response()->json([
            'products' => $branch->products,
            'providers' => $branch->providers,
            'taxes' => Tax::all(),
            'series' => $this->getSeries($branch)
        ]);
    }

    private function getSeries($branch)
    {
        $branch_id = $branch->id;

        $set_purchase = Shop::select('serie')
            ->where([
                ['branch_id', $branch_id], // De la sucursal especifico
                ['state', 'AUTORIZADO'], // El estado debe ser AUTORIZADO pero por el momento solo que este FIRMADO
                ['voucher_type', 3] // 3-Liquidacion-de-compra
            ])->orderBy('created_at', 'desc') // Para traer el ultimo
            ->first();

        $retention = Shop::select('serie_retencion AS serie')
            ->where([
                ['branch_id', $branch_id], // De la sucursal especifico
                ['state_retencion', 'AUTORIZADO'], // El estado debe ser AUTORIZADO pero por el momento solo que este FIRMADO
                // ['voucher_type', 3] // 3-Liquidacion-de-compra
            ])->orderBy('created_at', 'desc') // Para traer el ultimo
            ->first();

        $new_obj = [
            'set_purchase' => $this->generedSerie($set_purchase, $branch->store),
            'retention' => $this->generedSerie($retention, $branch->store)
        ];

        return $new_obj;
    }

    //Return the serie of sales generated
    private function generedSerie($serie, $branch_store)
    {
        if ($serie != null) {
            $serie = $serie->serie;
            //Convert string to array
            $serie = explode("-", $serie);
            //Get value Integer from String & sum 1
            $serie[2] = (int) $serie[2] + 1;
            //Complete 9 zeros to left 
            $serie[2] = str_pad($serie[2], 9, 0, STR_PAD_LEFT);
            //convert Array to String
            $serie = implode("-", $serie);
        } else {
            $serie = str_pad($branch_store, 3, 0, STR_PAD_LEFT) . '-001-000000001';
        }

        return $serie;
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
