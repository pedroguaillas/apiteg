<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\ChartAccount;
use App\Company;
use App\Contact;

class ContactController extends Controller
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
        $contacts = $company->branches->first()->contacts;

        return response()->json(['contacts' => $contacts]);
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
        return response()->json([
            //Falta restringir que el plan de cuentas sea solo de esa compania
            'accounts' => ChartAccount::where('type', $company->type)->get()
        ]);
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
            $contact = $company->branches->first()->contacts()->create($request->all());
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                return response()->json(['message' => 'KEY_DUPLICATE'], 405);
            }
        }
        // $id = $request->get('id');

        // if ($id > 0) {
        //     $contact = Contact::find($id);
        // } else {
        //     $contact = new Contact;
        // }

        // $contact->state = $request->get('state');
        // $contact->type = $request->get('type');
        // $contact->special = $request->get('special');
        // $contact->identication_card = $request->get('identication_card');
        // $contact->ruc = $request->get('ruc');
        // $contact->company = $request->get('company');
        // $contact->name = $request->get('name');
        // $contact->address = $request->get('address');
        // $contact->phone = $request->get('phone');
        // $contact->email = $request->get('email');
        // $contact->accounting = $request->get('accounting');
        // $contact->receive_account_id = $request->get('receive_account_id');
        // $contact->discount = $request->get('discount');
        // $contact->pay_account_id = $request->get('pay_account_id');
        // $contact->rent_retention = $request->get('rent_retention');
        // $contact->iva_retention = $request->get('iva_retention');
        // $contact->save();
    }

    public function import(Request $request)
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);

        $contacts = $request->get('contacts');

        $newContacts = [];
        foreach ($contacts as $contact) {
            array_push($newContacts, [
                'state' => 1,
                'special' => 0,
                'identication_card' => strlen($contact['identication_card']) ? $contact['identication_card'] : null,
                'ruc' => strlen($contact['ruc']) ? $contact['ruc'] : null,
                'company' => $contact['company'],
                'address' => $contact['address']
            ]);
        }
        $contact = $company->branches->first()->contacts()->createMany($newContacts);

        return response()->json(['msm' => 'Result from server', 'contacts' => $contacts]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Contact  $contact
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        return response()->json([
            'contact' => Contact::find($id),
            //Falta restringir que el plan de cuentas sea solo de esa compania
            // 'accounts' => ChartAccount::where('type', $company->type)->get()
        ]);
    }

    public function update(Request $request, $id)
    {
        $contact = Contact::findOrFail($id);
        $contact->update($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Contact  $contact
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact)
    {
        //
    }
}
