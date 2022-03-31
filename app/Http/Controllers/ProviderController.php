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

    public function providerlist(Request $request = null)
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        $search = '';
        $paginate = 15;

        if ($request) {
            $search = $request->search;
            $paginate = $request->has('paginate') ? $request->paginate : $paginate;
        }

        $providers = Provider::where('branch_id', $branch->id)
            ->where(function ($query) use ($search) {
                return $query->where('identication', 'LIKE', "%$search%")
                    ->orWhere('name', 'LIKE', "%$search%");
            })
            ->orderBy('created_at', 'DESC');

        return ProviderResources::collection($providers->paginate($paginate));
    }

    public function store(Request $request)
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        try {
            $provider = $branch->providers()->create($request->all());
            return response()->json(['provider' => $provider]);
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                $provider = Provider::where([
                    'identication' => $request->identication,
                    'branch_id' => $branch->id,
                ])->get()->first();
                return response()->json([
                    'message' => 'KEY_DUPLICATE',
                    'provider' => $provider
                ], 405);
            }
        }
    }

    // public function storeOrUdate(Request $request)
    // {
    //     $auth = Auth::user();
    //     $level = $auth->companyusers->first();
    //     $company = Company::find($level->level_id);
    //     $branch = $company->branches->first();

    //     DB::table('providers')
    //         ->updateOrInsert(
    //             ['identication' => $request->get('identication'), 'branch_id'=>$branch->id],
    //             [
    //                 'razonsocial' => $request->get('razonsocial'),
    //                 'phone' => $request->get('phone'),
    //                 'mail' => $request->get('mail'),
    //                 'direccion' => $request->get('direccion'),
    //                 'diadeclaracion' => $request->get('diadeclaracion'),
    //                 'sri' => $request->get('sri'),
    //                 'representantelegal' => $request->get('representantelegal'),
    //                 'iess1' => $request->get('iess1'),
    //                 'iess2' => $request->get('iess2'),
    //                 'mt' => $request->get('mt'),
    //                 'mrl' => $request->get('mrl'),
    //                 'super' => $request->get('super'),
    //                 'contabilidad' => $request->get('contabilidad')
    //             ]
    //         );

    //     try {
    //         $provider = $branch->providers()->create($request->all());
    //         return response()->json(['provider' => $provider]);
    //     } catch (\Illuminate\Database\QueryException $e) {
    //         $errorCode = $e->errorInfo[1];
    //         if ($errorCode == 1062) {
    //             $provider = Provider::where([
    //                 'identication' => $request->identication,
    //                 'branch_id' => $branch->id,
    //             ])->get();
    //             return response()->json([
    //                 'message' => 'KEY_DUPLICATE',
    //                 'provider' => $provider
    //             ], 405);
    //         }
    //     }
    // }

    public function edit($id)
    {
        $provider = Provider::findOrFail($id);
        return response()->json(['provider' => $provider]);
    }

    public function update(Request $request, $id)
    {
        $provider = Provider::findOrFail($id);

        try {
            $provider->update($request->except(['id']));
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                return response()->json(['message' => 'KEY_DUPLICATE'], 405);
            }
        }
    }
}
