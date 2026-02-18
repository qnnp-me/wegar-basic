<?php

namespace Wegar\Basic\Helper;

use ArrayAccess;
use JsonSerializable;
use LogicException;

class DTO implements ArrayAccess, JsonSerializable
{
  protected mixed $data;
  /**
   * 是否深度转换
   */
  protected bool $deep = true;

  public function __construct(array $data)
  {
    $this->data = ($data);
  }

  public function __get($name)
  {
    return $this->getValue($name);
  }

  protected function getValue($name)
  {
    if (is_array($this->data[$name] ?? null) && !array_is_list($this->data[$name])) {
      return new DTO($this->data[$name]);
    }
    return $this->data[$name] ?? null;
  }

  public function value()
  {
    return $this->data;
  }

  public function offsetExists(mixed $offset): bool
  {
    return isset($this->data[$offset]);
  }

  public function offsetGet(mixed $offset): mixed
  {
    return $this->getValue($offset);
  }

  public function offsetSet(mixed $offset, mixed $value): void
  {
    throw new LogicException(class_basename($this) . ' 为纯数据对象，不能设置数据');
  }

  public function offsetUnset(mixed $offset): void
  {
    throw new LogicException(class_basename($this) . ' 为纯数据对象，不能删除数据');
  }

  public function jsonSerialize(): mixed
  {
    return $this->data;
  }
}
