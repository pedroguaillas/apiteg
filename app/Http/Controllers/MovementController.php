<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Movement;
use App\MovementItem;
use App\Product;
use App\Company;
use App\Http\Resources\MovementResources;
use Illuminate\Http\Request;

class MovementController extends Controller
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

        $movements = $company->branches->first()->movements;

        return MovementResources::collection($movements);
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

        // $products = Product::leftJoin('categories', 'categories.id', 'products.category_id')
        //     ->where('categories.branch_id', $branch->id)
        //     ->select('products.id', 'products.code', 'products.name')->get();

        $products = $branch->products;

        // Note: require equal attributes what Product.index
        return response()->json(['products' => $products]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // $id = $request->get('id');

        // if ($id > 0) {
        //     $movement = Movement::find($id);
        // } else {
        //     $movement = new Movement;
        // }

        // $movement->date = $request->get('date');
        // $movement->type = $request->get('type');
        // $movement->description = $request->get('description');
        // //Delete seat_generate to register if true
        // $movement->seat_generate = $request->get('seat_generate');
        // $movement->sub_total = $request->get('sub_total');

        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);

        $movement = $company->branches->first()->movements()
            ->create($request->except('products'));

        if ($movement) {
            $products = $request->get('products');
            $movement->movementitems()->createMany($products);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Movement  $movement
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $movement = Movement::find($id);
        $products = MovementItem::join('products', 'products.id', 'movement_items.product_id')
            ->where('movement_items.movement_id', $id)
            ->get();

        return response()->json(['movement' => $movement, 'products' => $products]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Movement  $movement
     * @return \Illuminate\Http\Response
     */
    public function destroy(Movement $movement)
    {
        //
    }
}
