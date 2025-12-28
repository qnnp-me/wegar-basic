<?php

namespace Wegar\Basic\Init;

use plugin\admin\app\common\Util;
use plugin\admin\app\model\Rule;
use Wegar\Basic\Abstract\InitAbstract;

class PermissionTSGenerator extends InitAbstract
{

  function run(): void
  {
    if (is_phar() || !\Wegar\Basic\env('DEBUG')) return;
    if (
      ($save_path = config('app.permission_types_save_path', \Wegar\Basic\env('PERMISSION_TYPES_SAVE_PATH')))
      && ($content = $this->generate_type_content())
    ) {
      file_put_contents($save_path, $content);
    }
  }

  function generate_type_content(): string
  {
    if (!class_exists(Rule::class)) return '';
    $keys = Rule::select(['key'])->pluck('key');
    $date = date('Y-m-d H:i:s');
    $permissions = <<<TS
      // 此文件由 Wegar Basic 生成，请勿手动修改 {$date}
      declare type AdminPermission =
        '*'
      TS;
    foreach ($keys as $key) {
      if (!$key = Util::controllerToUrlPath($key)) {
        continue;
      }
      $code = str_replace('/', '.', trim($key, '/'));
      $permissions .= "  | '$code'\n";
    }
    return $permissions;
  }
}
