<?php

use PHPUnit\Framework\TestCase;
use AgroChamba\API\Profile\ProfilePhoto;

class TestProfilePhoto extends TestCase
{
    protected function setUp(): void
    {
        if (isset($GLOBALS['__ac_rest_server'])) {
            $GLOBALS['__ac_rest_server']->reset();
        }
        $GLOBALS['__ac_actions'] = [];
        $GLOBALS['__ac_current_user_logged_in'] = false;
        $GLOBALS['__ac_current_user_id'] = 0;
        $GLOBALS['__ac_users'] = [];
        $GLOBALS['__ac_user_meta'] = [];
    }

    public function test_routes_registered_once(): void
    {
        ProfilePhoto::init();
        do_action('rest_api_init');
        $routes = rest_get_server()->get_routes();
        $this->assertArrayHasKey('/agrochamba/v1/me/profile/photo', $routes);
        $count1 = count($routes['/agrochamba/v1/me/profile/photo']);

        ProfilePhoto::init();
        do_action('rest_api_init');
        $routes2 = rest_get_server()->get_routes();
        $count2 = count($routes2['/agrochamba/v1/me/profile/photo']);
        $this->assertSame($count1, $count2);
    }

    public function test_delete_requires_auth(): void
    {
        $resp = ProfilePhoto::delete_photo(null);
        $this->assertInstanceOf(WP_Error::class, $resp);
        $this->assertSame(401, $resp->get_error_data()['status']);
    }

    public function test_delete_success(): void
    {
        $GLOBALS['__ac_current_user_logged_in'] = true;
        $GLOBALS['__ac_current_user_id'] = 10;
        $GLOBALS['__ac_users'][10] = (object) [
            'ID' => 10,
            'user_login' => 'picuser',
            'display_name' => 'Pic User',
            'user_email' => 'pic@example.com',
            'roles' => ['subscriber'],
        ];
        update_user_meta(10, 'profile_photo_id', 123);

        $resp = ProfilePhoto::delete_photo(null);
        $this->assertInstanceOf(WP_REST_Response::class, $resp);
        $this->assertSame(200, $resp->get_status());
        $this->assertSame('', get_user_meta(10, 'profile_photo_id', true));
    }
}
