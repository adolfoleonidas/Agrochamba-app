<?php

use PHPUnit\Framework\TestCase;
use AgroChamba\API\Profile\UserProfile;

class TestUserProfile extends TestCase
{
    protected function setUp(): void
    {
        // Reset REST server routes before each test
        if (isset($GLOBALS['__ac_rest_server'])) {
            $GLOBALS['__ac_rest_server']->reset();
        }
        // Reset actions
        $GLOBALS['__ac_actions'] = [];

        // Reset users/auth
        $GLOBALS['__ac_current_user_logged_in'] = false;
        $GLOBALS['__ac_current_user_id'] = 0;
        $GLOBALS['__ac_users'] = [];
        $GLOBALS['__ac_user_meta'] = [];
    }

    public function test_routes_registered_once(): void
    {
        // Register routes via init hook
        UserProfile::init();
        do_action('rest_api_init');

        $routes = rest_get_server()->get_routes();
        $this->assertArrayHasKey('/agrochamba/v1/me/profile', $routes);
        $firstCount = count($routes['/agrochamba/v1/me/profile']);

        // Try to register again
        UserProfile::init();
        do_action('rest_api_init');
        $routes2 = rest_get_server()->get_routes();
        $secondCount = count($routes2['/agrochamba/v1/me/profile']);

        $this->assertSame($firstCount, $secondCount, 'Las rutas no deben duplicarse.');
    }

    public function test_get_profile_requires_auth(): void
    {
        $resp = UserProfile::get_profile(null);
        $this->assertInstanceOf(WP_Error::class, $resp);
        $this->assertSame('rest_forbidden', $resp->get_error_code());
        $data = $resp->get_error_data();
        $this->assertIsArray($data);
        $this->assertSame(401, $data['status']);
    }

    public function test_update_profile_sanitization_and_meta_updates(): void
    {
        // Create a logged-in user
        $GLOBALS['__ac_current_user_logged_in'] = true;
        $GLOBALS['__ac_current_user_id'] = 1;
        $GLOBALS['__ac_users'][1] = (object) [
            'ID' => 1,
            'user_login' => 'jdoe',
            'display_name' => 'John',
            'user_email' => 'john@example.com',
            'roles' => ['subscriber'],
        ];

        // Fake request with unsafe bio/phone
        $req = new class {
            public function get_json_params() {
                return [
                    'first_name' => ' John  ',
                    'last_name' => " <b>Doe</b> ",
                    'display_name' => " John <script>alert('x')</script> Doe ",
                    'bio' => '<p>Hola <strong>mundo</strong><script>x</script></p>',
                    'phone' => '+51 (999) 123-456 ext. 77',
                ];
            }
        };

        $resp = UserProfile::update_profile($req);
        $this->assertInstanceOf(WP_REST_Response::class, $resp);
        $this->assertSame(200, $resp->get_status());

        // Check updated display name sanitized (no tags)
        $this->assertSame('John  Doe', $GLOBALS['__ac_users'][1]->display_name);
        // Meta sanitization
        $this->assertSame('Hola mundo', get_user_meta(1, 'bio', true));
        $this->assertSame('+51 999 123-456 ext. 77', get_user_meta(1, 'phone', true));
    }

    public function test_update_profile_enterprise_url_sanitization(): void
    {
        // Employer role to unlock enterprise fields
        $GLOBALS['__ac_current_user_logged_in'] = true;
        $GLOBALS['__ac_current_user_id'] = 2;
        $GLOBALS['__ac_users'][2] = (object) [
            'ID' => 2,
            'user_login' => 'acme',
            'display_name' => 'ACME Inc',
            'user_email' => 'acme@example.com',
            'roles' => ['employer'],
        ];

        $req = new class {
            public function get_json_params() {
                return [
                    'company_website' => 'javascript:alert(1)',
                    'company_facebook' => 'https://facebook.com/acme',
                    'company_linkedin' => 'http://linkedin.com/company/acme',
                    'company_description' => '  <i>Great</i> Co.  ',
                ];
            }
        };

        $resp = UserProfile::update_profile($req);
        $this->assertInstanceOf(WP_REST_Response::class, $resp);
        $this->assertSame('', get_user_meta(2, 'company_website', true));
        $this->assertSame('https://facebook.com/acme', get_user_meta(2, 'company_facebook', true));
        $this->assertSame('http://linkedin.com/company/acme', get_user_meta(2, 'company_linkedin', true));
        $this->assertSame('Great Co.', get_user_meta(2, 'company_description', true));
    }
}
