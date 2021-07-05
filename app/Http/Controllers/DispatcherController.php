<?php

namespace App\Http\Controllers;

use App\Dispatcher;
use Illuminate\Http\Request;

class DispatcherController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Dispatcher::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (isset($_GET['id'])) {
            $dispatcher = Dispatcher::find($request->get('id'));
        } else {
            $dispatcher = new Dispatcher;
        }

        $dispatcher->identification_type = $request->get('identification_type');
        $dispatcher->identification_value = $request->get('identification_value');
        $dispatcher->name = $request->get('name');
        $dispatcher->email = $request->get('email');
        $dispatcher->license_plate = $request->get('license_plate');

        if ($dispatcher->save()) {
            return response()->json(['dispatcher' => $dispatcher]);
        }

        return response()->json(['dispatcher', null]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Dispatcher  $dispatcher
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Dispatcher::find($id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Dispatcher  $dispatcher
     * @return \Illuminate\Http\Response
     */
    public function edit(Dispatcher $dispatcher)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Dispatcher  $dispatcher
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Dispatcher $dispatcher)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Dispatcher  $dispatcher
     * @return \Illuminate\Http\Response
     */
    public function destroy(Dispatcher $dispatcher)
    {
        //
    }
}
