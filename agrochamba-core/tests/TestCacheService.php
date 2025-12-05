<?php
use PHPUnit\Framework\TestCase;

// Asegurarse de que el bootstrap de tests se cargó
require_once __DIR__ . '/bootstrap.php';

class TestCacheService extends TestCase
{
    public function test_set_get_delete_transient_cache()
    {
        // Arrange
        $key = 'unit_test_key';
        $value = ['a' => 1, 'b' => 2];

        // Act: set
        $set = \AgroChamba\Services\CacheService::set($key, $value, 60);

        // Assert: set ok
        $this->assertTrue($set, 'CacheService::set debe retornar true.');

        // Act: get
        $got = \AgroChamba\Services\CacheService::get($key);

        // Assert: get ok
        $this->assertEquals($value, $got, 'CacheService::get debe retornar el mismo valor almacenado.');

        // Act: delete
        $deleted = \AgroChamba\Services\CacheService::delete($key);

        // Assert: delete ok
        $this->assertTrue($deleted, 'CacheService::delete debe retornar true al eliminar.');

        // Assert: ya no existe
        $this->assertFalse(\AgroChamba\Services\CacheService::get($key), 'Tras delete, get debe retornar false (no existe).');
    }
}
