<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContactsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('contacts')->insert([
            'state' => 1,
            'type' => 1,
            'special' => false,
            'identication_card' => '9999999999999',
            'company' => 'CONSUMIDOR FINAL',
            'address' => 'not',
            'phone' => '999999999',
            'email' => 'not',
            'accounting' => false,
        ]);
    }
}
