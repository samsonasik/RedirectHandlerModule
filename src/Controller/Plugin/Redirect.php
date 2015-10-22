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

use Zend\Mvc\Controller\Plugin\Redirect as BaseRedirect;

class Redirect extends BaseRedirect
{
    /**
     * Redirect with Handling against url
     *
     * @param  string $url
     * @return Response
     */
    public function toUrl($url)
    {
        $controller     = $this->getController();
        $serviceLocator = $controller->getServiceLocator();

        $config = $serviceLocator->get('config');
        $allow_not_routed_url = (isset($config['allow_not_routed_url']) ? $config['allow_not_routed_url'] : false;
        $default_route = (isset($config['default_route']) ? $config['default_route'] : 'home';
        $default_url = (isset($config['default_url']) ? $config['default_url'] : '/';

        $request        = $controller->getRequest();
        $request->setUri($url);

        $currentRouteMatchName = $controller->getEvent()
                                            ->getRouteMatch()
                                            ->getMatchedRouteName();

        if ($routeToBeMatched = $serviceLocator->get('Router')
                                               ->match($request)
        ) {
            if ($routeToBeMatched->getMatchedRouteName() != $currentRouteMatchName) {
                return parent::toUrl($url);
            }
        }

        if ($currentRouteMatchName !== $default_route) {
            return parent::toUrl($default_url);
        }
    }
}
