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
use Zend\Uri\Uri;

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
    ) {
        $this->config = $redirectHandlerConfig;
        $this->manager = $manager;
    }

    /**
     * Redirect with Handling against url.
     *
     * @param string $url
     *
     * @return Response
     */
    public function toUrl($url)
    {
        $allow_not_routed_url = (isset($this->config['allow_not_routed_url']))
            ? $this->config['allow_not_routed_url']
            : false;
        $exclude_urls = (isset($this->config['options']['exclude_urls']))
            ? $this->config['options']['exclude_urls']
            : [];
        $exclude_hosts = (isset($this->config['options']['exclude_hosts']))
            ? $this->config['options']['exclude_hosts']
            : [];

        $uriTargetHost  = (new Uri($url))->getHost();
        if (true === $allow_not_routed_url ||
            in_array($url, $exclude_urls) ||
            in_array($uriTargetHost, $exclude_hosts)
        ) {
            return parent::toUrl($url);
        }

        $controller = $this->getController();

        $request = $controller->getRequest();
        $current_url = $request->getRequestUri();
        $request->setUri($url);

        if ($current_url === (new Uri($url))->__toString()) {
            $this->getEventManager()->trigger('redirect-same-url');

            return;
        }

        $mvcEvent = $this->getEvent();
        $routeMatch = $mvcEvent->getRouteMatch();
        $currentRouteMatchName = $routeMatch->getMatchedRouteName();
        $router = $mvcEvent->getRouter();

        $uriCurrentHost = (new Uri($router->getRequestUri()))->getHost();
        if (($routeToBeMatched = $router->match($request))
            && (
                $uriTargetHost === null
                ||
                $uriCurrentHost === $uriTargetHost
            )
        ) {
            $controller = $routeToBeMatched->getParam('controller');
            $middleware = $routeToBeMatched->getParam('middleware');
            $routeToBeMatchedRouteName = $routeToBeMatched->getMatchedRouteName();

            if ($routeToBeMatchedRouteName !== $currentRouteMatchName
                && (
                    $this->manager->has($controller)
                    ||
                    $middleware !== false
                )
            ) {
                return parent::toUrl($url);
            }

            $action = $routeToBeMatched->getParam('action');
            $currentAction = $routeMatch->getParam('action');
            if ($action !== $currentAction) {
                return parent::toUrl($url);
            }

            if ($routeToBeMatchedRouteName === $currentRouteMatchName
                && $url !== $current_url
            ) {
                return parent::toUrl($url);
            }
        }

        $default_url = (isset($this->config['default_url']))
            ? $this->config['default_url']
            : '/';

        return parent::toUrl($default_url);
    }
}
