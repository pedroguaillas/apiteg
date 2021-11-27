<?php

namespace App\Http\Controllers;

use App\Carrier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Company;
use App\Http\Resources\CarrierResources;

class CarrierController extends Controller
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

        $carriers = Carrier::where('branch_id', $branch->id);

        return CarrierResources::collection($carriers->paginate());
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

        try {
            $company->branches->first()->carriers()->create($request->all());
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                return response()->json(['message' => 'KEY_DUPLICATE'], 405);
            }
        }
    }

    public function edit(int $id)
    {
        $carrier = Carrier::find($id);
        return response()->json(['carrier' => $carrier]);
    }

    public function update(Request $request, int $id)
    {
        $carrier = Carrier::findOrFail($id);
        $carrier->update($request->all());
    }
}
