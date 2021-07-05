<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// API route group
$router->group(['prefix' => 'api'], function () use ($router) {

    // Matches "/api/companies
    $router->post('companies', 'CompanyController@store');

    // Matches "/api/login
    $router->post('login', 'AuthController@login');
    // Matches "/api/refreshtoken
    $router->get('refreshtoken', 'AuthController@refreshToken');
});

$router->group(['middleware' => 'jwt.refresh'], function ($router) {
    // $router->get('refreshtoken', 'AuthController@refreshToken');
});

$router->group(['middleware' => 'jwt.verify'], function ($router) {
    // Logout
    $router->get('logout', 'AuthController@logout');

    //Companies
    $router->get('companies', 'CompanyController@index');

    // Branches
    $router->get('branches', 'BranchController@index');
    $router->post('branches', 'BranchController@store');

    // Categories
    $router->get('categories', 'CategoryController@index');
    $router->post('categories', 'CategoryController@store');
    $router->get('categories/{id}', 'CategoryController@show');
    $router->put('categories/{id}', 'CategoryController@update');
    $router->delete('categories/{id}', 'CategoryController@destroy');

    // Unity
    $router->get('unities', 'UnityController@index');
    $router->post('unities', 'UnityController@store');

    // Account
    $router->get('chartaccounts', 'ChartAccountController@index');
    $router->get('chartaccountspdf', 'ChartAccountController@indexPdf');
    $router->post('chartaccounts', 'ChartAccountController@store');

    // Ledger
    $router->get('chartaccountsledger', 'ChartAccountController@ledger');
    $router->get('balancepurchase', 'ChartAccountController@balancepurchase');
    $router->get('balanceSheet', 'ChartAccountController@balanceSheet');
    $router->get('balanceSheetPdf/{level}', 'ChartAccountController@balanceSheetPdf');
    $router->get('resultState', 'ChartAccountController@resultState');
    $router->get('resultStatePdf/{level}', 'ChartAccountController@resultStatePdf');

    // Start Account Entries .....
    // Diary Book
    $router->get('accountentries', 'AccountEntryController@index');
    // Register a new account entry
    $router->post('accountentries', 'AccountEntryController@store');
    // End Account Entries .....

    //Categories
    $router->get('categories', 'CategoryController@index');
    $router->post('categories', 'CategoryController@store');

    //Products
    $router->get('products', 'ProductController@index');
    $router->get('productscreate', 'ProductController@create');
    $router->post('products', 'ProductController@store');
    $router->get('products/{id}', 'ProductController@show');
    $router->put('products/{id}', 'ProductController@update');

    //Movements
    $router->get('movements', 'MovementController@index');
    $router->get('movementscreate', 'MovementController@create');
    $router->post('movements', 'MovementController@store');
    $router->get('movements/{id}', 'MovementController@show');

    //Vouchers
    $router->get('vouchers', 'VoucherController@index');
    $router->get('voucherscreate', 'VoucherController@create'); //Used create new voucher
    $router->post('vouchers', 'VoucherController@store');
    $router->get('vouchers/{id}', 'VoucherController@show');
    $router->get('vouchersbycontact/{contact_id}', 'VoucherController@showByContact');
    $router->get('voucherspdf/{id}', 'VoucherController@showPdf');

    // return xml
    $router->get('xml/{id}', 'XmlVoucherController@xml');
    $router->get('xml_retention/{id}', 'XmlVoucherController@xmlRetention');

    // Azur
    $router->get('azur/{id}', 'AzurApiController@index');

    // Datil
    $router->get('datil/{id}', 'DatilApiController@index');

    $router->get('sendsri/{id}', 'WSSriController@sendVoucher');
    $router->get('authorizevoucher/{id}', 'WSSriController@authorizevoucher');

    //Contacts
    $router->get('contacts', 'ContactController@index');
    $router->get('contactscreate', 'ContactController@create');
    $router->post('contacts', 'ContactController@store');
    $router->get('contacts/{id}', 'ContactController@show');
    $router->post('contacts_import', 'ContactController@import');
});
