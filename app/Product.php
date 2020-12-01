<?php
namespace App; use App\Library\Helper; use Illuminate\Database\Eloquent\Model; use Illuminate\Support\Facades\Log as LogWriter; class Product extends Model { protected $guarded = array(); protected $hidden = array(); const ID_API = -1001; const DELIVERY_AUTO = 0; const DELIVERY_MANUAL = 1; const DELIVERY_API = 2; function getUrlAttribute() { return config('app.url') . '/p/' . Helper::id_encode($this->id, Helper::ID_TYPE_PRODUCT); } function getCountAttribute() { return $this->count_all - $this->count_sold; } function category() { return $this->belongsTo(Category::class); } function cards() { return $this->hasMany(Card::class); } function coupons() { return $this->hasMany(Coupon::class); } function orders() { return $this->hasMany(Order::class); } function user() { return $this->belongsTo(User::class); } public static function refreshCount($spb14cf0) { \App\Card::where('user_id', $spb14cf0->id)->selectRaw('`product_id`,SUM(`count_sold`) as `count_sold`,SUM(`count_all`) as `count_all`')->groupBy('product_id')->orderByRaw('`product_id`')->chunk(1000, function ($sp1432b9) { foreach ($sp1432b9 as $sp4c126d) { $spe7d79d = \App\Product::where('id', $sp4c126d->product_id)->first(); if ($spe7d79d) { if ($spe7d79d->delivery === \App\Product::DELIVERY_MANUAL) { $spe7d79d->update(array('count_sold' => $sp4c126d->count_sold)); } else { $spe7d79d->update(array('count_sold' => $sp4c126d->count_sold, 'count_all' => $sp4c126d->count_all)); } } else { } } }); } function createApiCards($sp600a89) { $spe1c2dd = array(); $sp2887bd = array(); $sp072569 = array(); for ($spe89d11 = 0; $spe89d11 < $sp600a89->count; $spe89d11++) { $spe1c2dd[] = strtoupper(str_random(16)); $sp622402 = date('Y-m-d H:i:s'); switch ($this->id) { case 6: $spaa0f95 = 1; break; case 11: $spaa0f95 = 2; break; case 37: $spaa0f95 = 3; break; default: die('App.Products fatal error#1'); } $sp072569[] = array('user_id' => $this->user_id, 'product_id' => $this->id, 'card' => $spe1c2dd[$spe89d11], 'type' => \App\Card::TYPE_ONETIME, 'status' => \App\Card::STATUS_NORMAL, 'count_sold' => 0, 'count_all' => 1); $sp2887bd[] = "(NULL, '{$spe1c2dd[$spe89d11]}', '1', '{$spaa0f95}', NULL, NULL, NULL, NULL, NULL, '0', '{$sp622402}', '0000-00-00 00:00:00')"; } $spb64b87 = mysqli_connect('localhost', 'udiddz', 'tRihPm3sh6yKedtX', 'udiddz', '3306'); $sp7e7d51 = 'INSERT INTO `udiddz`.`ac_kms` (`id`, `km`, `value`, `task`, `udid`, `diz`, `task_id`, `install_url`, `plist_url`, `jh`, `addtime`, `tjtime`) VALUES ' . join(',', $sp2887bd); $sp204119 = mysqli_query($spb64b87, $sp7e7d51); if (!$sp204119) { LogWriter::error('App.Products, connect udid database failed', array('sql' => $sp7e7d51, 'error' => mysqli_error($spb64b87))); return array(); } $this->count_all += $sp600a89->count; return $this->cards()->createMany($sp072569); } function setForShop($spb14cf0 = null) { $spe7d79d = $this; $spdba6bb = $spe7d79d->count; $sp315e13 = $spe7d79d->inventory; if ($sp315e13 == User::INVENTORY_AUTO) { $sp315e13 = System::_getInt('shop_inventory'); } if ($sp315e13 == User::INVENTORY_RANGE) { if ($spdba6bb <= 0) { $sp7c063c = '不足'; } elseif ($spdba6bb <= 10) { $sp7c063c = '少量'; } elseif ($spdba6bb <= 20) { $sp7c063c = '一般'; } else { $sp7c063c = '大量'; } $spe7d79d->setAttribute('count2', $sp7c063c); } else { $spe7d79d->setAttribute('count2', $spdba6bb); } $spe7d79d->setAttribute('count', $spdba6bb); $spe7d79d->setVisible(array('id', 'name', 'description', 'fields', 'delivery', 'count', 'count2', 'buy_min', 'buy_max', 'support_coupon', 'password_open', 'price', 'price_whole')); } }