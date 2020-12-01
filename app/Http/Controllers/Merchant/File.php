<?php
namespace App\Http\Controllers\Merchant; use App\Library\Response; use App\System; use function GuzzleHttp\Psr7\mimetype_from_filename; use Illuminate\Http\Request; use App\Http\Controllers\Controller; use Illuminate\Support\Facades\Auth; use Illuminate\Support\Facades\Storage; class File extends Controller { public static function uploadImg($spefa168, $spdad372, $spdeba28, $sp48d2d8 = false) { try { $sp9cc497 = $spefa168->extension(); } catch (\Throwable $sp96dd17) { return Response::fail($sp96dd17->getMessage()); } if (!$spefa168 || !in_array(strtolower($sp9cc497), array('jpg', 'jpeg', 'png', 'gif'))) { return Response::fail('图片错误, 系统支持jpg/png/gif格式'); } if ($spefa168->getSize() > 5 * 1024 * 1024) { return Response::fail('图片不能大于5MB'); } try { $sp5af18a = $spefa168->store($spdeba28, array('disk' => System::_get('storage_driver'))); } catch (\Exception $sp96dd17) { \Log::error('File.uploadImg folder:' . $spdeba28 . ', error:' . $sp96dd17->getMessage(), array('exception' => $sp96dd17)); if (config('app.debug')) { return Response::fail($sp96dd17->getMessage()); } else { return Response::fail('上传文件失败, 内部错误, 请联系客服'); } } if (!$sp5af18a) { return Response::fail('系统保存文件出错, 请稍后再试'); } $sp1feac0 = System::_get('storage_driver'); $sp40b98a = Storage::disk($sp1feac0)->url($sp5af18a); $sp4332c9 = \App\File::insertGetId(array('user_id' => $spdad372, 'driver' => $sp1feac0, 'path' => $sp5af18a, 'url' => $sp40b98a)); if ($sp4332c9 < 1) { Storage::disk($sp1feac0)->delete($sp5af18a); return Response::fail('数据库繁忙，请稍后再试'); } $sp6a22d6 = array('id' => $sp4332c9, 'url' => $sp40b98a, 'name' => pathinfo($sp5af18a, PATHINFO_BASENAME)); if ($sp48d2d8) { return $sp6a22d6; } return Response::success($sp6a22d6); } function upload_merchant(Request $sp3c91bd) { $spb14cf0 = $this->getUser($sp3c91bd); if ($spb14cf0 === null) { return Response::forbidden('无效的用户'); } $spefa168 = $sp3c91bd->file('file'); return $this->uploadImg($spefa168, $spb14cf0->id, \App\File::getProductFolder()); } public function renderImage(Request $sp3c91bd, $sp6bb33d) { if (str_contains($sp6bb33d, '..') || str_contains($sp6bb33d, './') || str_contains($sp6bb33d, '.\\') || !starts_with($sp6bb33d, 'images/')) { $sp60c0bb = file_get_contents(public_path('images/illegal.jpg')); } else { $sp6bb33d = str_replace('\\', '/', $sp6bb33d); $spefa168 = \App\File::wherePath($sp6bb33d)->first(); if ($spefa168) { $sp1feac0 = $spefa168->driver; } else { $sp1feac0 = System::_get('storage_driver'); } if (!in_array($sp1feac0, array('local', 's3', 'oss', 'qiniu'))) { return response()->view('message', array('title' => '404', 'message' => '404 Driver NotFound'), 404); } try { $sp60c0bb = Storage::disk($sp1feac0)->get($sp6bb33d); } catch (\Illuminate\Contracts\Filesystem\FileNotFoundException $sp96dd17) { \Log::error('File.renderImage error: ' . $sp96dd17->getMessage(), array('exception' => $sp96dd17)); return response()->view('message', array('title' => '404', 'message' => '404 NotFound'), 404); } } ob_end_clean(); header('Content-Type: ' . mimetype_from_filename($sp6bb33d)); die($sp60c0bb); } }