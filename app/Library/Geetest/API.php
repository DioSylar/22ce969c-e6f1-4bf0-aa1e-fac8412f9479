<?php
namespace App\Library\Geetest; use Illuminate\Support\Facades\Session; class API { private $geetest_conf = null; public function __construct($sp8a1f10) { $this->geetest_conf = $sp8a1f10; } public static function get() { $spbabe1d = config('services.geetest.id'); $spe57121 = config('services.geetest.key'); if (!strlen($spbabe1d) || !strlen($spe57121)) { return null; } $spc641e0 = new Lib($spbabe1d, $spe57121); $spdad372 = time() . rand(1, 10000); $sp31859f = $spc641e0->pre_process($spdad372); $sp6a22d6 = json_decode($spc641e0->get_response_str()); Session::put('gt_server', $sp31859f); Session::put('gt_user_id', $spdad372); return $sp6a22d6; } public static function verify($spedc927, $spfff427, $sp4662f8) { $spc641e0 = new Lib(config('services.geetest.id'), config('services.geetest.key')); $spdad372 = Session::get('gt_user_id'); if (Session::get('gt_server') == 1) { $spf77edc = $spc641e0->success_validate($spedc927, $spfff427, $sp4662f8, $spdad372); if ($spf77edc) { return true; } else { return false; } } else { if ($spc641e0->fail_validate($spedc927, $spfff427, $sp4662f8)) { return true; } else { return false; } } } }