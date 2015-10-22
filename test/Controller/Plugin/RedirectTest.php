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
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Http\PhpEnvironment\Request;

class RedirectTest extends PHPUnit_Framework_TestCase
{
    /** @var Redirect */
    private $plugin;

    /** @var ServiceLocatorInterface */
    private $serviceLocator;

    protected function setUp()
    {
        $this->serviceLocator = $this->prophesize('Zend\ServiceManager\ServiceLocatorInterface');
        $this->controller     = $this->prophesize('Zend\Mvc\Controller\AbstractActionController');

        $this->redirect = new Redirect();
    }

    public function testAllowNotRoutedUrl()
    {
        $url = '/foo';

        $this->serviceLocator->get('config')
                             ->willReturn(['allow_not_routed_url' => true]);

        $mvcEvent = $this->prophesize('Zend\Mvc\MvcEvent');
        $response = $this->prophesize('Zend\Http\PhpEnvironment\Response');
        $mvcEvent->getResponse()->willReturn($response);

        $headers = $this->prophesize('Zend\Http\Headers');
        $headers->addHeaderLine('Location', $url);
        $response->getHeaders()->willReturn($headers);
        $response->setStatusCode(302)->shouldBeCalled();

        $this->controller->getEvent()->willReturn($mvcEvent);
        $this->controller->getServiceLocator()->willReturn($this->serviceLocator);
        $this->redirect->setController($this->controller->reveal());
        $this->redirect->toUrl($url);
    }
}
