<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Company;
use App\User;

class CompanyController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

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
        $company->accounting = $company->accounting === 1;
        $company->micro_business = $company->micro_business === 1;

        return response()->json(['company' => $company]);
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
        // New Object constraint
        $input = $request->except(['logo', 'cert', 'user', 'password']);

        // Load logo
        if ($request->logo === NULL) {
            $input['logo_dir'] = 'default.png';
        } else {
            // Load from API
            $image = $request->file('logo');
            $imagename = $request->ruc . '.' . $image->getClientOriginalExtension();
            $request->file('logo')->storeAs('logos', $imagename);
            $input['logo_dir'] = $imagename;
        }

        if ($request->cert !== NULL) {

            $certname = $request->ruc . $request->extention_cert;

            $request->file('cert')->storeAs('cert', $certname);

            $results = array();
            if (openssl_pkcs12_read(Storage::get('cert' . DIRECTORY_SEPARATOR . $certname), $results, $request->pass_cert)) {
                $cert = $results['cert'];
                openssl_x509_export($cert, $certout);
                $data = openssl_x509_parse($certout);
                $validFrom = \DateTime::createFromFormat('U', strval($data['validFrom_time_t']));
                $validFrom->setTimeZone(new \DateTimeZone('America/Guayaquil'));
                $input['sign_valid_from'] = $validFrom->format('Y/m/d H:i:s');
                $validTo = \DateTime::createFromFormat('U', strval($data['validTo_time_t']));
                $validTo->setTimeZone(new \DateTimeZone('America/Guayaquil'));
                $input['sign_valid_to'] = $validTo->format('Y/m/d H:i:s');
                $date_aux = date('Y/m/d H:i:s');
                // Valid cert
                // if (!(($date_aux >= $input['sign_valid_from']) && ($date_aux <= $input['sign_valid_to']))) {
                //     return response()->json(['message' => 'EXPIRED_DIGITAL_CERT'], 403);
                // } else {
                // }
                $input['cert_dir'] = $certname;
            }
        }

        $input['accounting'] = $request->accounting === 'true' ? 1 : 0;
        $input['micro_business'] = $request->micro_business === 'true' ? 1 : 0;
        $input['rimpe'] = $request->rimpe === 'true' ? 1 : 0;

        if (Company::create($input)) {
            $user = $request->only(['user', 'password', 'email']);
            $user['user_type_id'] = 2;
            $user['password'] = Hash::make($user['password']);

            User::create($user);

            // queries
            $company = Company::where('ruc', $input['ruc'])->first();
            $user = User::where('user', $user['user'])->first();

            $user->companyusers()->create([
                'level' => 'owner',
                'level_id' => $company->id
            ]);

            return response()->json([
                'user' => $user
            ]);

            return response()->json(['message' => 'Registrado compañia']);
        } else {
            return response()->json(['message' => 'Errores desconocidos']);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function show(Company $company)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function edit(Company $company)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $company = Company::find($request->id);

        // New Object constraint
        $input = $request->except(['logo', 'cert', 'extention_cert']);

        // Load logo
        if ($request->logo !== NULL) {
            // Load from API
            $image = $request->file('logo');
            $imagename = $request->ruc . '.' . $image->getClientOriginalExtension();
            $request->file('logo')->storeAs('logos', $imagename);
            $input['logo_dir'] = $imagename;
        }

        if ($request->cert !== NULL) {

            $certname = $request->ruc . $request->extention_cert;

            $request->file('cert')->storeAs('cert', $certname);

            $results = array();
            if (openssl_pkcs12_read(Storage::get('cert' . DIRECTORY_SEPARATOR . $certname), $results, $request->pass_cert)) {
                $cert = $results['cert'];
                openssl_x509_export($cert, $certout);
                $data = openssl_x509_parse($certout);
                $validFrom = \DateTime::createFromFormat('U', strval($data['validFrom_time_t']));
                $validFrom->setTimeZone(new \DateTimeZone('America/Guayaquil'));
                $input['sign_valid_from'] = $validFrom->format('Y/m/d H:i:s');
                $validTo = \DateTime::createFromFormat('U', strval($data['validTo_time_t']));
                $validTo->setTimeZone(new \DateTimeZone('America/Guayaquil'));
                $input['sign_valid_to'] = $validTo->format('Y/m/d H:i:s');
                $date_aux = date('Y/m/d H:i:s');

                // Valid cert
                // if (!(($date_aux >= $input['sign_valid_from']) && ($date_aux <= $input['sign_valid_to']))) {
                //     return response()->json(['message' => 'EXPIRED_DIGITAL_CERT'], 403);
                // } else {
                //     $input['cert_dir'] = $certname;
                // }
            }
        }

        $input['accounting'] = $request->accounting === 'true' ? 1 : 0;
        $input['micro_business'] = $request->micro_business === 'true' ? 1 : 0;

        if ($company->update($input)) {
            return response()->json(['message' => 'Actualizado compañia']);
        } else {
            return response()->json(['message' => 'Errores desconocidos']);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function destroy(Company $company)
    {
        //
    }
}
