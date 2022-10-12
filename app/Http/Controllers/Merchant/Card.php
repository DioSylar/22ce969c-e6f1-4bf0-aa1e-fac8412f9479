<?php
namespace App\Http\Controllers\Merchant; use App\Library\Response; use App\System; use Illuminate\Http\Request; use App\Http\Controllers\Controller; use Illuminate\Support\Facades\Auth; use Illuminate\Support\Facades\DB; use Illuminate\Support\Facades\Storage; class Card extends Controller { function get(Request $spe5a184, $spfe258e = false, $sp1e896b = false, $spe45da9 = false) { $spa8a4ff = $this->authQuery($spe5a184, \App\Card::class)->with(array('product' => function ($spa8a4ff) { $spa8a4ff->select(array('id', 'name')); })); $sp8336a0 = $spe5a184->input('search', false); $spdbda3a = $spe5a184->input('val', false); if ($sp8336a0 && $spdbda3a) { if ($sp8336a0 == 'id') { $spa8a4ff->where('id', $spdbda3a); } else { $spa8a4ff->where($sp8336a0, 'like', '%' . $spdbda3a . '%'); } } $sp23779d = (int) $spe5a184->input('category_id'); $sp89bb44 = $spe5a184->input('product_id', -1); if ($sp23779d > 0) { if ($sp89bb44 > 0) { $spa8a4ff->where('product_id', $sp89bb44); } else { $spa8a4ff->whereHas('product', function ($spa8a4ff) use($sp23779d) { $spa8a4ff->where('category_id', $sp23779d); }); } } $spaa7124 = $spe5a184->input('status'); if (strlen($spaa7124)) { $spa8a4ff->whereIn('status', explode(',', $spaa7124)); } $sp3b077b = (int) $spe5a184->input('onlyCanSell'); if ($sp3b077b) { $spa8a4ff->whereRaw('`count_all`>`count_sold`'); } $sp22f15e = $spe5a184->input('type'); if (strlen($sp22f15e)) { $spa8a4ff->whereIn('type', explode(',', $sp22f15e)); } $sp787b97 = $spe5a184->input('trashed') === 'true'; if ($sp787b97) { $spa8a4ff->onlyTrashed(); } if ($sp1e896b === true) { if ($sp787b97) { $spa8a4ff->forceDelete(); } else { \App\Card::_trash($spa8a4ff); } return Response::success(); } else { if ($sp787b97 && $spe45da9 === true) { \App\Card::_restore($spa8a4ff); return Response::success(); } else { $spa8a4ff->orderByRaw('`product_id`,`type`,`status`,`id`'); if ($spfe258e === true) { $sp363ce7 = ''; $spa8a4ff->chunk(100, function ($spc3aa7c) use(&$sp363ce7) { foreach ($spc3aa7c as $sp2173f5) { $sp363ce7 .= $sp2173f5->card . '
'; } }); $sp29fbb6 = 'export_cards_' . $this->getUserIdOrFail($spe5a184) . '_' . date('YmdHis') . '.txt'; $spdc767d = array('Content-type' => 'text/plain', 'Content-Disposition' => sprintf('attachment; filename="%s"', $sp29fbb6), 'Content-Length' => strlen($sp363ce7)); return response()->make($sp363ce7, 200, $spdc767d); } $sp32b355 = (int) $spe5a184->input('current_page', 1); $sp048014 = (int) $spe5a184->input('per_page', 20); $spdf0cee = $spa8a4ff->paginate($sp048014, array('*'), 'page', $sp32b355); return Response::success($spdf0cee); } } } function export(Request $spe5a184) { return self::get($spe5a184, true); } function trash(Request $spe5a184) { $this->validate($spe5a184, array('ids' => 'required|string')); $sp8152f4 = $spe5a184->post('ids'); $spa8a4ff = $this->authQuery($spe5a184, \App\Card::class)->whereIn('id', explode(',', $sp8152f4)); \App\Card::_trash($spa8a4ff); return Response::success(); } function restoreTrashed(Request $spe5a184) { $this->validate($spe5a184, array('ids' => 'required|string')); $sp8152f4 = $spe5a184->post('ids'); $spa8a4ff = $this->authQuery($spe5a184, \App\Card::class)->whereIn('id', explode(',', $sp8152f4)); \App\Card::_restore($spa8a4ff); return Response::success(); } function deleteTrashed(Request $spe5a184) { $this->validate($spe5a184, array('ids' => 'required|string')); $sp8152f4 = $spe5a184->post('ids'); $this->authQuery($spe5a184, \App\Card::class)->whereIn('id', explode(',', $sp8152f4))->forceDelete(); return Response::success(); } function deleteAll(Request $spe5a184) { return $this->get($spe5a184, false, true); } function restoreAll(Request $spe5a184) { return $this->get($spe5a184, false, false, true); } function add(Request $spe5a184) { $sp89bb44 = (int) $spe5a184->post('product_id'); $spc3aa7c = $spe5a184->post('card'); $sp22f15e = (int) $spe5a184->post('type', \App\Card::TYPE_ONETIME); $sp4c59e7 = $spe5a184->post('is_check') === 'true'; if (str_contains($spc3aa7c, '<') || str_contains($spc3aa7c, '>')) { return Response::fail('卡密不能包含 < 或 > 符号'); } $sp7aa9af = $this->getUserIdOrFail($spe5a184); $sp429e29 = $this->authQuery($spe5a184, \App\Product::class)->where('id', $sp89bb44); $sp429e29->firstOrFail(array('id')); if ($sp22f15e === \App\Card::TYPE_REPEAT) { if ($sp4c59e7) { if (\App\Card::where('product_id', $sp89bb44)->where('card', $spc3aa7c)->exists()) { return Response::fail('该卡密已经存在，添加失败'); } } $sp2173f5 = new \App\Card(array('user_id' => $sp7aa9af, 'product_id' => $sp89bb44, 'card' => $spc3aa7c, 'type' => \App\Card::TYPE_REPEAT, 'count_sold' => 0, 'count_all' => (int) $spe5a184->post('count_all', 1))); if ($sp2173f5->count_all < 1 || $sp2173f5->count_all > 10000000) { return Response::forbidden('可售总次数不能超过10000000'); } return DB::transaction(function () use($sp429e29, $sp2173f5) { $sp2173f5->saveOrFail(); $spb395ca = $sp429e29->lockForUpdate()->firstOrFail(); $spb395ca->count_all += $sp2173f5->count_all; $spb395ca->saveOrFail(); return Response::success(); }); } else { $sp30a549 = explode('
', $spc3aa7c); $sp18533f = count($sp30a549); $sp09a859 = 50000; if ($sp18533f > $sp09a859) { return Response::fail('每次添加不能超过 ' . $sp09a859 . ' 张'); } $sp2e6c45 = array(); if ($sp4c59e7) { $sp5d033e = \App\Card::where('user_id', $sp7aa9af)->where('product_id', $sp89bb44)->get(array('card'))->all(); foreach ($sp5d033e as $sp0345f7) { $sp2e6c45[] = $sp0345f7['card']; } } $spaa9edc = array(); $spedc10e = 0; for ($sp03f985 = 0; $sp03f985 < $sp18533f; $sp03f985++) { $speeccb4 = trim($sp30a549[$sp03f985]); if (strlen($speeccb4) < 1) { continue; } if (strlen($speeccb4) > 1024) { return Response::fail('第 ' . $sp03f985 . ' 张卡密 ' . $speeccb4 . ' 长度错误<br>卡密最大长度为1024'); } if ($sp4c59e7) { if (in_array($speeccb4, $sp2e6c45)) { continue; } $sp2e6c45[] = $speeccb4; } $spaa9edc[] = array('user_id' => $sp7aa9af, 'product_id' => $sp89bb44, 'card' => $speeccb4, 'type' => \App\Card::TYPE_ONETIME); $spedc10e++; } if ($spedc10e === 0) { return Response::success(); } return DB::transaction(function () use($sp429e29, $spaa9edc, $spedc10e) { \App\Card::insert($spaa9edc); $spb395ca = $sp429e29->lockForUpdate()->firstOrFail(); $spb395ca->count_all += $spedc10e; $spb395ca->saveOrFail(); return Response::success(); }); } } function edit(Request $spe5a184) { $spb3d6c6 = (int) $spe5a184->post('id'); $sp2173f5 = $this->authQuery($spe5a184, \App\Card::class)->findOrFail($spb3d6c6); if ($sp2173f5) { $spb87760 = $spe5a184->post('card'); $sp22f15e = (int) $spe5a184->post('type', \App\Card::TYPE_ONETIME); $sp0ae739 = (int) $spe5a184->post('count_all', 1); return DB::transaction(function () use($sp2173f5, $spb87760, $sp22f15e, $sp0ae739) { $sp2173f5 = \App\Card::where('id', $sp2173f5->id)->lockForUpdate()->firstOrFail(); $sp2173f5->card = $spb87760; $sp2173f5->type = $sp22f15e; if ($sp2173f5->type === \App\Card::TYPE_REPEAT) { if ($sp0ae739 < $sp2173f5->count_sold) { return Response::forbidden('可售总次数不能低于当前已售次数'); } if ($sp0ae739 < 1 || $sp0ae739 > 10000000) { return Response::forbidden('可售总次数不能超过10000000'); } $sp2173f5->count_all = $sp0ae739; } else { $sp2173f5->count_all = 1; } $sp2173f5->saveOrFail(); $spb395ca = $sp2173f5->product()->lockForUpdate()->firstOrFail(); $spb395ca->count_all -= $sp2173f5->count_all; $spb395ca->count_all += $sp0ae739; $spb395ca->saveOrFail(); return Response::success(); }); } return Response::success(); } }