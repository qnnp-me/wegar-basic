<?php

return [
  'enable' => true,
  // phar 环境下由 ReleaseFiles 释放到宿主可写路径的默认清单（from => to）。
  // 把 database/ 释放到 Phinx 运行目录，配合 phinx.php 的
  // runtime_path('phinx/database/migrations|seeds') 生效（见 README「打包文件自动释放」）。
  // 宿主可用 config/extract.php 的 extract.list 或 config/app.php 的 build_release
  // 覆盖/追加（同 from 以宿主为准）。
  'release' => [
    'database/' => runtime_path('phinx'),
  ],
];
