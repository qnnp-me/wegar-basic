<?php

return [
  'enable' => true,
  // phar 环境下由 ReleaseFiles 释放到宿主可写路径的默认清单（from => to）。
  // 仅释放数据库迁移/种子文件，且不覆盖宿主已有文件；宿主可用 config/extract.php
  // 的 extract.list 或 config/app.php 的 build_release 追加/覆盖（同 from 以宿主为准）。
  'release' => [
    'database/migrations' => base_path('database/migrations'),
    'database/seeds' => base_path('database/seeds'),
  ],
];
