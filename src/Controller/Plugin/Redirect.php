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

namespace RedirectHandlerModule\Controller\Plugin;

use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\Mvc\Controller\ControllerManager;
use Zend\Mvc\Controller\Plugin\Redirect as BaseRedirect;

class Redirect extends BaseRedirect implements EventManagerAwareInterface
{
    use EventManagerAwareTrait;

    /**
     * @var array
     */
    private $config;

    /**
     * @var ControllerManager
     */
    private $manager;

    public function __construct(
        array $redirectHandlerConfig,
        ControllerManager $manager
    ){
        $this->config = $redirectHandlerConfig;
        $this->manager = $manager;
    }

    /**
     * Redirect with Handling against url
     *
     * @param  string $url
     * @return Response
     */
    public function toUrl($url)
    {
        $allow_not_routed_url = (isset($this->config['allow_not_routed_url']))
            ? $this->config['allow_not_routed_url']
            : false;
        $default_url          = (isset($this->config['default_url']))
            ? $this->config['default_url']
            : '/';

        if (true === $allow_not_routed_url) {
            return parent::toUrl($url);
        }

        $controller = $this->getController();

        $request     = $controller->getRequest();
        $current_url = $request->getRequestUri();
        $request->setUri($url);

        if ($current_url === $url) {
            $this->getEventManager()->trigger('redirect-same-url');
            return;
        }

        $mvcEvent              = $this->getEvent();
        $currentRouteMatchName = $mvcEvent->getRouteMatch()->getMatchedRouteName();
        $router                = $mvcEvent->getRouter();

        if ($routeToBeMatched = $router->match($request)) {
            $controller = $routeToBeMatched->getParam('controller');
            $middleware = $routeToBeMatched->getParam('middleware');

            if ($routeToBeMatched->getMatchedRouteName() !== $currentRouteMatchName
                && (
                    $this->manager->has($controller) ||
                    $middleware !== false
                )
            ) {
                return parent::toUrl($url);
            }
        }

        return parent::toUrl($default_url);
    }
}
