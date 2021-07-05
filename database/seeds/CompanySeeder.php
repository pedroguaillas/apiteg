<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('companies')->insert([
            'ruc' => '0691775097001',
            'company' => 'CONSTRUIR MAS',
            'name' => NULL,
            'economic_activity' => 'Construcción',
            'date' => new \DateTime('2020-01-01'),
            'special' => NULL,
            'address' => 'CHIMBORAZO / RIOBAMBA / MALDONADO / ALMAGRO 17-35 Y CHILE',
            'accounting' => true
        ]);

        DB::table('branches')->insert([
            'company_id' => 1,
            'store' => '001',
            'address' => 'Riobamba',
            'name' => 'Sucursar Riobamba'
        ]);

        DB::table('categories')->insert([
            'branch_id' => 1,
            'category' => 'Sin categoria',
            'type' => 'Producto',
            'buy' => true,
            'sale' => true
        ]);
    }
}
