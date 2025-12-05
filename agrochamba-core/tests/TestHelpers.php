<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class TestHelpers extends TestCase
{
    public function test_agrochamba_get_optimized_image_url_card()
    {
        $id = 123;
        $url = agrochamba_get_optimized_image_url($id, 'card');
        $this->assertIsString($url);
        $this->assertStringContainsString('/' . $id . '/agrochamba_card', $url);
    }

    public function test_agrochamba_get_optimized_image_url_detail()
    {
        $id = 456;
        $url = agrochamba_get_optimized_image_url($id, 'detail');
        $this->assertIsString($url);
        $this->assertStringContainsString('/' . $id . '/agrochamba_detail', $url);
    }

    public function test_agrochamba_get_optimized_image_url_thumb()
    {
        $id = 789;
        $url = agrochamba_get_optimized_image_url($id, 'thumb');
        $this->assertIsString($url);
        $this->assertStringContainsString('/' . $id . '/agrochamba_thumb', $url);
    }
}
