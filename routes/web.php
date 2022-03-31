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


// $router->get('/key', function() {
//     return \Illuminate\Support\Str::random(32);
// });

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

    $router->get('dashboard', 'DashboardController@index');

    // Logout
    $router->get('logout', 'AuthController@logout');

    //Companies
    $router->get('companies', 'CompanyController@index');
    $router->post('company_update', 'CompanyController@update');

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

    //Products
    $router->get('products', 'ProductController@index');
    $router->post('productlist', 'ProductController@productlist');
    $router->get('productscreate', 'ProductController@create');
    $router->post('products', 'ProductController@store');
    $router->get('products/{id}', 'ProductController@show');
    $router->put('products/{id}', 'ProductController@update');
    $router->post('products_import', 'ProductController@import');

    //orders
    $router->get('orders', 'OrderController@index');
    $router->post('orderlist', 'OrderController@orderlist');
    $router->get('orders/create', 'OrderController@create');
    $router->post('orders', 'OrderController@store');
    $router->get('orders/{id}', 'OrderController@show');
    $router->get('orders/{search}/search', 'OrderController@search');
    $router->put('orders/{id}', 'OrderController@update');
    $router->get('orders/{id}/pdf', 'OrderController@showPdf');

    //Order Xml
    $router->get('orders/xml/{id}', 'OrderXmlController@xml');
    $router->get('orders/download/{id}', 'OrderXmlController@download');
    $router->get('orders/sendsri/{id}', 'WSSriOrderController@send');
    $router->get('orders/authorize/{id}', 'WSSriOrderController@authorize');

    //shops
    $router->get('shops', 'ShopController@index');
    $router->post('shoplist', 'ShopController@shoplist');
    $router->get('shops/create', 'ShopController@create');
    $router->post('shops', 'ShopController@store');
    $router->get('shops/duplicate/{id}', 'ShopController@duplicate');
    $router->get('shops/{id}', 'ShopController@show');
    $router->put('shops/{id}', 'ShopController@update');

    // Liquidacion compra
    $router->get('shops/{id}/xml', 'SettlementOnPurchaseXmlController@xml');
    $router->get('shops/{id}/download', 'SettlementOnPurchaseXmlController@download');
    $router->get('shops/{id}/sendsri', 'WSSriSettlementOnPurchaseController@send');
    $router->get('shops/{id}/authorize', 'WSSriSettlementOnPurchaseController@authorize');
    $router->get('shops/{id}/pdf', 'ShopController@showPdf');

    //shops Import from txt
    $router->post('shops/import', 'ShopImportController@import');

    //Retention Pdf
    $router->get('retentions/pdf/{id}', 'ShopController@showPdfRetention');

    //Retention Xml
    $router->get('retentions/xml/{id}', 'RetentionXmlController@xml');
    $router->get('retentions/download/{id}', 'RetentionXmlController@download');
    $router->get('retentions/sendsri/{id}', 'WSSriRetentionController@sendSri');
    $router->get('retentions/authorize/{id}', 'WSSriRetentionController@authorize');

    // Guias de remision
    $router->get('referralguides', 'ReferralGuideController@index');
    $router->get('referralguides/create', 'ReferralGuideController@create');
    $router->post('referralguides', 'ReferralGuideController@store');
    $router->get('referralguides/{id}', 'ReferralGuideController@show');
    $router->put('referralguides/{id}', 'ReferralGuideController@update');
    $router->get('referralguides/{id}/pdf', 'ReferralGuideController@showPdf');

    //Guias de remision Xml
    $router->get('referralguides/xml/{id}', 'ReferralGuideXmlController@xml');
    $router->get('referralguides/download/{id}', 'ReferralGuideXmlController@download');
    $router->get('referralguides/sendsri/{id}', 'WSSriReferralGuide@send');
    $router->get('referralguides/authorize/{id}', 'WSSriReferralGuide@authorize');

    //Customers
    $router->get('customers', 'CustomerController@index');
    $router->post('customerlist', 'CustomerController@customerlist');
    $router->post('customers', 'CustomerController@store');
    $router->get('customers/{id}/edit', 'CustomerController@edit');
    $router->put('customers/{id}', 'CustomerController@update');
    $router->post('customers_import_csv', 'CustomerController@importCsv');

    // Proveedores
    $router->get('providers', 'ProviderController@index');
    $router->post('providerlist', 'ProviderController@providerlist');
    $router->post('providers', 'ProviderController@store');
    $router->get('providers/{id}/edit', 'ProviderController@edit');
    $router->put('providers/{id}', 'ProviderController@update');

    // Transportistas
    $router->get('carriers', 'CarrierController@index');
    $router->post('carrierlist', 'CarrierController@carrierlist');
    $router->post('carriers', 'CarrierController@store');
    $router->get('carriers/{id}/edit', 'CarrierController@edit');
    $router->put('carriers/{id}', 'CarrierController@update');
});
