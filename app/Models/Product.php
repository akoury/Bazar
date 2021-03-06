<?php

namespace App\Models;

use Carbon\Carbon;
use App\Classes\Reservation;
use App\Traits\ProductInformation;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Exceptions\NotEnoughItemsException;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Exceptions\UnpublishedProductException;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory, ProductInformation, SoftDeletes;

    protected $guarded = [];

    protected $dates = ['deleted_at'];

    public function orders()
    {
        return Order::whereIn('id', $this->items()->pluck('order_id'));
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function values()
    {
        return $this->belongsToMany(Value::class)->withTimestamps();
    }

    public function model()
    {
        return $this->belongsTo(ProductModel::class, 'product_model_id')->withTrashed();
    }

    public function price()
    {
        return number_format($this->price / 100, 2);
    }

    public function getFullNameAttribute()
    {
        $values = $this->values;

        if ($values->isEmpty()) {
            return $this->model->name;
        }

        return $this->model->name . ' (' . $values->implode('name', ', ') . ')';
    }

    public function reserveItems($quantity, $email = null)
    {
        if (! $this->published) {
            throw new UnpublishedProductException;
        }

        $items = DB::transaction(function () use ($quantity) {
            // Finds items and locks them to avoid race conditions
            $items = $this->items()->available()->take($quantity)->lockForUpdate()->get();

            if ($items->count() < $quantity) {
                throw new NotEnoughItemsException;
            }

            $items->transform(function ($item) {
                $item->price = $this->price;
                $item->reserve();
                return $item;
            });

            return $items;
        });

        if ($email) {
            return new Reservation($email, $items);
        }

        return $items;
    }

    public function addItems($quantity)
    {
        $now = Carbon::now()->toDateTimeString();
        $chunkedItems = array_chunk(array_fill(0, $quantity, ['product_id' => $this->id, 'created_at' => $now, 'updated_at' => $now]), 200);

        foreach ($chunkedItems as $items) {
            Item::insert($items);
        }

        return $this;
    }

    public function itemsRemaining()
    {
        return $this->items()->available()->count();
    }

    public function setItemsRemaining($quantity)
    {
        $availableItems = $this->items()->available();
        $availableCount = $availableItems->count();

        if ($availableCount <= $quantity) {
            $this->addItems($quantity - $availableCount);
        } else {
            $availableItems->take($availableCount - $quantity)->delete();
        }

        return $this;
    }

    public function itemsSold()
    {
        return $this->items()->sold()->count();
    }

    public function revenue()
    {
        return $this->orders()->sum('amount') / 100;
    }

    public static function fromCart($cart)
    {
        return self::find($cart->products->pluck('id'));
    }

    public function url($brandId = null)
    {
        return route('products.show', [$brandId ?? $this->brand_id, $this->id]);
    }
}
