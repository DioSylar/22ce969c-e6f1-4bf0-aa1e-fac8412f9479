<?php
namespace App\Http\Controllers\Shop; use Illuminate\Database\Eloquent\Relations\Relation; use Illuminate\Http\Request; use App\Http\Controllers\Controller; use App\Library\Response; class Product extends Controller { function get(Request $sp3c91bd) { $sp8af541 = (int) $sp3c91bd->post('category_id'); if (!$sp8af541) { return Response::forbidden('请选择商品分类'); } $spa74819 = \App\Category::where('id', $sp8af541)->first(); if (!$spa74819) { return Response::forbidden('商品分类未找到'); } if ($spa74819->password_open && $sp3c91bd->post('password') !== $spa74819->password) { return Response::fail('分类密码输入错误'); } $sp6c9c2f = \App\Product::where('category_id', $sp8af541)->where('enabled', 1)->orderBy('sort')->get(); foreach ($sp6c9c2f as $spe7d79d) { $spe7d79d->setForShop(); } return Response::success($sp6c9c2f); } function verifyPassword(Request $sp3c91bd) { $sp5d967e = (int) $sp3c91bd->post('product_id'); if (!$sp5d967e) { return Response::forbidden('请选择商品'); } $spe7d79d = \App\Product::where('id', $sp5d967e)->first(); if (!$spe7d79d) { return Response::forbidden('商品未找到'); } if ($spe7d79d->password_open && $sp3c91bd->post('password') !== $spe7d79d->password) { return Response::fail('商品密码输入错误'); } return Response::success(); } }