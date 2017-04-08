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

use RedirectHandlerModule\Controller\Plugin\RedirectFactory;
use PHPUnit\Framework\TestCase;

class RedirectFactoryTest extends TestCase
{
    private $factory;

    protected function setUp()
    {
        $this->factory = new RedirectFactory();
    }

    public function provideInvoke()
    {
        return [
            [[]],
            [
                [
                    'redirect_handler_module' => [
                        'allow_not_routed_url' => false,
                        'default_url' => '/',
                    ],
                ],
            ],
            [
                [
                    'redirect_handler_module' => [
                        'allow_not_routed_url' => true,
                        'default_url' => '/',
                    ],
                ],
            ],
            [
                [
                    'redirect_handler_module' => [
                        'allow_not_routed_url' => false,
                        'default_url' => '/',
                        'options' => [
                            'exclude_urls' => [
                                'https://www.github.com/samsonasik/RedirectHandlerModule',
                            ],
                            'exclude_hosts' => [
                                'www.github.com'
                            ],
                            'exclude_domains' => [
                                'github.com',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideInvoke
     */
    public function testInvoke($config)
    {
        $services = $this->prophesize('Zend\ServiceManager\ServiceLocatorInterface');

        $controllerManager = $this->prophesize('Zend\Mvc\Controller\ControllerManager');
        $services->get('ControllerManager')->willReturn($controllerManager)
                                           ->shouldBeCalled();

        $services->get('config')->willReturn($config)
                                ->shouldBeCalled();

        $this->factory->__invoke($services->reveal());
    }

    public function testInvokeWithServiceLocatorAwareInterfaceInstance()
    {
        $config = [];
        $services = $this->prophesize('Zend\ServiceManager\ServiceLocatorInterface');
        $controllerManager = $this->prophesize('Zend\Mvc\Controller\ControllerManager');
        $controllerManager->getServiceLocator()->willReturn($services)
                                               ->shouldBeCalled();

        $services->get('config')->willReturn($config)
                               ->shouldBeCalled();

        $this->factory->__invoke($controllerManager->reveal());
    }
}
