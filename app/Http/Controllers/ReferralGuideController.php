<?php

namespace App\Http\Controllers;

use App\Company;
use App\Http\Resources\ReferralGuideResources;
use Illuminate\Http\Request;
use App\Product;
use App\ReferralGuide;
use App\ReferralGuideItem;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade as PDF;

class ReferralGuideController extends Controller
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

        $referralguide = ReferralGuide::join('carriers AS ca', 'ca.id', 'carrier_id')
            ->join('customers AS c', 'c.id', 'customer_id')
            ->select('referral_guides.*', 'c.name', 'ca.name AS carrier_name')
            ->where('c.branch_id', $branch->id);

        return ReferralGuideResources::collection($referralguide->paginate());
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
            'customers' => $branch->customers,
            'carriers' => $branch->carriers,
            'serie' => $this->getSeries($branch)
        ]);
    }

    private function getSeries($branch)
    {
        $branch_id = $branch->id;
        $invoice = ReferralGuide::select('serie')
            ->where([
                ['branch_id', $branch_id], // De la sucursal especifico
                ['state', 'AUTORIZADO'], // El estado debe ser AUTORIZADO pero por el momento solo que este FIRMADO
            ])->orderBy('created_at', 'desc') // Para traer el ultimo
            ->first();

        return $this->generedSerie($invoice, $branch->store);
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

        if ($referralguide = $branch->referralguides()->create($request->except(['products', 'send']))) {
            $products = $request->get('products');

            if (count($products) > 0) {
                $array = [];
                foreach ($products as $product) {
                    $array[] = [
                        'product_id' => $product['product_id'],
                        'quantity' => $product['quantity'],
                    ];
                }
                $referralguide->referralguidetems()->createMany($array);

                if ($request->get('send')) {
                    (new ReferralGuideXmlController())->xml($referralguide->id);
                }
            }
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Voucher  $voucher
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        $referralguide = ReferralGuide::findOrFail($id);

        $referralguide_items = Product::join('referral_guide_items AS rgi', 'product_id', 'products.id')
            ->select('products.iva', 'rgi.*')
            ->where('referral_guide_id', $referralguide->id)
            ->get();

        return response()->json([
            'products' => $branch->products,
            'customers' => $branch->customers,
            'carriers' => $branch->carriers,
            'referralguide' => $referralguide,
            'referralguide_items' => $referralguide_items
        ]);
    }

    public function showPdf($id)
    {
        $movement = ReferralGuide::join('customers AS c', 'customer_id', 'c.id')
            ->join('carriers AS ca', 'carrier_id', 'ca.id')
            ->select('referral_guides.*', 'c.*', 'ca.identication AS ca_identication', 'ca.name AS ca_name', 'ca.license_plate')
            ->where('referral_guides.id', $id)
            ->first();

        $movement->voucher_type = 6;

        $movement_items = ReferralGuideItem::join('products AS p', 'p.id', 'product_id')
            ->select('p.*', 'referral_guide_items.quantity')
            ->where('referral_guide_id', $id)
            ->get();

        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);

        $pdf = PDF::loadView('vouchers/referralguide', compact('movement', 'company', 'movement_items'));

        return $pdf->stream();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $referralguide = ReferralGuide::findOrFail($id);

        if ($referralguide->update($request->except(['products', 'send']))) {
            $products = $request->get('products');

            if (count($products) > 0) {
                $array = [];
                foreach ($products as $product) {
                    $array[] = [
                        'product_id' => $product['product_id'],
                        'quantity' => $product['quantity'],
                    ];
                }
                ReferralGuideItem::where('referral_guide_id', $referralguide->id)->delete();
                $referralguide->referralguidetems()->createMany($array);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Voucher  $voucher
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
