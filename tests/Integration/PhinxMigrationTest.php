<?php

namespace Tests\Integration;

class PhinxMigrationTest extends HostTestCase
{
  public function testMigrateAndSeedAgainstSqlite(): void
  {
    $dir = $this->hostDir();
    $db = $dir . '/runtime/test.sqlite';
    @mkdir($dir . '/runtime', 0777, true);

    file_put_contents($dir . '/phinx.php', <<<PHP
<?php
return [
  'paths' => [
    'migrations' => __DIR__ . '/database/migrations',
    'seeds' => __DIR__ . '/database/seeds',
  ],
  'environments' => [
    'default_migration_table' => 'phinxlog',
    'default_environment' => 'default',
    'default' => [
      'adapter' => 'sqlite',
      'name' => '$db',
      'suffix' => '',
    ],
  ],
];
PHP);

    file_put_contents($dir . '/database/migrations/20260921000000_create_demo_items.php', <<<'PHP'
<?php

use Phinx\Migration\AbstractMigration;

class CreateDemoItems extends AbstractMigration
{
  public function change(): void
  {
    $this->table('demo_items')
      ->addColumn('name', 'string', ['limit' => 64])
      ->create();
  }
}
PHP);

    file_put_contents($dir . '/database/seeds/DemoSeeder.php', <<<'PHP'
<?php

use Phinx\Seed\AbstractSeed;

class DemoSeeder extends AbstractSeed
{
  public function run(): void
  {
    $this->table('demo_items')->insert([['name' => 'alpha']])->save();
  }
}
PHP);

    [$migrateExit, $migrateOut] = $this->runHost(['php', 'webman', 'phinx', 'migrate', '-e', 'default']);
    $this->assertSame(0, $migrateExit, $migrateOut);
    [$seedExit, $seedOut] = $this->runHost(['php', 'webman', 'phinx', 'seed:run', '-e', 'default']);
    $this->assertSame(0, $seedExit, $seedOut);

    $pdo = new \PDO('sqlite:' . $db);
    $rows = $pdo->query('select name from demo_items')->fetchAll(\PDO::FETCH_COLUMN);
    $this->assertSame(['alpha'], $rows);
  }
}
