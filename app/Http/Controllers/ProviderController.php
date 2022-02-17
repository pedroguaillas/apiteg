<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Company;
use App\Http\Resources\ProviderResources;
use App\Provider;

class ProviderController extends Controller
{
    public function index()
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        $providers = Provider::where('branch_id', $branch->id);

        return ProviderResources::collection($providers->paginate());
    }

    public function providerlist(Request $request)
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        $search = $request->search;

        $providers = Provider::where('branch_id', $branch->id)
            ->where(function ($query) use ($search) {
                return $query->where('identication', 'LIKE', "%$search%")
                    ->orWhere('name', 'LIKE', "%$search%");
            })
            ->orderBy('created_at', 'DESC');

        return ProviderResources::collection($providers->paginate());
    }

    public function store(Request $request)
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);

        try {
            $provider = $company->branches->first()->providers()->create($request->all());
            return response()->json([
                'provider' => $provider
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                return response()->json(['message' => 'KEY_DUPLICATE'], 405);
            }
        }
    }

    public function edit($id)
    {
        $provider = Provider::findOrFail($id);
        return response()->json(['provider' => $provider]);
    }

    public function update(Request $request, $id)
    {
        $provider = Provider::findOrFail($id);
        $provider->update($request->except(['id']));
    }
}
