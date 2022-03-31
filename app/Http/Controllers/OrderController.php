<?php

namespace App\Http\Controllers;

use App\Company;
use App\Customer;
use App\Http\Resources\CustomerResources;
use App\Http\Resources\OrderResources;
use App\Http\Resources\ProductResources;
use Illuminate\Http\Request;
use App\Order;
use App\OrderAditional;
use App\OrderItem;
use App\Product;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade as PDF;

class OrderController extends Controller
{
    public function index()
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        $orders = Order::join('customers AS c', 'c.id', 'customer_id')
            ->select('orders.*', 'c.name')
            ->where('c.branch_id', $branch->id)
            ->orderBy('orders.created_at', 'DESC');

        return OrderResources::collection($orders->paginate());
    }

    public function orderlist(Request $request)
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        $search = $request->search;

        $orders = Order::join('customers AS c', 'c.id', 'customer_id')
            ->select('orders.*', 'c.name')
            ->where('orders.branch_id', $branch->id)
            ->where(function ($query) use ($search) {
                return $query->where('orders.serie', 'LIKE', "%$search%")
                    ->orWhere('c.name', 'LIKE', "%$search%");
            })
            ->orderBy('orders.created_at', 'DESC');

        return OrderResources::collection($orders->paginate());
    }

    public function create()
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        return response()->json([
            'series' => $this->getSeries($branch)
        ]);
    }

    private function getSeries($branch)
    {
        $branch_id = $branch->id;
        $invoice = Order::select('serie')
            ->where([
                ['branch_id', $branch_id], // De la sucursal especifico
                ['state', 'AUTORIZADO'], // El estado debe ser AUTORIZADO pero por el momento solo que este FIRMADO
                ['voucher_type', 1] // 1-Factura
            ])->orderBy('created_at', 'desc') // Para traer el ultimo
            ->first();

        $cn = Order::select('serie')
            ->where([
                ['branch_id', $branch_id], // De la sucursal especifico
                ['state', 'AUTORIZADO'], // El estado debe ser AUTORIZADO pero por el momento solo que este FIRMADO
                ['voucher_type', 4] // 4-Nota-Credito
            ])->orderBy('created_at', 'desc') // Para traer el ultimo
            ->first();

        $new_obj = [
            'invoice' => $this->generedSerie($invoice, $branch->store),
            'cn' => $this->generedSerie($cn, $branch->store),
        ];

        return $new_obj;
    }

    //Return the serie of sales generated
    private function generedSerie($serie, $branch_store)
    {
        if ($serie != null) {
            $serie = $serie->serie;
            //Convert string to array
            $serie = explode("-", $serie);
            //Get value Integer from String & sum 1
            $serie[2] = (int) $serie[2] + 1;
            //Complete 9 zeros to left 
            $serie[2] = str_pad($serie[2], 9, 0, STR_PAD_LEFT);
            //convert Array to String
            $serie = implode("-", $serie);
        } else {
            $serie = str_pad($branch_store, 3, 0, STR_PAD_LEFT) . '-010-000000001';
        }

        return $serie;
    }

    public function store(Request $request)
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        if ($order = $branch->orders()->create($request->except(['products', 'send', 'aditionals']))) {

            // Registro de los Items de la Orden
            $products = $request->get('products');

            if (count($products) > 0) {
                $array = [];
                foreach ($products as $product) {
                    $array[] = [
                        'product_id' => $product['product_id'],
                        'quantity' => $product['quantity'],
                        'price' => $product['price'],
                        'discount' => $product['discount']
                    ];
                }
                $order->orderitems()->createMany($array);
            }

            // Registro de la Informacion Adicional de la Orden
            $aditionals = $request->get('aditionals');

            if (count($aditionals) > 0) {
                $array = [];
                foreach ($aditionals as $aditional) {
                    if (($aditional['name'] !== null && $aditional['name'] !== '') && ($aditional['description'] !== null && $aditional['description'] !== '')) {
                        $array[] = [
                            'name' => $aditional['name'],
                            'description' => $aditional['description']
                        ];
                    }
                }

                if (count($array)) {
                    $order->orderaditionals()->createMany($array);
                }
            }

            if ($request->get('send')) {
                (new OrderXmlController())->xml($order->id);
            }
        }
    }

    public function show($id)
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        $order = Order::findOrFail($id);

        $products = Product::join('order_items AS oi', 'product_id', 'products.id')
            ->select('products.*')
            ->where('order_id', $order->id)
            ->get();

        $orderitems = Product::join('order_items AS oi', 'product_id', 'products.id')
            ->select('products.iva', 'oi.*')
            ->where('order_id', $order->id)
            ->get();

        $orderaditionals = OrderAditional::where('order_id', $order->id)->get();

        $customers = Customer::where('id', $order->customer_id)->get();

        return response()->json([
            'products' => ProductResources::collection($products),
            'customers' => CustomerResources::collection($customers),
            'order' => $order,
            'order_items' => $orderitems,
            'order_aditionals' => $orderaditionals,
            'series' => $this->getSeries($branch)
        ]);
    }

    public function showPdf($id)
    {
        $movement = Order::join('customers AS c', 'orders.customer_id', 'c.id')
            ->select('orders.*', 'c.*')
            ->where('orders.id', $id)
            ->first();

        $movement_items = OrderItem::join('products', 'products.id', 'order_items.product_id')
            ->select('products.*', 'order_items.*')
            ->where('order_items.order_id', $id)
            ->get();

        $orderaditionals = OrderAditional::where('order_id', $id)->get();

        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);

        switch ($movement->voucher_type) {
            case 1:
                // $pdf = PDF::loadView('vouchers/testinvoice', compact('movement', 'company', 'movement_items', 'orderaditionals'));
                // $pdf->setPaper('A7', 'portrait');
                // $pdf->setPaper(array(0, 0, 396, 1002), 'portrait');
                $pdf = PDF::loadView('vouchers/invoice', compact('movement', 'company', 'movement_items', 'orderaditionals'));
                break;
            case 4:
                $pdf = PDF::loadView('vouchers/creditnote', compact('movement', 'company', 'movement_items', 'orderaditionals'));
                break;
        }

        return $pdf->stream();
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if ($order->update($request->except(['id', 'products', 'send', 'aditionals']))) {

            // Actualizar los Items de la Orden
            $products = $request->get('products');

            if (count($products) > 0) {
                $array = [];
                foreach ($products as $product) {
                    $array[] = [
                        'product_id' => $product['product_id'],
                        'quantity' => $product['quantity'],
                        'price' => $product['price'],
                        'discount' => $product['discount']
                    ];
                }
                OrderItem::where('order_id', $order->id)->delete();
                $order->orderitems()->createMany($array);
            }

            // Actualizar la Informacion Adicional de la Orden
            $aditionals = $request->get('aditionals');

            // Eliminar todos los registros de informacion adicional
            OrderAditional::where('order_id', $order->id)->delete();

            if (count($aditionals) > 0) {
                $array = [];

                foreach ($aditionals as $aditional) {
                    if (($aditional['name'] !== null && $aditional['name'] !== '') && ($aditional['description'] !== null && $aditional['description'] !== '')) {
                        $array[] = [
                            'name' => $aditional['name'],
                            'description' => $aditional['description']
                        ];
                    }
                }

                if (count($array)) {
                    $order->orderaditionals()->createMany($array);
                }
            }
        }
    }
}
