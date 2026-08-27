<?php

namespace Tests\Feature;

use Tests\TestCase;

class SessionSecurityConfigTest extends TestCase
{
    private array $originalEnv = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalEnv['APP_ENV'] = env('APP_ENV');
        $this->originalEnv['SESSION_SECURE_COOKIE'] = env('SESSION_SECURE_COOKIE');
    }

    protected function tearDown(): void
    {
        $this->setEnv('APP_ENV', $this->originalEnv['APP_ENV']);
        $this->setEnv('SESSION_SECURE_COOKIE', $this->originalEnv['SESSION_SECURE_COOKIE']);
        parent::tearDown();
    }

    /**
     * Re-read config/session.php so the derivation runs against the
     * environment variables currently in force.
     *
     * @return array<string, mixed>
     */
    private function resolveSessionConfig(): array
    {
        return require config_path('session.php');
    }

    private function setEnv(string $name, ?string $value): void
    {
        if ($value === null) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);

            return;
        }

        putenv($name.'='.$value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    public function test_secure_cookie_is_true_when_app_env_is_production(): void
    {
        $this->setEnv('APP_ENV', 'production');
        $this->setEnv('SESSION_SECURE_COOKIE', null);

        $config = $this->resolveSessionConfig();

        $this->assertTrue($config['secure']);

        $this->app['config']->set('session', $config);
        $this->assertTrue(config('session.secure'));
    }

    public function test_secure_cookie_is_false_outside_production_by_default(): void
    {
        $this->setEnv('APP_ENV', 'local');
        $this->setEnv('SESSION_SECURE_COOKIE', null);

        $config = $this->resolveSessionConfig();

        $this->assertFalse($config['secure']);
    }

    public function test_explicit_session_secure_cookie_overrides_environment_default(): void
    {
        $this->setEnv('APP_ENV', 'local');
        $this->setEnv('SESSION_SECURE_COOKIE', 'true');

        $this->assertTrue($this->resolveSessionConfig()['secure']);

        $this->setEnv('APP_ENV', 'production');
        $this->setEnv('SESSION_SECURE_COOKIE', 'false');

        $this->assertFalse($this->resolveSessionConfig()['secure']);
    }

    public function test_http_only_is_true_regardless_of_environment(): void
    {
        $this->setEnv('APP_ENV', 'local');
        $this->assertTrue($this->resolveSessionConfig()['http_only']);

        $this->setEnv('APP_ENV', 'production');
        $this->assertTrue($this->resolveSessionConfig()['http_only']);
    }
}
