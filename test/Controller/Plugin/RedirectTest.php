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

use PHPUnit_Framework_TestCase;
use RedirectHandlerModule\Controller\Plugin\Redirect;
use Zend\EventManager\EventManager;
use Zend\Mvc\Controller\ControllerManager;

class RedirectTest extends PHPUnit_Framework_TestCase
{
    private $serviceLocator;
    private $controllerManager;
    private $controller;

    protected function setUp()
    {
        $this->serviceLocator    = $this->prophesize('Zend\ServiceManager\ServiceLocatorInterface');
        $this->controller        = $this->prophesize('Zend\Mvc\Controller\AbstractActionController');
        $this->controllerManager = $this->prophesize('Zend\Mvc\Controller\ControllerManager');
    }

    public function testAllowNotRoutedUrl()
    {
        $this->redirect = new Redirect(
            [
                'allow_not_routed_url' => true,
                'default_url' => '/'
            ],
            $this->controllerManager->reveal()
        );

        $url = '/foo';

        $mvcEvent = $this->prophesize('Zend\Mvc\MvcEvent');
        $response = $this->prophesize('Zend\Http\PhpEnvironment\Response');
        $mvcEvent->getResponse()->willReturn($response);
        $this->controller->getEvent()->willReturn($mvcEvent);

        $headers = $this->prophesize('Zend\Http\Headers');
        $headers->addHeaderLine('Location', $url);
        $response->getHeaders()->willReturn($headers);
        $response->setStatusCode(302)->shouldBeCalled();

        $this->redirect->setController($this->controller->reveal());
        $this->redirect->toUrl($url);
    }

    public function provideMatches()
    {
        $routeMatch1 = $this->prophesize('Zend\Mvc\Router\RouteMatch');
        $routeMatch1->getParam('controller')->willReturn('not-bar')->shouldBeCalled();
        $routeMatch1->getMatchedRouteName()->willReturn('not-bar')->shouldBeCalled();;
        $routeMatch2 = $this->prophesize('Zend\Mvc\Router\RouteMatch');
        $routeMatch2->getParam('controller')->willReturn('bar')->shouldBeCalled();
        $routeMatch2->getMatchedRouteName()->willReturn('bar')->shouldBeCalled();

        return array(
            array('isnull', null),
            array('not-bar', $routeMatch1),
            array('bar', $routeMatch2),
        );
    }

    /**
     * @dataProvider provideMatches
     */
    public function testDisallowNotRoutedUrl($status, $match)
    {
        $url = '/foo';

        $this->redirect = new Redirect(
            [
                'allow_not_routed_url' => false,
            ],
            $this->controllerManager->reveal()
        );

        $request = $this->prophesize('Zend\Http\PhpEnvironment\Request');
        $request->getRequestUri()->willReturn('/bar')->shouldBeCalled();
        $request->setUri($url)->shouldBeCalled();
        $this->controller->getRequest()->willReturn($request);

        $mvcEvent = $this->prophesize('Zend\Mvc\MvcEvent');
        $routeMatch = $this->prophesize('Zend\Mvc\Router\RouteMatch');
        $routeMatch->getMatchedRouteName()->willReturn('bar');
        $mvcEvent->getRouteMatch()->willReturn($routeMatch);

        $router = $this->prophesize('Zend\Mvc\Router\RouteInterface');
        $router->match($request)->willReturn($match);
        $mvcEvent->getRouter()->willReturn($router);

        if ($status !== 'isnull') {
            $this->controllerManager->has($status)->willReturn(true);
        }

        $response = $this->prophesize('Zend\Http\PhpEnvironment\Response');
        $mvcEvent->getResponse()->willReturn($response);

        $headers = $this->prophesize('Zend\Http\Headers');
        $headers->addHeaderLine('Location', $url);
        $response->getHeaders()->willReturn($headers);
        $response->setStatusCode(302)->shouldBeCalled();

        $this->controller->getEvent()->willReturn($mvcEvent);
        $this->redirect->setController($this->controller->reveal());
        $this->redirect->toUrl($url);
    }

    public function testDisallowNotRoutedUrlWithSameUrlWithTriggerEvent()
    {
        $url = '/bar';

        $this->redirect = new Redirect(
            [
                'allow_not_routed_url' => false,
            ],
            $this->controllerManager->reveal()
        );

        $request = $this->prophesize('Zend\Http\PhpEnvironment\Request');
        $request->getRequestUri()->willReturn('/bar')->shouldBeCalled();
        $request->setUri($url)->shouldBeCalled();
        $this->controller->getRequest()->willReturn($request);

        $this->redirect->setController($this->controller->reveal());

        $eventManager = new EventManager();
        $eventManager->attach('redirect-same-url', function() {
            echo 'redirect to same url is not allowed.';
        });
        $this->redirect->setEventManager($eventManager);

        ob_start();
        $this->redirect->toUrl($url);
        $content = ob_get_clean();

        $this->assertEquals('redirect to same url is not allowed.', $content);
    }

    public function testDisallowNotRoutedUrlAndUrlSameWithDefaultUrl()
    {
        $url = '/bar';

        $this->redirect = new Redirect(
            [
                'allow_not_routed_url' => false,
                'default_url' => '/bar'
            ],
            $this->controllerManager->reveal()
        );

        $request = $this->prophesize('Zend\Http\PhpEnvironment\Request');
        $request->getRequestUri()->willReturn('/bar')->shouldBeCalled();
        $request->setUri($url)->shouldBeCalled();
        $this->controller->getRequest()->willReturn($request);

        $mvcEvent = $this->prophesize('Zend\Mvc\MvcEvent');
        $routeMatch = $this->prophesize('Zend\Mvc\Router\RouteMatch');
        $mvcEvent->getRouteMatch()->willReturn($routeMatch);

        $router = $this->prophesize('Zend\Mvc\Router\RouteInterface');
        $router->match($request)->willReturn(null);
        $this->serviceLocator->get('Router')
                             ->willReturn($router);

        $response = $this->prophesize('Zend\Http\PhpEnvironment\Response');
        $mvcEvent->getResponse()->willReturn($response);

        $this->controller->getEvent()->willReturn($mvcEvent);
        $this->redirect->setController($this->controller->reveal());

        $this->redirect->toUrl($url);
    }
}
