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

    //Products
    $router->get('products', 'ProductController@index');
    $router->get('productscreate', 'ProductController@create');
    $router->post('products', 'ProductController@store');
    $router->get('products/{id}', 'ProductController@show');
    $router->put('products/{id}', 'ProductController@update');
    $router->post('products_import', 'ProductController@import');

    //orders
    $router->get('orders', 'OrderController@index');
    $router->get('orders/create', 'OrderController@create');
    $router->post('orders', 'OrderController@store');
    $router->get('orders/{id}', 'OrderController@show');
    $router->put('orders/{id}', 'OrderController@update');
    $router->get('orders/{id}/pdf', 'OrderController@showPdf');

    //Order Xml
    $router->get('orders/xml/{id}', 'OrderXmlController@xml');
    $router->get('orders/download/{id}', 'OrderXmlController@download');
    $router->get('orders/sendsri/{id}', 'WSSriOrderController@send');
    $router->get('orders/authorize/{id}', 'WSSriOrderController@authorize');

    //shops
    $router->get('shops', 'ShopController@index');
    $router->get('shops/create', 'ShopController@create');
    $router->post('shops', 'ShopController@store');
    $router->get('shops/{id}', 'ShopController@show');

    //Retention Xml
    $router->get('retentions/xml/{id}', 'RetentionXmlController@xml');
    $router->get('retentions/download/{id}', 'OrderXmlController@downloadXml');
    $router->get('retentions/sendsri/{id}', 'WSSriRetentionController@sendSri');
    $router->get('retentions/authorize/{id}', 'WSSriRetentionController@authorize');

    //Customers
    $router->get('customers', 'CustomerController@index');
    $router->post('customers', 'CustomerController@store');
    $router->get('customers/{id}/edit', 'CustomerController@edit');
    $router->put('customers/{id}', 'CustomerController@update');

    // Proveedores
    $router->get('providers', 'ProviderController@index');
    $router->post('providers', 'ProviderController@store');
    $router->get('providers/{id}/edit', 'ProviderController@edit');
    $router->put('providers/{id}', 'ProviderController@update');
});
