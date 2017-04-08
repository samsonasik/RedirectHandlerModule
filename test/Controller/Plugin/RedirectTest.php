<?php

/**
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

namespace RedirectHandlerModuleTest\Controller\Plugin;

use InvalidArgumentException;
use RedirectHandlerModule\Controller\Plugin\Redirect;
use Zend\EventManager\EventManager;
use Zend\Http\Headers;
use Zend\Http\PhpEnvironment\Request;
use Zend\Http\PhpEnvironment\Response;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\Controller\ControllerManager;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\Http\TreeRouteStack as V2TreeRouteStack;
use Zend\Mvc\Router\RouteMatch as V2RouteMatch;
use Zend\Router\Http\TreeRouteStack as V3TreeRouteStack;
use Zend\Router\RouteMatch as V3RouteMatch;
use PHPUnit\Framework\TestCase;

class RedirectTest extends TestCase
{
    private $controllerManager;
    private $controller;

    protected function setUp()
    {
        $this->controller = $this->prophesize(AbstractActionController::class);
        $this->controllerManager = $this->prophesize(ControllerManager::class);
    }

    public function testAllowNotRoutedUrl()
    {
        $redirect = new Redirect(
            [
                'allow_not_routed_url' => true,
                'default_url' => '/',
            ],
            $this->controllerManager->reveal()
        );

        $url = '/foo';

        $mvcEvent = $this->prophesize(MvcEvent::class);
        $response = $this->prophesize(Response::class);
        $mvcEvent->getResponse()->willReturn($response);
        $this->controller->getEvent()->willReturn($mvcEvent);

        $headers = $this->prophesize(Headers::class);
        $headers->addHeaderLine('Location', $url);
        $response->getHeaders()->willReturn($headers);
        $response->setStatusCode(302)->shouldBeCalled();

        $redirect->setController($this->controller->reveal());
        $redirect->toUrl($url);
    }

    public function testExcludedUrls()
    {
        $redirect = new Redirect(
            [
                'allow_not_routed_url' => false,
                'default_url' => '/',
                'options' => [
                    'exclude_urls' => [
                        'https://www.github.com/samsonasik/RedirectHandlerModule',
                    ],
                ],
            ],
            $this->controllerManager->reveal()
        );

        $url = 'https://www.github.com/samsonasik/RedirectHandlerModule';

        $mvcEvent = $this->prophesize(MvcEvent::class);
        $response = $this->prophesize(Response::class);
        $mvcEvent->getResponse()->willReturn($response);
        $this->controller->getEvent()->willReturn($mvcEvent);

        $headers = $this->prophesize(Headers::class);
        $headers->addHeaderLine('Location', $url);
        $response->getHeaders()->willReturn($headers);
        $response->setStatusCode(302)->shouldBeCalled();

        $redirect->setController($this->controller->reveal());
        $redirect->toUrl($url);
    }

    public function testExcludedHosts()
    {
        $redirect = new Redirect(
            [
                'allow_not_routed_url' => false,
                'default_url' => '/',
                'options' => [
                    'exclude_hosts' => [
                        'www.github.com',
                    ],
                ],
            ],
            $this->controllerManager->reveal()
        );

        $url = 'https://www.github.com/samsonasik/RedirectHandlerModule';

        $mvcEvent = $this->prophesize(MvcEvent::class);
        $response = $this->prophesize(Response::class);
        $mvcEvent->getResponse()->willReturn($response);
        $this->controller->getEvent()->willReturn($mvcEvent);

        $headers = $this->prophesize(Headers::class);
        $headers->addHeaderLine('Location', $url);
        $response->getHeaders()->willReturn($headers);
        $response->setStatusCode(302)->shouldBeCalled();

        $redirect->setController($this->controller->reveal());
        $redirect->toUrl($url);
    }

    public function testExcludedDomainsWithInvalidDomain()
    {
        $this->setExpectedException(InvalidArgumentException::class);

        $redirect = new Redirect(
            [
                'allow_not_routed_url' => false,
                'default_url' => '/',
                'options' => [
                    'exclude_domains' => [
                        'github.com',
                        'example.invalid',
                    ],
                ],
            ],
            $this->controllerManager->reveal()
        );

        $url = 'https://www.github.com/samsonasik/RedirectHandlerModule';
        $redirect->toUrl($url);
    }

    public function testExcludedDomains()
    {
        $redirect = new Redirect(
            [
                'allow_not_routed_url' => false,
                'default_url' => '/',
                'options' => [
                    'exclude_domains' => [
                        'github.com',
                    ],
                ],
            ],
            $this->controllerManager->reveal()
        );

        $url = 'https://www.github.com/samsonasik/RedirectHandlerModule';

        $mvcEvent = $this->prophesize(MvcEvent::class);
        $response = $this->prophesize(Response::class);
        $mvcEvent->getResponse()->willReturn($response);
        $this->controller->getEvent()->willReturn($mvcEvent);

        $headers = $this->prophesize(Headers::class);
        $headers->addHeaderLine('Location', $url);
        $response->getHeaders()->willReturn($headers);
        $response->setStatusCode(302)->shouldBeCalled();

        $redirect->setController($this->controller->reveal());
        $redirect->toUrl($url);
    }

    public function testExcludedDomainsButDifferentDomainInUrl()
    {
        $redirect = new Redirect(
            [
                'allow_not_routed_url' => false,
                'default_url' => '/',
                'options' => [
                    'exclude_domains' => [
                        'github.com',
                    ],
                ],
            ],
            $this->controllerManager->reveal()
        );

        $url = 'https://www.google.com/search';

        $request = $this->prophesize(Request::class);
        $request->getBasePath()->willReturn('')->shouldBeCalled();
        $request->getRequestUri()->willReturn('/bar')->shouldBeCalled();
        $request->setUri($url)->shouldBeCalled();
        $this->controller->getRequest()->willReturn($request);

        $mvcEvent = $this->prophesize(MvcEvent::class);

        if (class_exists(V3RouteMatch::class)) {
            $routeMatch = $this->prophesize(V3RouteMatch::class);
        } else {
            $routeMatch = $this->prophesize(V2RouteMatch::class);
        }

        if (class_exists(V3TreeRouteStack::class)) {
            $router = $this->prophesize(V3TreeRouteStack::class);
        } else {
            $router = $this->prophesize(V2TreeRouteStack::class);
        }

        $mvcEvent->getRouteMatch()->willReturn($routeMatch);
        $mvcEvent->getRouter()->willReturn($router);

        $router->getRequestUri()->willReturn('http://localhost/bar');
        $router->match($request)->willReturn(null);

        $response = $this->prophesize(Response::class);
        $mvcEvent->getResponse()->willReturn($response);

        $headers = $this->prophesize(Headers::class);
        $headers->addHeaderLine('Location', '/');
        $response->getHeaders()->willReturn($headers);
        $response->setStatusCode(302)->shouldBeCalled();

        $this->controller->getEvent()->willReturn($mvcEvent);
        $redirect->setController($this->controller->reveal());

        $redirect->toUrl($url);
    }

    public function provideMatches()
    {
        if (class_exists(V3RouteMatch::class)) {
            $routeMatch1 = $this->prophesize(V3RouteMatch::class);
        } else {
            $routeMatch1 = $this->prophesize(V2RouteMatch::class);
        }

        $routeMatch1->getParam('controller')->willReturn('not-bar')->shouldBeCalled();
        $routeMatch1->getParam('middleware')->willReturn(false)->shouldBeCalled();
        $routeMatch1->getMatchedRouteName()->willReturn('not-bar')->shouldBeCalled();

        if (class_exists(V3RouteMatch::class)) {
            $routeMatch2 = $this->prophesize(V3RouteMatch::class);
        } else {
            $routeMatch2 = $this->prophesize(V2RouteMatch::class);
        }
        $routeMatch2->getParam('controller')->willReturn('bar')->shouldBeCalled();
        $routeMatch2->getParam('middleware')->willReturn(false)->shouldBeCalled();
        $routeMatch2->getMatchedRouteName()->willReturn('bar')->shouldBeCalled();
        $routeMatch2->getParam('action')->willReturn('bar')->shouldBeCalled();

        if (class_exists(V3RouteMatch::class)) {
            $routeMatch3 = $this->prophesize(V3RouteMatch::class);
        } else {
            $routeMatch3 = $this->prophesize(V2RouteMatch::class);
        }
        $routeMatch3->getParam('controller')->willReturn('not-registered')->shouldBeCalled();
        $routeMatch3->getParam('middleware')->willReturn('bar')->shouldBeCalled();
        $routeMatch3->getMatchedRouteName()->willReturn('bar')->shouldBeCalled();
        $routeMatch3->getParam('action')->willReturn('bar')->shouldBeCalled();

        return [
            [
                'isnull',
                null,
                [
                    'allow_not_routed_url' => false,
                    'default_url' => '/',
                ]
            ],

            [
                'not-bar',
                $routeMatch1,
                [
                    'allow_not_routed_url' => false,
                    'default_url' => '/',
                ]
            ],

            [
                'bar',
                $routeMatch2,
                [
                    'allow_not_routed_url' => false,
                    'default_url' => '/',
                ]
            ],

            [
                'bar',
                $routeMatch2,
                [
                    'allow_not_routed_url' => false,
                    'default_url' => '/',
                ],
                'http://www.google.com'
            ],

            [
                'bar',
                $routeMatch3,
                [
                    'allow_not_routed_url' => false,
                    'default_url' => '/',
                ]
            ],

            [
                'isnull',
                null,
                [
                    'allow_not_routed_url' => false,
                ]
            ],

            [
                'not-bar',
                $routeMatch1,
                [
                    'allow_not_routed_url' => false,
                ]
            ],

            [
                'bar',
                $routeMatch2,
                [
                    'allow_not_routed_url' => false,
                ]
            ],

            [
                'bar',
                $routeMatch3,
                [
                    'allow_not_routed_url' => false,
                ]
            ],
        ];
    }

    /**
     * @dataProvider provideMatches
     */
    public function testDisallowNotRoutedUrl($status, $match, $config, $url = '/foo')
    {
        $redirect = new Redirect(
            $config,
            $this->controllerManager->reveal()
        );

        $request = $this->prophesize(Request::class);
        $request->getBasePath()->willReturn('')->shouldBeCalled();
        $request->getRequestUri()->willReturn('/bar')->shouldBeCalled();
        $request->setUri($url)->shouldBeCalled();
        $this->controller->getRequest()->willReturn($request);

        $mvcEvent = $this->prophesize(MvcEvent::class);
        if (class_exists(V3RouteMatch::class)) {
            $routeMatch = $this->prophesize(V3RouteMatch::class);
        } else {
            $routeMatch = $this->prophesize(V2RouteMatch::class);
        }
        $routeMatch->getMatchedRouteName()->willReturn('bar')->shouldBeCalled();
        if ($status === 'bar' && $url !== 'http://www.google.com') {
            $routeMatch->getParam('action')->willReturn('foo')->shouldBeCalled();
        }
        $mvcEvent->getRouteMatch()->willReturn($routeMatch);

        if (class_exists(V3TreeRouteStack::class)) {
            $router = $this->prophesize(V3TreeRouteStack::class);
        } else {
            $router = $this->prophesize(V2TreeRouteStack::class);
        }

        $router->getRequestUri()->willReturn('http://localhost/bar');

        $router->match($request)->willReturn($match);
        $mvcEvent->getRouter()->willReturn($router);

        if ($status !== 'isnull') {
            $this->controllerManager->has($status)->willReturn(true);
        }

        $response = $this->prophesize(Response::class);
        $mvcEvent->getResponse()->willReturn($response);

        $headers = $this->prophesize(Headers::class);
        $headers->addHeaderLine('Location', $url);
        $response->getHeaders()->willReturn($headers);
        $response->setStatusCode(302)->shouldBeCalled();

        $this->controller->getEvent()->willReturn($mvcEvent);
        $redirect->setController($this->controller->reveal());
        $redirect->toUrl($url);
    }

    public function provideRedirectConfig()
    {
        return [
            [
                [
                    'allow_not_routed_url' => false,
                ],
            ],
            [
                [
                    'allow_not_routed_url' => false,
                    'default_url' => '/',
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideRedirectConfig
     *
     *  @param $config
     */
    public function testDisallowNotRoutedUrlWithSameUrlWithTriggerEvent($config)
    {
        $url = '/bar';

        $redirect = new Redirect(
            $config,
            $this->controllerManager->reveal()
        );

        $request = $this->prophesize(Request::class);
        $request->getBasePath()->willReturn('')->shouldBeCalled();
        $request->getRequestUri()->willReturn('/bar')->shouldBeCalled();
        $request->setUri($url)->shouldBeCalled();
        $this->controller->getRequest()->willReturn($request);

        $redirect->setController($this->controller->reveal());

        $eventManager = new EventManager();
        $eventManager->attach('redirect-same-url', function () {
            echo 'redirect to same url is not allowed.';
        });
        $redirect->setEventManager($eventManager);

        ob_start();
        $redirect->toUrl($url);
        $content = ob_get_clean();

        $this->assertEquals('redirect to same url is not allowed.', $content);
    }

    public function testDisallowNotRoutedUrlAndUrlSameWithDefaultUrl()
    {
        $url = '/bar';

        $redirect = new Redirect(
            [
                'allow_not_routed_url' => false,
                'default_url' => '/bar',
            ],
            $this->controllerManager->reveal()
        );

        $request = $this->prophesize(Request::class);
        $request->getBasePath()->willReturn('')->shouldBeCalled();
        $request->getRequestUri()->willReturn('/bar')->shouldBeCalled();
        $request->setUri($url)->shouldBeCalled();
        $this->controller->getRequest()->willReturn($request);

        $mvcEvent = $this->prophesize(MvcEvent::class);

        if (class_exists(V3RouteMatch::class)) {
            $routeMatch = $this->prophesize(V3RouteMatch::class);
        } else {
            $routeMatch = $this->prophesize(V2RouteMatch::class);
        }

        $mvcEvent->getRouteMatch()->willReturn($routeMatch);

        if (class_exists(V3TreeRouteStack::class)) {
            $router = $this->prophesize(V3TreeRouteStack::class);
        } else {
            $router = $this->prophesize(V2TreeRouteStack::class);
        }
        $router->match($request)->willReturn(null);

        $response = $this->prophesize(Response::class);
        $mvcEvent->getResponse()->willReturn($response);

        $this->controller->getEvent()->willReturn($mvcEvent);
        $redirect->setController($this->controller->reveal());

        $redirect->toUrl($url);
    }

    public function testSameUrlWithDifferentQueryParametersShouldBeRedirected()
    {
        $redirect = new Redirect(
            [
                'allow_not_routed_url' => false,
                'default_url' => '/',
            ],
            $this->controllerManager->reveal()
        );

        $url = '/bar?succes=1';

        $request = $this->prophesize(Request::class);
        $request->getBasePath()->willReturn('')->shouldBeCalled();
        $request->getRequestUri()->willReturn('/bar')->shouldBeCalled();
        $request->setUri($url)->shouldBeCalled();
        $this->controller->getRequest()->willReturn($request);

        $mvcEvent = $this->prophesize(MvcEvent::class);
        if (class_exists(V3RouteMatch::class)) {
            $routeMatch = $this->prophesize(V3RouteMatch::class);
        } else {
            $routeMatch = $this->prophesize(V2RouteMatch::class);
        }
        $routeMatch->getMatchedRouteName()->willReturn('bar')->shouldBeCalled();
        $routeMatch->getParam('action')->willReturn('bar')->shouldBeCalled();
        $mvcEvent->getRouteMatch()->willReturn($routeMatch);

        if (class_exists(V3TreeRouteStack::class)) {
            $router = $this->prophesize(V3TreeRouteStack::class);
        } else {
            $router = $this->prophesize(V2TreeRouteStack::class);
        }
        $router->getRequestUri()->willReturn('http://localhost/bar');

        if (class_exists(V3RouteMatch::class)) {
            $match = $this->prophesize(V3RouteMatch::class);
        } else {
            $match = $this->prophesize(V2RouteMatch::class);
        }

        $match->getMatchedRouteName()->willReturn('bar')->shouldBeCalled();
        $match->getParam('action')->willReturn('bar')->shouldBeCalled();

        $router->match($request)->willReturn($match);
        $mvcEvent->getRouter()->willReturn($router);

        $response = $this->prophesize(Response::class);
        $mvcEvent->getResponse()->willReturn($response);

        $headers = $this->prophesize(Headers::class);
        $headers->addHeaderLine('Location', $url);
        $response->getHeaders()->willReturn($headers);
        $response->setStatusCode(302)->shouldBeCalled();

        $this->controller->getEvent()->willReturn($mvcEvent);
        $redirect->setController($this->controller->reveal());
        $redirect->toUrl($url);
    }

    public function testNonEmptyBasePathAndUrlContainsBasePathShouldRedirectNotApplyBaseUrlIntoUrlPrefix()
    {
        $redirect = new Redirect(
            [
                'allow_not_routed_url' => false,
                'default_url' => '/',
            ],
            $this->controllerManager->reveal()
        );

        $url = '/app/public/bar';

        $request = $this->prophesize(Request::class);
        $request->getBasePath()->willReturn('/app/public')->shouldBeCalled();
        $request->getRequestUri()->willReturn('/app/public/foo')->shouldBeCalled();
        $request->setUri($url)->shouldBeCalled();
        $this->controller->getRequest()->willReturn($request);

        $mvcEvent = $this->prophesize(MvcEvent::class);
        if (class_exists(V3RouteMatch::class)) {
            $routeMatch = $this->prophesize(V3RouteMatch::class);
        } else {
            $routeMatch = $this->prophesize(V2RouteMatch::class);
        }
        $routeMatch->getMatchedRouteName()->willReturn('foo')->shouldBeCalled();
        $mvcEvent->getRouteMatch()->willReturn($routeMatch);

        if (class_exists(V3TreeRouteStack::class)) {
            $router = $this->prophesize(V3TreeRouteStack::class);
        } else {
            $router = $this->prophesize(V2TreeRouteStack::class);
        }
        $router->getRequestUri()->willReturn('http://localhost/app/public/bar');

        if (class_exists(V3RouteMatch::class)) {
            $match = $this->prophesize(V3RouteMatch::class);
        } else {
            $match = $this->prophesize(V2RouteMatch::class);
        }

        $match->getMatchedRouteName()->willReturn('bar')->shouldBeCalled();
        $match->getParam('controller')->willReturn('bar')->shouldBeCalled();
        $this->controllerManager->has('bar')->willReturn(true);

        $router->match($request)->willReturn($match);
        $mvcEvent->getRouter()->willReturn($router);

        $response = $this->prophesize(Response::class);
        $mvcEvent->getResponse()->willReturn($response);

        $headers = $this->prophesize(Headers::class);
        $headers->addHeaderLine('Location', $url);
        $response->getHeaders()->willReturn($headers);
        $response->setStatusCode(302)->shouldBeCalled();

        $this->controller->getEvent()->willReturn($mvcEvent);
        $redirect->setController($this->controller->reveal());
        $redirect->toUrl($url);
    }

    public function testNonEmptyBasePathAndUrlNotContainsBasePathShouldRedirectWithApplyBaseUrlIntoUrlPrefix()
    {
        $redirect = new Redirect(
            [
                'allow_not_routed_url' => false,
                'default_url' => '/',
            ],
            $this->controllerManager->reveal()
        );

        $url = '/bar';

        $request = $this->prophesize(Request::class);
        $request->getBasePath()->willReturn('/app/public')->shouldBeCalled();
        $request->getRequestUri()->willReturn('/app/public/foo')->shouldBeCalled();
        $request->setUri('/app/public' . $url)->shouldBeCalled();
        $this->controller->getRequest()->willReturn($request);

        $mvcEvent = $this->prophesize(MvcEvent::class);
        if (class_exists(V3RouteMatch::class)) {
            $routeMatch = $this->prophesize(V3RouteMatch::class);
        } else {
            $routeMatch = $this->prophesize(V2RouteMatch::class);
        }
        $routeMatch->getMatchedRouteName()->willReturn('foo')->shouldBeCalled();
        $mvcEvent->getRouteMatch()->willReturn($routeMatch);

        if (class_exists(V3TreeRouteStack::class)) {
            $router = $this->prophesize(V3TreeRouteStack::class);
        } else {
            $router = $this->prophesize(V2TreeRouteStack::class);
        }
        $router->getRequestUri()->willReturn('http://localhost/app/public/bar');

        if (class_exists(V3RouteMatch::class)) {
            $match = $this->prophesize(V3RouteMatch::class);
        } else {
            $match = $this->prophesize(V2RouteMatch::class);
        }

        $match->getMatchedRouteName()->willReturn('bar')->shouldBeCalled();
        $match->getParam('controller')->willReturn('bar')->shouldBeCalled();
        $this->controllerManager->has('bar')->willReturn(true);

        $router->match($request)->willReturn($match);
        $mvcEvent->getRouter()->willReturn($router);

        $response = $this->prophesize(Response::class);
        $mvcEvent->getResponse()->willReturn($response);

        $headers = $this->prophesize(Headers::class);
        $headers->addHeaderLine('Location', $url);
        $response->getHeaders()->willReturn($headers);
        $response->setStatusCode(302)->shouldBeCalled();

        $this->controller->getEvent()->willReturn($mvcEvent);
        $redirect->setController($this->controller->reveal());
        $redirect->toUrl($url);
    }
}
