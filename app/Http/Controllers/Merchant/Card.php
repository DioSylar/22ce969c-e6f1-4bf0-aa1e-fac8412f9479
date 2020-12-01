<?php
namespace App\Http\Controllers\Merchant; use App\Library\Response; use App\System; use Illuminate\Http\Request; use App\Http\Controllers\Controller; use Illuminate\Support\Facades\Auth; use Illuminate\Support\Facades\DB; use Illuminate\Support\Facades\Storage; class Card extends Controller { function get(Request $spba756f, $spe71412 = false, $sp90f231 = false, $sp3ff80d = false) { $spca8acc = $this->authQuery($spba756f, \App\Card::class)->with(array('product' => function ($spca8acc) { $spca8acc->select(array('id', 'name')); })); $speeddd9 = $spba756f->input('search', false); $spe1fc85 = $spba756f->input('val', false); if ($speeddd9 && $spe1fc85) { if ($speeddd9 == 'id') { $spca8acc->where('id', $spe1fc85); } else { $spca8acc->where($speeddd9, 'like', '%' . $spe1fc85 . '%'); } } $sp55f32c = (int) $spba756f->input('category_id'); $sp1b83a8 = $spba756f->input('product_id', -1); if ($sp55f32c > 0) { if ($sp1b83a8 > 0) { $spca8acc->where('product_id', $sp1b83a8); } else { $spca8acc->whereHas('product', function ($spca8acc) use($sp55f32c) { $spca8acc->where('category_id', $sp55f32c); }); } } $sp732cf7 = $spba756f->input('status'); if (strlen($sp732cf7)) { $spca8acc->whereIn('status', explode(',', $sp732cf7)); } $sp194b3c = (int) $spba756f->input('onlyCanSell'); if ($sp194b3c) { $spca8acc->whereRaw('`count_all`>`count_sold`'); } $spe7b1c7 = $spba756f->input('type'); if (strlen($spe7b1c7)) { $spca8acc->whereIn('type', explode(',', $spe7b1c7)); } $sp09ee91 = $spba756f->input('trashed') === 'true'; if ($sp09ee91) { $spca8acc->onlyTrashed(); } if ($sp90f231 === true) { if ($sp09ee91) { $spca8acc->forceDelete(); } else { \App\Card::_trash($spca8acc); } return Response::success(); } else { if ($sp09ee91 && $sp3ff80d === true) { \App\Card::_restore($spca8acc); return Response::success(); } else { $spca8acc->orderByRaw('`product_id`,`type`,`status`,`id`'); if ($spe71412 === true) { $sp93a446 = ''; $spca8acc->chunk(100, function ($sp137cd7) use(&$sp93a446) { foreach ($sp137cd7 as $spa9cef2) { $sp93a446 .= $spa9cef2->card . '
'; } }); $spe6c5dc = 'export_cards_' . $this->getUserIdOrFail($spba756f) . '_' . date('YmdHis') . '.txt'; $sp3e89cf = array('Content-type' => 'text/plain', 'Content-Disposition' => sprintf('attachment; filename="%s"', $spe6c5dc), 'Content-Length' => strlen($sp93a446)); return response()->make($sp93a446, 200, $sp3e89cf); } $sp881a75 = $spba756f->input('current_page', 1); $sp2a01a9 = $spba756f->input('per_page', 20); $spfea7ce = $spca8acc->paginate($sp2a01a9, array('*'), 'page', $sp881a75); return Response::success($spfea7ce); } } } function export(Request $spba756f) { return self::get($spba756f, true); } function trash(Request $spba756f) { $this->validate($spba756f, array('ids' => 'required|string')); $sp12026a = $spba756f->post('ids'); $spca8acc = $this->authQuery($spba756f, \App\Card::class)->whereIn('id', explode(',', $sp12026a)); \App\Card::_trash($spca8acc); return Response::success(); } function restoreTrashed(Request $spba756f) { $this->validate($spba756f, array('ids' => 'required|string')); $sp12026a = $spba756f->post('ids'); $spca8acc = $this->authQuery($spba756f, \App\Card::class)->whereIn('id', explode(',', $sp12026a)); \App\Card::_restore($spca8acc); return Response::success(); } function deleteTrashed(Request $spba756f) { $this->validate($spba756f, array('ids' => 'required|string')); $sp12026a = $spba756f->post('ids'); $this->authQuery($spba756f, \App\Card::class)->whereIn('id', explode(',', $sp12026a))->forceDelete(); return Response::success(); } function deleteAll(Request $spba756f) { return $this->get($spba756f, false, true); } function restoreAll(Request $spba756f) { return $this->get($spba756f, false, false, true); } function add(Request $spba756f) { $sp1b83a8 = (int) $spba756f->post('product_id'); $sp137cd7 = $spba756f->post('card'); $spe7b1c7 = (int) $spba756f->post('type', \App\Card::TYPE_ONETIME); $spd3d838 = $spba756f->post('is_check') === 'true'; if (str_contains($sp137cd7, '<') || str_contains($sp137cd7, '>')) { return Response::fail('卡密不能包含 < 或 > 符号'); } $spf5ae13 = $this->getUserIdOrFail($spba756f); $sp94e351 = $this->authQuery($spba756f, \App\Product::class)->where('id', $sp1b83a8); $sp94e351->firstOrFail(array('id')); if ($spe7b1c7 === \App\Card::TYPE_REPEAT) { if ($spd3d838) { if (\App\Card::where('product_id', $sp1b83a8)->where('card', $sp137cd7)->exists()) { return Response::fail('该卡密已经存在，添加失败'); } } $spa9cef2 = new \App\Card(array('user_id' => $spf5ae13, 'product_id' => $sp1b83a8, 'card' => $sp137cd7, 'type' => \App\Card::TYPE_REPEAT, 'count_sold' => 0, 'count_all' => (int) $spba756f->post('count_all', 1))); if ($spa9cef2->count_all < 1 || $spa9cef2->count_all > 10000000) { return Response::forbidden('可售总次数不能超过10000000'); } return DB::transaction(function () use($sp94e351, $spa9cef2) { $spa9cef2->saveOrFail(); $sp9dfc99 = $sp94e351->lockForUpdate()->firstOrFail(); $sp9dfc99->count_all += $spa9cef2->count_all; $sp9dfc99->saveOrFail(); return Response::success(); }); } else { $spb73662 = explode('
', $sp137cd7); $sp1def7a = count($spb73662); $sp9461d0 = 50000; if ($sp1def7a > $sp9461d0) { return Response::fail('每次添加不能超过 ' . $sp9461d0 . ' 张'); } $spcb374f = array(); if ($spd3d838) { $sp3d7961 = \App\Card::where('user_id', $spf5ae13)->where('product_id', $sp1b83a8)->get(array('card'))->all(); foreach ($sp3d7961 as $spb2b013) { $spcb374f[] = $spb2b013['card']; } } $spb26570 = array(); $spb668e0 = 0; for ($spc8f255 = 0; $spc8f255 < $sp1def7a; $spc8f255++) { $spec0853 = trim($spb73662[$spc8f255]); if (strlen($spec0853) < 1) { continue; } if (strlen($spec0853) > 255) { return Response::fail('第 ' . $spc8f255 . ' 张卡密 ' . $spec0853 . ' 长度错误<br>卡密最大长度为255'); } if ($spd3d838) { if (in_array($spec0853, $spcb374f)) { continue; } $spcb374f[] = $spec0853; } $spb26570[] = array('user_id' => $spf5ae13, 'product_id' => $sp1b83a8, 'card' => $spec0853, 'type' => \App\Card::TYPE_ONETIME); $spb668e0++; } if ($spb668e0 === 0) { return Response::success(); } return DB::transaction(function () use($sp94e351, $spb26570, $spb668e0) { \App\Card::insert($spb26570); $sp9dfc99 = $sp94e351->lockForUpdate()->firstOrFail(); $sp9dfc99->count_all += $spb668e0; $sp9dfc99->saveOrFail(); return Response::success(); }); } } function edit(Request $spba756f) { $sp8e8060 = (int) $spba756f->post('id'); $spa9cef2 = $this->authQuery($spba756f, \App\Card::class)->findOrFail($sp8e8060); if ($spa9cef2) { $spb66176 = $spba756f->post('card'); $spe7b1c7 = (int) $spba756f->post('type', \App\Card::TYPE_ONETIME); $sp5f45b6 = (int) $spba756f->post('count_all', 1); return DB::transaction(function () use($spa9cef2, $spb66176, $spe7b1c7, $sp5f45b6) { $spa9cef2 = \App\Card::where('id', $spa9cef2->id)->lockForUpdate()->firstOrFail(); $spa9cef2->card = $spb66176; $spa9cef2->type = $spe7b1c7; if ($spa9cef2->type === \App\Card::TYPE_REPEAT) { if ($sp5f45b6 < $spa9cef2->count_sold) { return Response::forbidden('可售总次数不能低于当前已售次数'); } if ($sp5f45b6 < 1 || $sp5f45b6 > 10000000) { return Response::forbidden('可售总次数不能超过10000000'); } $spa9cef2->count_all = $sp5f45b6; } else { $spa9cef2->count_all = 1; } $spa9cef2->saveOrFail(); $sp9dfc99 = $spa9cef2->product()->lockForUpdate()->firstOrFail(); $sp9dfc99->count_all -= $spa9cef2->count_all; $sp9dfc99->count_all += $sp5f45b6; $sp9dfc99->saveOrFail(); return Response::success(); }); } return Response::success(); } }