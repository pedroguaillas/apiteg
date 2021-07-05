<?php

namespace App\Http\Controllers;

use App\Customer;
use Illuminate\Http\Request;
use DB;

class CustomerController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Customer::all();
    }

    /**
     * Display a listing of the resource for search smart.
     *
     * @return \Illuminate\Http\Response
     */
    public function findSmart()
    {
        return Customer::all();
        //return DB::connection('mysql2')->select("SELECT * FROM customers");
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

        $customer = ($id > 0) ? Customer::find($id) : new Customer;

        $customer->identification_type = $request->get('identification_type');
        $customer->identification_value = $request->get('identification_value');
        $customer->name = $request->get('name');
        $customer->direction = $request->get('direction');
        $customer->phone = $request->get('phone');
        $customer->email = $request->get('email');
        $customer->type_tax_payer = 1;

        if ($customer->save()) {
            return response()->json(['customer' => $customer]);
        }

        return response()->json(['customer' => null]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function show(Customer $customer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function edit(Customer $customer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Customer $customer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Customer $customer)
    {
        //
    }
}
