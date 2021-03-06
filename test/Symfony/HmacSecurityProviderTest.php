<?php

namespace WouterAdem\Hmac\Test\Symfony;

use WouterAdem\Hmac\KeyLoaderInterface;
use WouterAdem\Hmac\Symfony\HmacAuthenticationEntryPoint;
use WouterAdem\Hmac\Symfony\HmacAuthenticationListener;
use WouterAdem\Hmac\Symfony\HmacAuthenticationProvider;
use WouterAdem\Hmac\Symfony\HmacResponseListener;
use WouterAdem\Hmac\Symfony\HmacSecurityProvider;
use Silex\Application;
use Silex\Provider\SecurityServiceProvider;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Tests the Silex service provider that adds HTTP HMAC as a firewall option.
 */
class HmacSecurityProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Ensures the service provider only loads if SecurityServiceProvider is present.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage You must register the SecurityServiceProvider to use the HmacServiceProvider.
     */
    public function testBootFailureWithoutSecurityServiceProvider()
    {
        $keyLoader = $this->getMock(KeyLoaderInterface::class);

        $app = new Application();
        $app->register(new HmacSecurityProvider($keyLoader));
        $app->boot();
    }

    /**
     * Ensures the service provider registers the response listener with the global dispatcher.
     */
    public function testResponseListenerIsRegisteredWithDispatcher()
    {
        $keyLoader = $this->getMock(KeyLoaderInterface::class);

        $app = new Application();
        $app->register(new SecurityServiceProvider());
        $app->register(new HmacSecurityProvider($keyLoader));

        $app['security.firewalls'] = [
            'http-auth' => [
                'pattern' => '^.*$',
                'hmac' => true,
            ],
        ];

        $app->boot();

        $this->assertTrue($app['dispatcher']->hasListeners(KernelEvents::RESPONSE));
    }

    /**
     * Ensures the service provider registers the correct services.
     */
    public function testRegisteredServices()
    {
        $firewall = 'http-auth';
        $authenticationListenerServices = [
            'security.authentication_provider.' . $firewall . '.hmac',
            'security.authentication_listener.' . $firewall . '.hmac',
            'security.entry_point.' . $firewall . '.hmac',
            'pre_auth',
        ];

        $keyLoader = $this->getMock(KeyLoaderInterface::class);

        $app = new Application();
        $app->register(new SecurityServiceProvider());
        $app->register(new HmacSecurityProvider($keyLoader));

        $app['security.firewalls'] = [
            $firewall => [
                'pattern' => '^.*$',
                'hmac' => true,
            ],
        ];

        $app->boot();

        $this->assertArrayHasKey('security.authentication_listener.factory.hmac', $app);
        $this->assertArrayHasKey('security.hmac.response_listener', $app);
        $this->assertArrayHasKey('security.authentication_provider.hmac._proto', $app);
        $this->assertArrayHasKey('security.authentication_listener.hmac._proto', $app);
        $this->assertArrayHasKey('security.entry_point.hmac._proto', $app);
        $this->assertInstanceOf(HmacResponseListener::class, $app['security.hmac.response_listener']);

        $factoryResponse = $app['security.authentication_listener.factory.hmac']($firewall, []);

        $this->assertEquals($authenticationListenerServices, $factoryResponse);

        $this->assertArrayHasKey('security.authentication_provider.' . $firewall . '.hmac', $app);
        $this->assertInstanceOf(HmacAuthenticationProvider::class, $app['security.authentication_provider.' . $firewall . '.hmac']);

        $this->assertArrayHasKey('security.authentication_listener.' . $firewall . '.hmac', $app);
        $this->assertInstanceOf(HmacAuthenticationListener::class, $app['security.authentication_listener.' . $firewall . '.hmac']);

        $this->assertArrayHasKey('security.entry_point.' . $firewall . '.hmac', $app);
        $this->assertInstanceOf(HmacAuthenticationEntryPoint::class, $app['security.entry_point.' . $firewall . '.hmac']);
    }
}
