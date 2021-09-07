<?php

namespace App\Http\Controllers;

use App\Company;
use Illuminate\Http\Request;
use App\Voucher;
use App\Movement;
use App\Contact;
use App\MovementItem;
use App\Order;
use App\PayMethod;
use App\Product;
use App\Retention;
use App\Tax;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade as PDF;

class OrderController extends Controller
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

        $orders = Order::join('customers AS c', 'c.id', 'customer_id')
            ->select('orders.*', 'c.name')
            ->where('branch_id', $branch->id)
            ->get();

        return response()->json(['orders' => $orders]);
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
        $branch = $company->branches->first();

        return response()->json([
            'products' => $branch->products,
            'customers' => $branch->customers,
            'taxes' => Tax::all(),
            'series' => $this->getSeries($branch->id)
        ]);
    }

    private function getSeries($branch_id)
    {
        $invoice = Order::select('serie')
            ->where([
                ['branch_id', $branch_id], // De la sucursal especifico
                ['state', 'AUTORIZADO'], // El estado debe ser AUTORIZADO pero por el momento solo que este FIRMADO
                ['voucher_type', 1] // 1-Factura
            ])->orderBy('created_at', 'desc') // Para traer el ultimo
            ->first();

        $serie_retencion = Order::select('serie_retencion')
            ->where([
                ['branch_id', $branch_id], // De la sucursal especifico
                ['state_retencion', 'AUTORIZADO'], // El estado debe ser AUTORIZADO pero por el momento solo que este FIRMADO
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

        $dn = Order::select('serie')
            ->where([
                ['branch_id', $branch_id], // De la sucursal especifico
                ['state', 'AUTORIZADO'], // El estado debe ser AUTORIZADO pero por el momento solo que este FIRMADO
                ['voucher_type', 5] // 4-Nota-Debito
            ])->orderBy('created_at', 'desc') // Para traer el ultimo
            ->first();

        $new_obj = [
            'invoice' => $this->generedSerie($invoice),
            'cn' => $this->generedSerie($cn),
            'dn' => $this->generedSerie($dn),
            'retention' => $this->generedSerie($serie_retencion)
        ];

        return $new_obj;
    }

    //Return the serie of sales generated
    private function generedSerie($serie)
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
            $serie = '001-001-000000001';
        }

        return $serie;
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //Recalculate ajust decimal........Start
        $products = $request->get('products');

        $no_iva = 0;
        $base0 = 0;
        $base12 = 0;
        $discount = 0;

        if (count($products) > 0) {

            foreach ($products as $product) {
                $sub_total = $product['quantity'] * $product['price'];
                $dis = $product['discount'] > 0 ? round($sub_total * $product['discount'] * .01, 2) : 0;
                $total = $sub_total - $dis;
                $discount += $dis;

                switch ($product['iva']) {
                    case 0:
                        $base0 += $total;
                        break;
                    case 2:
                        $base12 += $total;
                        break;
                    case 6:
                        $no_iva += $total;
                        break;
                }
            }

            $iva = round($base12 * .12, 2);
            $sub_total = $no_iva + $base0 + $base12;
            $total = $sub_total + $iva;
        }

        //Recalculate ajust decimal...........End

        // Validar si no existe productos que se registre la retencion para poner en el monto

        $taxes = $request->get('taxes');

        if ($sub_total === 0) {
            foreach ($taxes as $tax) {
                if ($tax->code === 1) {
                    $iva += $tax->base;
                } else {
                    $sub_total += $tax->base;
                }
            }
            $total = $sub_total + $iva;
        }

        $id = $request->get('id');
        if ($id > 0) {
            $movement = Movement::find($id);
        } else {
            $movement = new Movement;
        }

        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        $date = $request->get('date');

        $movement->branch_id = $branch->id;
        $movement->date = $date;
        $movement->sub_total = $sub_total;
        $description = $request->get('description');
        if ($description === null || $description === '') {
            $description = 'Venta ' . $movement->date;
        }
        $movement->description = $description;
        $movement->seat_generate = true; //Only true not is necesary by generate seat

        if ($movement->save()) {
            // Data Voucher
            if ($id > 0) {
                $voucher = Voucher::where('movement_id', $id)->first();
            } else {
                $voucher = new Voucher;
            }

            $voucher->movement_id = $movement->id;
            $voucher->serie = $request->get('serie');
            $voucher->contact_id = $request->get('contact_id');
            $voucher->expiration_days = $request->get('expiration_days');
            $voucher->doc_realeted = $request->get('doc_realeted');
            $voucher->no_iva = $no_iva;
            $voucher->base0 = $base0;
            $voucher->base12 = $base12;
            $voucher->iva = $iva;
            $voucher->discount = $discount;
            $voucher->total = $total;
            $voucher->voucher_type = $request->get('voucher_type');
            $voucher->pay_method = $request->get('pay_method');
            $voucher->paid = $voucher->total;
            $voucher->save();

            //Products from Voucher
            if ($id > 0) {
                $movement->movementitems()->delete();
            }

            $movement->movementitems()->createMany($products);

            if (isset($taxes) && count($taxes) > 0) {
                $form_retention = $request->get('form_retention');

                $retention = new Retention;
                $retention->vaucher_id = $movement->id;
                $retention->serie = $form_retention['serie'];
                $retention->date = $form_retention['date'];
                $retention->save();

                $taxes = $request->get('taxes');
                $retention->retentionitems()->createMany($taxes);
            }

            $pay_methods = $request->get('pay_methods');
            if (isset($pay_methods)) {
                $pay = $pay_methods[0];
                $pay['vaucher_id'] = $movement->id;
                PayMethod::create($pay);
            }

            if ($request->get('send')) {
                (new XmlVoucherController())->xml($movement->id);
            }
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Voucher  $voucher
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        $movement = Movement::join('vouchers', 'vouchers.movement_id', 'movements.id')
            ->select('movements.*', 'vouchers.*')
            ->where('movements.id', $id)
            ->first();

        $movement->seat_generate = $movement->seat_generate === 1;

        // $contacts = $branch->contacts;
        // Solo regresa el contacto de ese comprobante y se sabe que obligatoriamente cuando se crea se registro especificamente el cliente es de esa sucursal
        $contacts = Contact::where('id', $movement->contact_id)->get();

        // Una sucursal tiene productos pero puedo o no ser categorizados
        // Una sucursal tiene productos pero puedo o no tener centro de costos
        $products = Product::all();

        $movement_items = MovementItem::join('products', 'products.id', 'movement_items.product_id')
            ->select('movement_items.*', 'products.stock', 'products.iva')
            ->where('movement_id', $id)
            ->get();

        $retention = Retention::where('vaucher_id', $id)->get()->first();

        $retentionitems = null;
        if (isset($retention)) {
            $retentionitems = $retention->retentionitems;
        }

        $paymethods = PayMethod::where('vaucher_id', $id)->get();

        return response()->json([
            'movement' => $movement,
            'contacts' => $contacts,
            'products' => $products,
            'movement_items' => $movement_items,
            'retention' => $retention,
            'retentionitems' => $retentionitems,
            'paymethods' => $paymethods,
            'taxes' => Tax::all()
        ]);
    }

    public function showByContact($contact_id)
    {
        $vouchers = Voucher::select('movement_id', 'serie', 'total', 'date', 'state')
            ->join('movements', 'movements.id', 'vouchers.movement_id')
            ->where([
                ['contact_id', $contact_id],
                ['voucher_type', 1]
            ])->get();

        return response()->json(['documents' => $vouchers]);
    }

    public function showPdf($id)
    {
        $movement = Movement::join('vouchers', 'vouchers.movement_id', 'movements.id')
            ->join('contacts', 'vouchers.contact_id', 'contacts.id')
            ->select('movements.*', 'vouchers.*', 'contacts.*')
            ->where('movements.id', $id)
            ->first();

        $movement_items = MovementItem::join('products', 'products.id', 'movement_items.product_id')
            ->select('products.*', 'movement_items.*')
            ->where('movement_items.movement_id', $id)
            ->get();

        $auth = Auth::user();
        $level = $auth->companyusers->first();

        $company = Company::find($level->level_id);

        $keyaccess = (new \DateTime($movement->date))->format('dmY') . str_pad($movement->voucher_type, 2, '0', STR_PAD_LEFT) .
            $company->ruc . '1' . substr($movement->serie, 0, 3) .
            substr($movement->serie, 4, 3) . substr($movement->serie, 8, 9)
            // . $this->generateRandomNumericCode() . '1';
            . '123456781';

        $company->enviroment_type = (int)substr($movement->xml, -30, 1);

        $keyaccess .= (new XmlVoucherController())->generaDigitoModulo11($keyaccess);

        switch ($movement->voucher_type) {
            case 1:
                $pdf = PDF::loadView('vouchers/invoice', compact('movement', 'company', 'movement_items', 'keyaccess'));
                break;
            case 3:
                $pdf = PDF::loadView('vouchers/invoice', compact('movement', 'company', 'movement_items', 'keyaccess'));
                break;
            case 4:
                $invoice = Movement::select('date', 'serie')
                    ->join('vouchers', 'vouchers.movement_id', 'movements.id')
                    ->where('movements.id', $movement->doc_realeted)
                    ->first();

                $pdf = PDF::loadView('vouchers/creditnote', compact('movement', 'company', 'movement_items', 'keyaccess', 'invoice'));
                break;
        }

        return $pdf->stream();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Voucher  $voucher
     * @return \Illuminate\Http\Response
     */
    public function destroy(Voucher $voucher)
    {
        //
    }
}
