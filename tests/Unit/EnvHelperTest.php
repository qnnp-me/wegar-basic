<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wegar\Basic\Helper\EnvHelper;

class EnvHelperTest extends TestCase
{
  public function testEnvKeyIsDerivedFromFileAndPath(): void
  {
    $this->assertSame(
      'DATABASE_CONNECTIONS_MYSQL_HOST',
      EnvHelper::envKey('database', ['connections', 'mysql', 'host'])
    );
  }

  public function testCandidatesOnlyPickConnectionAndSecretKeys(): void
  {
    $config = [
      'default' => 'mysql',
      'connections' => [
        'mysql' => [
          'driver' => 'mysql',
          'host' => '127.0.0.1',
          'port' => '3306',
          'database' => 'your_database',
          'username' => 'your_username',
          'password' => 'your_password',
          'charset' => 'utf8mb4',
          'strict' => true,
        ],
      ],
    ];

    $keys = array_map(
      static fn(array $c): string => $c['envKey'],
      EnvHelper::candidates($config, 'database')
    );

    $this->assertContains('DATABASE_CONNECTIONS_MYSQL_HOST', $keys);
    $this->assertContains('DATABASE_CONNECTIONS_MYSQL_PORT', $keys);
    $this->assertContains('DATABASE_CONNECTIONS_MYSQL_DATABASE', $keys);
    $this->assertContains('DATABASE_CONNECTIONS_MYSQL_USERNAME', $keys);
    $this->assertContains('DATABASE_CONNECTIONS_MYSQL_PASSWORD', $keys);
    $this->assertNotContains('DATABASE_DEFAULT', $keys);
    $this->assertNotContains('DATABASE_CONNECTIONS_MYSQL_DRIVER', $keys);
    $this->assertNotContains('DATABASE_CONNECTIONS_MYSQL_CHARSET', $keys);
    $this->assertNotContains('DATABASE_CONNECTIONS_MYSQL_STRICT', $keys);
  }

  public function testCandidatesSkipNonScalarLeaves(): void
  {
    $config = ['pool' => ['max_connections' => 5], 'host' => 'x'];
    $keys = array_map(
      static fn(array $c): string => $c['envKey'],
      EnvHelper::candidates($config, 'redis')
    );
    $this->assertContains('REDIS_HOST', $keys);
    // max_connections 未命中关键词，即便标量也不收集
    $this->assertNotContains('REDIS_POOL_MAX_CONNECTIONS', $keys);
  }

  public function testRewriteReplacesScalarLiteralWithEnvCall(): void
  {
    $source = <<<'PHP'
    <?php
    return [
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'password' => 'your_password',
            ],
        ],
    ];
    PHP;

    $candidates = EnvHelper::candidates([
      'connections' => ['mysql' => ['driver' => 'mysql', 'host' => '127.0.0.1', 'password' => 'your_password']],
    ], 'database');

    $result = EnvHelper::rewrite($source, $candidates);

    $this->assertSame(2, $result['changed']);
    $this->assertStringContainsString(
      "'host' => env('DATABASE_CONNECTIONS_MYSQL_HOST', '127.0.0.1')",
      $result['source']
    );
    $this->assertStringContainsString(
      "'password' => env('DATABASE_CONNECTIONS_MYSQL_PASSWORD', 'your_password')",
      $result['source']
    );
    // 未命中的 driver 保持原样
    $this->assertStringContainsString("'driver' => 'mysql'", $result['source']);
  }

  public function testRewriteIsIdempotent(): void
  {
    $source = <<<'PHP'
    <?php
    return [
        'host' => '127.0.0.1',
    ];
    PHP;

    $candidates = EnvHelper::candidates(['host' => '127.0.0.1'], 'redis');
    $once = EnvHelper::rewrite($source, $candidates);
    $twice = EnvHelper::rewrite($once['source'], $candidates);

    $this->assertSame(1, $once['changed']);
    $this->assertSame(0, $twice['changed']);
    $this->assertSame($once['source'], $twice['source']);
  }
}
