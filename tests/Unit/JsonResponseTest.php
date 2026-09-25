<?php

namespace Tests\Unit;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\TestCase;

class JsonResponseTest extends TestCase
{
  private function body($response): array
  {
    return json_decode($response->rawBody(), true, 512, JSON_THROW_ON_ERROR);
  }

  public function testSuccessWrapsData(): void
  {
    $body = $this->body(\Wegar\Basic\json_success(['a' => 1]));
    $this->assertSame(['data' => ['a' => 1]], $body);
  }

  public function testSuccessMergesExtra(): void
  {
    $body = $this->body(\Wegar\Basic\json_success(['a' => 1], ['page' => 2]));
    $this->assertSame(['data' => ['a' => 1], 'page' => 2], $body);
  }

  public function testSuccessPaginatorBranch(): void
  {
    $paginator = $this->createStub(LengthAwarePaginator::class);
    $paginator->method('items')->willReturn(['x']);
    $paginator->method('total')->willReturn(9);

    $body = $this->body(\Wegar\Basic\json_success($paginator));
    $this->assertSame(['data' => ['x'], 'count' => 9], $body);
  }

  public function testErrorOmitsEmptyData(): void
  {
    $response = \Wegar\Basic\json_error('boom', 400);
    $this->assertSame(['code' => 400, 'msg' => 'boom'], $this->body($response));
    $this->assertSame(200, $response->getStatusCode());
  }

  public function testErrorIncludesDataWhenPresent(): void
  {
    $body = $this->body(\Wegar\Basic\json_error('boom', 500, ['k' => 'v']));
    $this->assertSame(['code' => 500, 'msg' => 'boom', 'data' => ['k' => 'v']], $body);
  }
}
