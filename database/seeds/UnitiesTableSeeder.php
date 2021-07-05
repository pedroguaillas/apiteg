<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitiesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('unities')->insert([
            'branch_id' => 1,
            'unity' => 'unidad'
        ]);
    }
}
