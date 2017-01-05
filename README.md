RedirectHandlerModule
=====================

[![PHP version](https://badge.fury.io/ph/samsonasik%2Fredirect-handler-module.svg)](https://badge.fury.io/ph/samsonasik%2Fredirect-handler-module)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://travis-ci.org/samsonasik/RedirectHandlerModule.svg?branch=master)](https://travis-ci.org/samsonasik/RedirectHandlerModule)
[![Coverage Status](https://coveralls.io/repos/samsonasik/RedirectHandlerModule/badge.svg?branch=master)](https://coveralls.io/r/samsonasik/RedirectHandlerModule)
[![Downloads](https://img.shields.io/packagist/dt/samsonasik/redirect-handler-module.svg?style=flat-square)](https://packagist.org/packages/samsonasik/redirect-handler-module)

*RedirectHandlerModule* is a module for handling redirect when the given url to redirect plugin is not registered in your zf2/zf3 application. It simply override existing ZF2/ZF3 redirect plugin, so we can just use it.

For example, we use `redirect()` plugin in your controller:

```php
$redirect = '/foo'; // may be a variable from GET
return $this->redirect()->toUrl($redirect);
```

if the passed `$redirect` as url is a valid and registered in the routes, it uses default `redirect()` implementation, otherwise, it will redirect to default `default_url` registered in `config/autoload/redirect-handler-module.local.php`:

For example, we define:

```php
return [
    'redirect_handler_module' => [
        'allow_not_routed_url' => false,
        'default_url' => '/',
        'options' => [
            'exclude_urls' => [
                // 'https://www.github.com/samsonasik/RedirectHandlerModule',
            ], // to allow excluded urls to always be redirected
            'exclude_hosts' => [
                // 'www.github.com'
            ],
            'exclude_domains' => [
                // 'google.com',
            ],
        ],
    ],
];
```

It means, we can't allow to make redirect to outside registered routes, whenever found un-registered url in routes, then we will be redirected to default_url. It also disable redirect to self, so you can't redirect to self.

For specific urls that exceptional ( allowed to be redirected even not registered in routes), you can register at `exclude_urls`/`exclude_hosts`/`exclude_domains` options.

> if you define exclude_urls/exclude_hosts/exclude_domains options, which one of them is your own current url/host/domain, its your risk to still get "infinite" redirection loops. so, make sure exclude_urls/exclude_hosts/exclude_domains is not your current own.

While default implementation of redirect to self will silently, you can trigger your listener to handle redirect to self in your `Module::onBootstrap($e)`:

```php
class Module
{
    public function onBootstrap($e)
    {
        $app           = $e->getApplication();
        $eventManager  = $app->getEventManager();
        $sharedManager = $eventManager->getSharedManager();

        $sharedManager->attach('RedirectHandlerModule\Controller\Plugin\Redirect', 'redirect-same-url', function() {
            die('You need to use different URL for Redirect');
        });

        $plugin = $app->getServiceManager()->get('ControllerPluginManager')->get('redirect');
        $plugin->setEventManager($eventManager);
    }
}
```

Installation
------------

Require via composer
```bash
composer require samsonasik/redirect-handler-module
```

After composer require done, you can copy `vendor/samsonasik/redirect-handler-module/config/redirect-handler-module.local.php.dist` to `config/autoload/redirect-handler-module.local.php` and modify on your needs.

Last, register to `config/application.config.php`:

```php
return [
    'modules' => [
        // ...
        'RedirectHandlerModule',
    ],
];
```

Contributing
------------
Contributions are very welcome. Please read [CONTRIBUTING.md](https://github.com/samsonasik/RedirectHandlerModule/blob/master/CONTRIBUTING.md)

Credit
------

- [Abdul Malik Ikhsan](https://github.com/samsonasik)
- [All RedirectHandlerModule contributors](https://github.com/samsonasik/RedirectHandlerModule/contributors)
