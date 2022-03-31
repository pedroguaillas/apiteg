<?php

namespace App\Http\Controllers;

use App\Company;
use App\Http\Resources\ProductResources;
use App\Http\Resources\ProviderResources;
use App\Http\Resources\ShopResources;
use App\ShopRetentionItem;
use App\Product;
use App\Provider;
use App\Shop;
use App\ShopItem;
use App\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade as PDF;

class ShopController extends Controller
{

    public function index()
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        $shops = Shop::join('providers AS p', 'p.id', 'provider_id')
            ->select('shops.*', 'p.name')
            ->where('p.branch_id', $branch->id)
            ->orderBy('shops.created_at', 'DESC');

        return ShopResources::collection($shops->paginate());
    }

    public function shoplist(Request $request)
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        $search = $request->search;

        $shops = Shop::join('providers AS p', 'p.id', 'provider_id')
            ->select('shops.*', 'p.name')
            ->where('shops.branch_id', $branch->id)
            ->where(function ($query) use ($search) {
                return $query->where('shops.serie', 'LIKE', "%$search%")
                    ->orWhere('p.name', 'LIKE', "%$search%");
            })
            ->orderBy('shops.created_at', 'DESC');

        return ShopResources::collection($shops->paginate());
    }

    public function create()
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        return response()->json([
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
            $serie = str_pad($branch_store, 3, 0, STR_PAD_LEFT) . '-010-000000001';
        }

        return $serie;
    }

    public function store(Request $request)
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        $except = ['taxes', 'pay_methods', 'app_retention', 'send'];

        if ($shop = $branch->shops()->create($request->except($except))) {

            $send_set = false;

            if (count($request->get('products')) > 0) {

                $products = $request->get('products');
                $array = [];

                foreach ($products as $product) {
                    $array[] = [
                        'product_id' => $product['product_id'],
                        'quantity' => $product['quantity'],
                        'price' => $product['price'],
                        'discount' => $product['discount']
                    ];
                }

                $shop->shopitems()->createMany($array);

                // Verificando que sea una LIQUIDACIÓN EN COMPRA para enviar
                if ($request->get('send') && $shop->voucher_type === 3) {
                    $send_set = true;
                }
            }

            $send_ret = false;

            // Verificando que sea una LIQUIDACIÓN EN COMPRA o FACTURA, además que exista retenciones
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
                    $send_ret = true;
                }
            }

            // Envio de comprobantes
            if ($send_set) {
                (new SettlementOnPurchaseXmlController())->xml($shop->id);
            }
            if ($send_ret) {
                (new RetentionXmlController())->xml($shop->id);
            }
        }
    }

    public function duplicate(int $id)
    {
        $shop = Shop::find($id);

        $newShop = Shop::create([
            'branch_id' => $shop->branch_id,
            'date' => $shop->date,
            'description' => $shop->description,
            'sub_total' => $shop->sub_total,
            'serie' => $shop->serie,
            'provider_id' => $shop->provider_id,
            'doc_realeted' => $shop->doc_realeted,
            'expiration_days' => $shop->expiration_days,
            'no_iva' => $shop->no_iva,
            'base0' => $shop->base0,
            'base12' => $shop->base12,
            'iva' => $shop->iva,
            'discount' => $shop->discount,
            'ice' => $shop->ice,
            'total' => $shop->total,
            'voucher_type' => $shop->voucher_type,
            'paid' => $shop->paid,
            'iva_retention' => $shop->iva_retention,
            'rent_retention' => $shop->rent_retention
        ]);

        if ($newShop) {
            return $this->index();
        } else {
            return response()->json(['msm' => 'No Duplicado']);
        }
    }

    public function show($id)
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        $shop = Shop::findOrFail($id);

        $products = Product::join('shop_items AS si', 'product_id', 'products.id')
            ->select('products.*')
            ->where('shop_id', $id)
            ->get();

        $shopitems = Product::join('shop_items AS si', 'si.product_id', 'products.id')
            ->select('products.iva', 'si.*')
            ->where('shop_id', $shop->id)
            ->get();

        $series = $this->getSeries($branch);
        $shop->serie_retencion = ($shop->serie_retencion !== null) ? $shop->serie_retencion : $series['retention'];
        // $shop->date_retention = date('Y-m-d');

        $providers = Provider::where('id', $shop->provider_id)->get();

        return response()->json([
            'products' => ProductResources::collection($products),
            'providers' => ProviderResources::collection($providers),
            'shop' => $shop,
            'shopitems' => $shopitems,
            'shopretentionitems' => $shop->shopretentionitems,
            'taxes' => Tax::all(),
            'series' => $series
        ]);
    }

    // Solo liquidacion en compra
    public function showPdf($id)
    {
        $movement = Shop::join('providers AS p', 'shops.provider_id', 'p.id')
            ->select('shops.*', 'p.*')
            ->where('shops.id', $id)
            ->first();

        $movement_items = ShopItem::join('products', 'products.id', 'shop_items.product_id')
            ->select('products.*', 'shop_items.*')
            ->where('shop_items.shop_id', $id)
            ->get();

        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);

        $pdf = PDF::loadView('vouchers/settlementonpurchase', compact('movement', 'company', 'movement_items'));

        return $pdf->stream();
    }

    public function showPdfRetention($id)
    {
        $movement = Shop::join('providers AS p', 'provider_id', 'p.id')
            ->select(
                'shops.id',
                'shops.date AS date_v',
                'shops.voucher_type AS voucher_type_v',
                'shops.date_retention AS date',
                'shops.serie AS serie_retencion',
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
}
