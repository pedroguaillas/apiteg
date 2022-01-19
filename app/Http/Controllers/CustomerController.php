<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Customer;
use Illuminate\Http\Request;
use App\Company;
use App\Http\Resources\CustomerResources;

class CustomerController extends Controller
{
    public function index()
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        $customers = Customer::where('branch_id', $branch->id);

        return CustomerResources::collection($customers->paginate());
    }

    public function findSmart()
    {
        return Customer::all();
    }

    public function store(Request $request)
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);

        try {
            $company->branches->first()->customers()->create($request->all());
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                return response()->json(['message' => 'KEY_DUPLICATE'], 405);
            }
        }
    }

    public function edit(int $id)
    {
        $customer = Customer::find($id);
        return response()->json(['customer' => $customer]);
    }

    public function update(Request $request, int $id)
    {
        $customer = Customer::findOrFail($id);
        $customer->update($request->all());
    }

    public function importCsv(Request $request)
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        $customers = $request->get('customers');

        $newcustomers = [];
        foreach ($customers as $customer) {
            array_push($newcustomers, [
                'type_identification' => $customer['type_identification'],
                'identication' => $customer['identication'],
                'name' => $customer['name'],
                'address' => $customer['address'],
            ]);
        }
        $customer = $company->branches->first()->customers()->createMany($newcustomers);

        $customers = Customer::where('branch_id', $branch->id);

        return CustomerResources::collection($customers->latest()->paginate());
    }

    public function destroy(Customer $customer)
    {
        //
    }
}
