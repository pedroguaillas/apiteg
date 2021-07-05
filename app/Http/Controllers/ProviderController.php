<?php

namespace App\Http\Controllers;

use App\Provider;
use Illuminate\Http\Request;
use DB;

class ProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $providers = Provider::all();

        $array = array();
        foreach ($providers as $provider) {
            $provider->accounting = $provider->accounting == 1;
            array_push($array, $provider);
        }
        return $array;
    }

    /**
     * Display a listing of the resource for search smart.
     *
     * @return \Illuminate\Http\Response
     */
    public function findSmart()
    {
        return Provider::all();
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
        $id = $request->get('id');
        if ($id > 0) {
            $provider = Provider::find($id);
        } else {
            $provider = new Provider;
        }

        $provider->identification_value = $request->get('identification_value');
        $provider->identification_type = $request->get('identification_type');
        $provider->name = $request->get('name');
        $provider->type = $request->get('type');
        $provider->accounting = $request->get('accounting');
        $provider->direction = $request->get('direction');
        $provider->phone = $request->get('phone');
        $provider->mail = $request->get('mail');
        if ($provider->save()) {
            return response()->json(['provider' => $provider]);
        }

        return response()->json(['provider' => null]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function show(Provider $provider)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function edit(Provider $provider)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Provider $provider)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function destroy(Provider $provider)
    {
        //
    }
}
