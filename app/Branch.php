<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = ['company_id', 'store', 'address', 'name', 'type'];

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function movements()
    {
        return $this->hasMany(Movement::class);
    }

    public function unities()
    {
        return $this->hasMany(Unity::class);
    }

    public function chartaccounts()
    {
        return $this->hasMany(ChartAccount::class);
    }
}
