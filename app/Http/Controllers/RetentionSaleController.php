<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\RetentionSale;
use App\Tax;

class RetentionSaleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
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
        $id = $request->get('id');

        if ($id > 0) {
            $retentionSale = RetentionSale::find($id);
        } else {
            $retentionSale = new RetentionSale;
        }

        $retentionSale->sale_id = $request->get('sale_id');
        $retentionSale->serie = $request->get('serie');
        $retentionSale->date = $request->get('date');

        if ($retentionSale->save()) {
            $taxes = $request->get('taxes');
            $retentionSale->retentionsaleitems()->createMany($taxes);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\RetentionSale  $retentionSale
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $retentionSale = RetentionSale::where('sale_id', $id)->first();
        $retentionSaleitems = ($retentionSale !== null) ? $retentionSale->retentionsaleitems : null;
        $taxes = Tax::all();

        return response()->json(['retentionSale' => $retentionSale, 'retentionSaleitems' => $retentionSaleitems, 'taxes' => $taxes]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function signSend(Request $request)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\RetentionSale  $retentionSale
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RetentionSale $retentionSale)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\RetentionSale  $retentionSale
     * @return \Illuminate\Http\Response
     */
    public function destroy(RetentionSale $retentionSale)
    {
        //
    }
}
