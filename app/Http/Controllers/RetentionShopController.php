<?php

namespace App\Http\Controllers;

use App\RetentionShop;
use App\Tax;
use Illuminate\Http\Request;

class RetentionShopController extends Controller
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
            $retentionShop = RetentionShop::find($id);
        } else {
            $retentionShop = new RetentionShop;
        }

        $retentionShop->shop_id = $request->get('shop_id');
        $retentionShop->serie = $request->get('serie');
        $retentionShop->date = $request->get('date');

        if ($retentionShop->save()) {
            $taxes = $request->get('taxes');
            $retentionShop->retentionshopitems()->createMany($taxes);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\RetentionShop  $retentionShop
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $retentionShop = RetentionShop::where('shop_id', $id)->first();
        $retentionShopItems = ($retentionShop !== null) ? $retentionShop->retentionshopitems : null;
        $taxes = Tax::all();

        return response()->json(['retentionShop' => $retentionShop, 'retentionShopItems' => $retentionShopItems, 'taxes' => $taxes]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\RetentionShop  $retentionShop
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RetentionShop $retentionShop)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\RetentionShop  $retentionShop
     * @return \Illuminate\Http\Response
     */
    public function destroy(RetentionShop $retentionShop)
    {
        //
    }
}
