<?php

namespace App\Http\Controllers;

use App\Company;
use App\Product;
use App\Shop;
use App\ShopRetentionItem;
use App\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade as PDF;

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
                ['branch_id', $branch_id], // De la sucursal específico
                ['state', 'AUTORIZADO'], // El estado debe ser AUTORIZADO pero por el momento solo que este FIRMADO
                ['voucher_type', 3] // 3-Liquidacion-de-compra
            ])->orderBy('created_at', 'desc') // Para traer el ultimo
            ->first();

        $retention = Shop::select('serie_retencion AS serie')
            ->where([
                ['branch_id', $branch_id], // De la sucursal específico
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
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        $except = ['taxes', 'pay_methods', 'app_retention', 'send'];

        if ($shop = $branch->shops()->create($request->except($except))) {

            if ($shop->voucher_type < 4 && $request->get('app_retention') && count($request->get('taxes')) > 0) {

                $taxes = $request->get('taxes');
                $array = [];

                foreach ($taxes as $tax) {
                    $array[] = [
                        'code' => $tax['code'],
                        'tax_code' => $tax['tax_code'],
                        'base' => $tax['base'],
                        'porcentage' => $tax['porcentage'],
                        'value' => $tax['value']
                    ];
                }

                $shop->shopretentionitems()->createMany($array);

                if ($request->get('send')) {
                    (new RetentionXmlController())->xml($shop->id);
                }
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Shop  $shop
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        $shop = Shop::findOrFail($id);

        $shopitems = Product::join('shop_items AS si', 'si.product_id', 'products.id')
            ->select('products.iva', 'si.*')
            ->where('shop_id', $shop->id)
            ->get();

        $series = $this->getSeries($branch);
        $shop->serie_retencion = ($shop->serie_retencion !== null) ? $shop->serie_retencion : $series['retention'];
        $shop->date_retention = date('Y-m-d');

        return response()->json([
            'products' => $branch->products,
            'providers' => $branch->providers,
            'shop' => $shop,
            'shopitems' => $shopitems,
            'shopretentionitems' => $shop->shopretentionitems,
            'taxes' => Tax::all(),
            'series' => $series
        ]);
    }

    public function showPdfRetention($id)
    {
        $movement = Shop::join('providers AS p', 'shops.provider_id', 'p.id')
            ->select(
                'shops.id',
                'shops.date AS date_v',
                'shops.voucher_type AS voucher_type_v',
                'shops.date_retention AS date',
                'shops.serie_retencion AS serie',
                'shops.autorized_retention AS autorized',
                'shops.xml_retention AS xml',
                'shops.authorization_retention AS authorization',
                'p.name',
                'p.identication'
            )
            ->where('shops.id', $id)
            ->first();

        $movement->voucher_type = 7;

        $retention_items = $movement->shopretentionitems;

        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);

        $pdf = PDF::loadView('vouchers/retention', compact('movement', 'company', 'retention_items'));

        return $pdf->stream();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Shop  $shop
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
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
    public function update(Request $request, $id)
    {
        $except = ['id', 'taxes', 'pay_methods', 'app_retention', 'send'];

        $shop = Shop::find($id);

        if ($shop->update($request->except($except))) {

            if ($shop->voucher_type < 4 && $request->get('app_retention') && count($request->get('taxes')) > 0) {

                $taxes = $request->get('taxes');
                $array = [];

                foreach ($taxes as $tax) {
                    $array[] = [
                        'code' => $tax['code'],
                        'tax_code' => $tax['tax_code'],
                        'base' => $tax['base'],
                        'porcentage' => $tax['porcentage'],
                        'value' => $tax['value']
                    ];
                }

                ShopRetentionItem::where('shop_id', $shop->id)->delete();

                $shop->shopretentionitems()->createMany($array);

                if ($request->get('send') && $shop->autorized_retention === null) {
                    (new RetentionXmlController())->xml($shop->id);
                }
            }
        }
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
