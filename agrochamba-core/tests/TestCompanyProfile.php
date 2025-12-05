<?php

use PHPUnit\Framework\TestCase;
use AgroChamba\API\Profile\CompanyProfile;

class TestCompanyProfile extends TestCase
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
        $_GET = [];
    }

    public function test_routes_registered_once(): void
    {
        CompanyProfile::init();
        do_action('rest_api_init');
        $routes = rest_get_server()->get_routes();
        $this->assertArrayHasKey('/agrochamba/v1/me/company-profile', $routes);
        $this->assertArrayHasKey('/agrochamba/v1/companies/(?P<user_id>\d+)/profile', $routes);
        $this->assertArrayHasKey('/agrochamba/v1/companies/profile', $routes);

        $c1 = count($routes['/agrochamba/v1/me/company-profile']);
        $c2 = count($routes['/agrochamba/v1/companies/(?P<user_id>\d+)/profile']);
        $c3 = count($routes['/agrochamba/v1/companies/profile']);

        // Reintentar registro
        CompanyProfile::init();
        do_action('rest_api_init');
        $routes2 = rest_get_server()->get_routes();
        $this->assertSame($c1, count($routes2['/agrochamba/v1/me/company-profile']));
        $this->assertSame($c2, count($routes2['/agrochamba/v1/companies/(?P<user_id>\d+)/profile']));
        $this->assertSame($c3, count($routes2['/agrochamba/v1/companies/profile']));
    }

    public function test_get_my_company_profile_requires_auth(): void
    {
        $resp = CompanyProfile::get_my_company_profile(null);
        $this->assertInstanceOf(WP_Error::class, $resp);
        $this->assertSame(401, $resp->get_error_data()['status']);
    }

    public function test_get_my_company_profile_forbidden_for_non_enterprise(): void
    {
        $GLOBALS['__ac_current_user_logged_in'] = true;
        $GLOBALS['__ac_current_user_id'] = 5;
        $GLOBALS['__ac_users'][5] = (object) [
            'ID' => 5,
            'user_login' => 'worker',
            'display_name' => 'Worker',
            'user_email' => 'w@example.com',
            'roles' => ['subscriber'],
        ];

        $resp = CompanyProfile::get_my_company_profile(null);
        $this->assertInstanceOf(WP_Error::class, $resp);
        $this->assertSame(403, $resp->get_error_data()['status']);
    }

    public function test_get_my_company_profile_success(): void
    {
        $GLOBALS['__ac_current_user_logged_in'] = true;
        $GLOBALS['__ac_current_user_id'] = 6;
        $GLOBALS['__ac_users'][6] = (object) [
            'ID' => 6,
            'user_login' => 'acme',
            'display_name' => 'ACME Inc',
            'user_email' => 'acme@example.com',
            'roles' => ['employer'],
        ];
        update_user_meta(6, 'company_description', 'Desc');
        update_user_meta(6, 'company_website', 'https://acme.test');
        update_user_meta(6, 'company_phone', '+51 999');
        update_user_meta(6, 'company_address', 'Main St');

        $resp = CompanyProfile::get_my_company_profile(null);
        $this->assertInstanceOf(WP_REST_Response::class, $resp);
        $data = $resp->get_data();
        $this->assertSame(6, $data['user_id']);
        $this->assertSame('ACME Inc', $data['company_name']);
        $this->assertSame('https://acme.test', $data['company_website']);
    }

    public function test_update_my_company_profile_sanitization(): void
    {
        $GLOBALS['__ac_current_user_logged_in'] = true;
        $GLOBALS['__ac_current_user_id'] = 7;
        $GLOBALS['__ac_users'][7] = (object) [
            'ID' => 7,
            'user_login' => 'corp',
            'display_name' => 'Corp',
            'user_email' => 'c@example.com',
            'roles' => ['administrator'],
        ];

        $req = new class {
            public function get_json_params() {
                return [
                    'description' => " <b>Cool</b> \n Co ",
                    'phone' => '+51 (123) 456 789 ext 1',
                    'website' => 'ftp://invalid.test',
                ];
            }
        };

        $resp = CompanyProfile::update_my_company_profile($req);
        $this->assertInstanceOf(WP_REST_Response::class, $resp);
        $this->assertSame('Cool  Co', get_user_meta(7, 'company_description', true));
        $this->assertSame('+51 123 456 789 ext 1', get_user_meta(7, 'company_phone', true));
        // esc_url_raw bloquea esquemas no http/https
        $this->assertSame('', get_user_meta(7, 'company_website', true));
    }

    public function test_public_get_by_id_validations(): void
    {
        // Crear usuario no empresa
        $GLOBALS['__ac_users'][8] = (object) [
            'ID' => 8,
            'user_login' => 'person',
            'display_name' => 'Person',
            'user_email' => 'p@example.com',
            'roles' => ['subscriber'],
        ];

        $req = new class {
            public function get_param($name) { return 8; }
        };
        $resp = CompanyProfile::get_company_profile_by_id($req);
        $this->assertInstanceOf(WP_Error::class, $resp);
        $this->assertSame(400, $resp->get_error_data()['status']);

        // Convertir en empresa y reintentar
        $GLOBALS['__ac_users'][8]->roles = ['employer'];
        update_user_meta(8, 'company_description', 'Ok');
        $resp2 = CompanyProfile::get_company_profile_by_id($req);
        $this->assertInstanceOf(WP_REST_Response::class, $resp2);
        $this->assertSame(200, $resp2->get_status());
    }

    public function test_public_get_by_name(): void
    {
        $GLOBALS['__ac_users'][9] = (object) [
            'ID' => 9,
            'user_login' => 'acme',
            'user_nicename' => 'acme',
            'display_name' => 'ACME',
            'user_email' => 'a@example.com',
            'roles' => ['employer'],
        ];
        $_GET['name'] = 'ACME';
        $resp = CompanyProfile::get_company_profile_by_name(null);
        $this->assertInstanceOf(WP_REST_Response::class, $resp);
        $this->assertSame(200, $resp->get_status());

        $_GET['name'] = '';
        $resp2 = CompanyProfile::get_company_profile_by_name(null);
        $this->assertInstanceOf(WP_Error::class, $resp2);
        $this->assertSame(400, $resp2->get_error_data()['status']);
    }
}
