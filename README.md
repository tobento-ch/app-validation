# App Validation

Validation support for the app.

## Table of Contents

- [Getting Started](#getting-started)
    - [Requirements](#requirements)
- [Documentation](#documentation)
    - [App](#app)
    - [Validator Boot](#validator-boot)
        - [Validate Data](#validate-data)
        - [Adding Rules](#adding-rules)
        - [Message Translation](#message-translation)
    - [Http Validation](#http-validation)
        - [Http Requirements](#http-requirements)
        - [Using Validation Request](#using-validation-request)
        - [Writing Request Validation](#using-validation-request)
        - [Http Validation Error Handler Boot](#http-validation-error-handler-boot)
    - [Live Validation](#live-validation)
- [Credits](#credits)
___

# Getting Started

Add the latest version of the app validation project running this command.

```
composer require tobento/app-validation
```

## Requirements

- PHP 8.0 or greater

# Documentation

## App

Check out the [**App Skeleton**](https://github.com/tobento-ch/app-skeleton) if you are using the skeleton.

You may also check out the [**App**](https://github.com/tobento-ch/app) to learn more about the app in general.

## Validator Boot

The validator boot does the following:

* installs validator translation files
* validator and rules interfaces implementation

```php
use Tobento\App\AppFactory;

// Create the app
$app = (new AppFactory())->createApp();

// Adding boots
$app->boot(\Tobento\App\Validation\Boot\Validator::class);

// Run the app
$app->run();
```

### Validate Data

You can validate data using the ```ValidatorInterface::class```. You can access the validator in several ways:

Check out the [**Validation Service - Validating**](https://github.com/tobento-ch/service-validation/#validating) section learn more about validating data in general.

**Using the app**

```php
use Tobento\App\AppFactory;
use Tobento\Service\Validation\ValidatorInterface;

// Create the app
$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../'), 'root')
    ->dir(realpath(__DIR__.'/../app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config')
    ->dir($app->dir('root').'public', 'public')
    ->dir($app->dir('root').'vendor', 'vendor');
    
// Adding boots
$app->boot(\Tobento\App\Validation\Boot\Validator::class);
$app->booting();

$validator = $app->get(ValidatorInterface::class);

$validation = $validator->validating(
    value: 'foo',
    rules: 'alpha|minLen:2',
);

// var_dump($validation->isValid());
// bool(true)

// Run the app
$app->run();
```

**Using autowiring**

You can also request the ```ValidatorInterface::class``` in any class resolved by the app.

```php
use Tobento\Service\Validation\ValidatorInterface;

class SomeService
{
    public function __construct(
        protected ValidatorInterface $validator,
    ) {}
}
```

### Adding Rules

The [**Default Rules**](https://github.com/tobento-ch/service-validation/#default-rules) are available by default. You may add more rules by the following way:

```php
use Tobento\App\AppFactory;
use Tobento\Service\Validation\RulesInterface;

// Create the app
$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../'), 'root')
    ->dir(realpath(__DIR__.'/../app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config')
    ->dir($app->dir('root').'public', 'public')
    ->dir($app->dir('root').'vendor', 'vendor');
    
// Adding boots
$app->boot(\Tobento\App\Validation\Boot\Validator::class);

// using the app on method:
$app->on(RulesInterface::class, function(RulesInterface $rules) {
    $rules->add(name: 'same', rule: new Same());
});

// Run the app
$app->run();
```

You may check out the [**Rules**](https://github.com/tobento-ch/service-validation/#rules) section to learn more about adding rules.

### Message Translation

Simply, install the [App Translation](https://github.com/tobento-ch/app-translation) bundle and boot the ```\Tobento\App\Translation\Boot\Translation::class```:

```
composer require tobento/app-translation
```

```php
use Tobento\App\AppFactory;

// Create the app
$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../'), 'root')
    ->dir(realpath(__DIR__.'/../app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config')
    ->dir($app->dir('root').'public', 'public')
    ->dir($app->dir('root').'vendor', 'vendor');
    
// Adding boots
$app->boot(\Tobento\App\Translation\Boot\Translation::class);
$app->boot(\Tobento\App\Validation\Boot\Validator::class);

// Run the app
$app->run();
```

Messages will be translated based on the [Configured Translator Locale](https://github.com/tobento-ch/app-translation#configure-translator).

By default, the [Default Rules](https://github.com/tobento-ch/service-validation/#default-rules) error messages are translated in ```en``` and ```de```.

Check out the [Add Translation](https://github.com/tobento-ch/app-translation#add-translations) section to learn how to add translations.

Make sure you define the resource name ```validator``` for rule error messages as configured on the [Message Translator Modifier](https://github.com/tobento-ch/service-message#translator). The [Message Parameter Translator Modifier](https://github.com/tobento-ch/service-message#parameter-translator) uses the ```*``` as resource name.

## Http Validation

#### Http Requirements

The following app example shows the minimum requirements for the http validation.

First, install the [App Http](https://github.com/tobento-ch/app-http) bundle:

```
composer require tobento/app-http
```

In addition, you may install the [App View](https://github.com/tobento-ch/app-view) bundle for view support:

```
composer require tobento/app-view
```

Next, make sure the following boots are defined:

```php
use Tobento\App\AppFactory;

// Create the app
$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../'), 'root')
    ->dir(realpath(__DIR__.'/../app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config')
    ->dir($app->dir('root').'public', 'public')
    ->dir($app->dir('root').'vendor', 'vendor');
    
// HTTP boots:
// Required for request validation:
$app->boot(\Tobento\App\Http\Boot\Routing::class);
$app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);

// Required for flashing input and messages:
$app->boot(\Tobento\App\Http\Boot\Session::class);

// VIEW boots:
// Optional boots for view support:
$app->boot(\Tobento\App\View\Boot\View::class);
$app->boot(\Tobento\App\View\Boot\Form::class);
$app->boot(\Tobento\App\View\Boot\Messages::class);

// VALIDATION boots:
// Default error handler for handling ValidationException.
// You may create your own handler or add one with higher priority.
$app->boot(\Tobento\App\Validation\Boot\HttpValidationErrorHandler::class);

$app->boot(\Tobento\App\Validation\Boot\Validator::class);

// Run the app
$app->run();
```

### Using Validation Request

You may use the ```Tobento\App\Validation\Http\ValidationRequest::class``` for validating the request input.

By default, a ```Tobento\App\Validation\Exception\ValidationException::class``` will be thrown if validation fails. The exception will be handled by the ```Tobento\App\Validation\Boot\HttpValidationErrorHandler::class``` if booted. See [Http Validation Error Handler Boot](#http-validation-error-handler-boot).

```php
use Tobento\App\Validation\Http\ValidationRequest;
use Tobento\Service\Validation\ValidationInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouterInterface;
use Psr\Http\Message\ResponseInterface;

class ProductController
{
    /**
     * Store a new product.
     */
    public function store(ValidationRequest $request): ResponseInterface
    {
        $validation = $request->validate(
            rules: [
                'title' => 'required|alpha',
            ],

            // You may specify an uri for redirection.
            redirectUri: '/products/create',

            // Or you may specify a route name for redirection.
            redirectRouteName: 'products.create',

            // If no uri or route name is specified,
            // it will be redirected to the previous url.

            // You may specify an error message flashed to the user.
            errorMessage: 'You have some errors check out the fields for its error message',

            // You may change the behaviour by a custom validation error handler though.
        );

        // The product is valid, store product e.g.

        var_dump($validation instanceof ValidationInterface);
        // bool(true)

        // The following interfaces are available:
        var_dump($request->requester() instanceof RequesterInterface);
        // bool(true)

        var_dump($request->responser() instanceof ResponserInterface);
        // bool(true)

        var_dump($request->router() instanceof RouterInterface);
        // bool(true)

        // you may use the responser and router for redirection:
        return $request->responser()->redirect($request->router()->url('products.index'));
    }
}
```

You may check out the following links for its documentation:

* [Validation Interface](https://github.com/tobento-ch/service-validation/#validation)
* [Requester](https://github.com/tobento-ch/service-requester#documentation)
* [Responser](https://github.com/tobento-ch/service-responser#responser)
* [Router](https://github.com/tobento-ch/service-routing)

**Manually handling validation**

```php
use Tobento\App\Validation\Http\ValidationRequest;
use Psr\Http\Message\ResponseInterface;

class ProductController
{
    public function store(ValidationRequest $request): ResponseInterface
    {
        $validation = $request->validate(
            rules: [
                'title' => 'required|alpha',
            ],

            // you may disable throwing ValidationException on failure
            // and handling it by yourself.
            throwExceptionOnFailure: false,
        );

        if (! $validation->isValid()) {
            // handle invalid validation.
        }

        // ...
    }
}
```

### Http Validation Error Handler Boot

The http error handler boot does the following:

* handles ```Tobento\App\Validation\Exception\ValidationException::class``` exceptions.

```php
use Tobento\App\AppFactory;

// Create the app
$app = (new AppFactory())->createApp();

// Adding boots
$app->boot(\Tobento\App\Validation\Boot\HttpValidationErrorHandler::class);

$app->boot(\Tobento\App\Validation\Boot\Validator::class);

// Run the app
$app->run();
```

When the incoming HTTP request is expecting a JSON response, the error handler will return a 422 Unprocessable Entity HTTP response with the following format:

```json
{
    "message": "The exception message",
    "errors": {
        "email": [
            "The email is required."
        ]
    }
}
```

Otherwise, the error handler will return a redirect response by using the defined ```redirectUri``` parameter from the ```Tobento\App\Validation\Exception\ValidationException::class``` or if not defined using the ```Tobento\Service\Uri\PreviousUriInterface::class``` uri:

Furthermore, you may create a custom [Error Handler](https://github.com/tobento-ch/app-http/#handle-other-exceptions) or add an [Error Handler With A Higher Priority](https://github.com/tobento-ch/app-http/#prioritize-error-handler) of ```3000``` as defined on the ```Tobento\App\Validation\Boot\HttpValidationErrorHandler::class```.

## Live Validation

In progress...

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)