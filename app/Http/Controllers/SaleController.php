<?php

namespace App\Http\Controllers;

use App\Product;
use App\Sale;
use App\SaleItem;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Sale::select('sales.*', 'customers.name')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->orderByRaw('serie')
            ->get();
    }

    //Return the serie of sales generated
    public function generedSerie()
    {
        //Query database
        $serie = Sale::select("serie")->orderBy('id', 'desc')->first();

        if ($serie != null) {
            //Convert string to array
            $serie = explode("-", $serie->serie);
            //Get value Integer from String & sum 1
            $serie[2] = (int) $serie[2] + 1;
            //Complete 9 zeros to left 
            $serie[2] = str_pad($serie[2], 9, 0, STR_PAD_LEFT);
            //convert Array to String
            $serie = implode("-", $serie);
        } else {
            $serie = '001-001-000000001';
        }

        return response()->json(['serie' => $serie]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $sale = new Sale;

        $sale->serie = $request->get('serie');
        //$sale->customer_id = $request->get('customer_id');
        $custom = $request->get('custom');
        $sale->customer_id = $custom['id'];
        $sale->date = $request->get('date');
        $sale->expiration_date = $request->get('expiration_date');
        $sale->no_iva = $request->get('no_iva');
        $sale->base0 = $request->get('base0');
        $sale->base12 = $request->get('base12');
        $sale->iva = $request->get('iva');
        $sale->sub_total = $request->get('sub_total');
        $sale->discount = $request->get('discount');
        $sale->total = $request->get('total');
        $sale->pay_method = $request->get('pay_method');
        $sale->notes = $request->get('notes');
        $sale->paid = ($sale->pay_method == 'contado') ? $request->get('total') : 0;
        $sale->save();

        $products = $request->get('products');

        foreach ($products as $product) {
            $sale->saleitems()->create([
                'product_id' => $product["id"],
                'price' => $product["price1"],
                'quantity' => $product["quantity"]
            ]);
            $prod = Product::find($product["id"]);
            $prod->stock = $prod->stock - $product["quantity"];
            $prod->save();
        }

        // AccountEntry::create([
        //     'date' => $request->get('date'),
        //     'description' => 'Ventas',
        //     'account_seat_id' => 8,
        //     'type' => 'HABER',
        //     'value' => $sale->total,
        // ]);

        // $accountEntry = new AccountEntry;
        // $accountEntry->date = $request->get('date');
        // $accountEntry->description = 'Ventas';
        // $accountEntry->save();

        // $accountEntry->accountentryitems()->create([
        //     'account_id' => 8,
        //     'type' => 'DEBE',
        //     'amount' => $sale->total
        // ]);

        return response()->json(['msg' => 'Ok']);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        /**
         * Use for view only sale to edit
         * Redirect from  to sale http://localhost:3000/#/inventarios/ventas
         */

        //Search sale inner join customer
        $sale = Sale::join('customers', 'sales.customer_id', '=', 'customers.id')
            ->where('sales.id', $id)
            ->select('customers.*', 'sales.*')
            ->get();

        //Sale return array is necesary convert to one object
        $sale = $sale[0];

        //Search saleitems of sale
        $saleitems = SaleItem::join('products', 'sale_items.product_id', 'products.id')
            ->where('sale_items.sale_id', '=', $sale->id)->get();
        //$saleitems = $sale->saleitems;
        //$saleitems = $sale->saleitems->join('products', 'sale_items.product_id', 'products.id');

        //Return values
        return response()->json(['sale' => $sale, 'saleitems' => $saleitems]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function by_id_customer($id)
    {
        /**
         * Use for view list sales of a customer
         * Redirect from  to sale http://localhost:3000/#/contactos/clientes
         */

        return response()->json(['sales' => Sale::where('customer_id', $id)->get()]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        Sale::find($request->get('id'))
            ->update(['paid' => 1]);

        return response()->json(['result' => 'Ok']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function destroy(Sale $sale)
    {
        //
    }
}
