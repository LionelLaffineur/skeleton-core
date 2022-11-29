# skeleton-core

## Description

This library contains the core functionality of the `skeleton` framework.
It performs these main tasks:

  - Autoloading
  - Config management
  - Application detection
  - HTTP toolkit
  
## Installation

Installation via composer:

    composer require tigron/skeleton-core

After installation you can start a skeleton project.

## Features

### Autoloading

Skeleton doesn't enforce you to use a specific file structure. This means that
skeleton can adapt itself to your structure. In order to do so, you need to 
configure the skeleton autoloader.

Autoloading can be configured like this:

		/**
		 * Register the autoloader
		 */
		$autoloader = new \Skeleton\Core\Autoloader();
		$autoloader->add_include_path($root_path . '/lib/model/');
		$autoloader->add_include_path($root_path . '/lib/base/');
		$autoloader->add_include_path($root_path . '/lib/component/');
		$autoloader->add_include_path($root_path . '/tests');
		$autoloader->register();

Skeleton autoloader will include the given include paths in its search for the
requested class. An optional parameter 'class_prefix' can be given. This will
prepend all classes for a given path with the given prefix:

### Config management

Skeleton core offers a Config object that is populated from a given config
directory. The Config object automatically includes all php files which are
stored in the config directory. Each php file should return a php array.
Each key/value pair will be available in your project.

Include a config directory

    \Skeleton\Core\Config::include_path('/config');

PHP files stored in the config directory will be evaluated in alphabetical
order. In case you have environment-specific configuration, you can create a
file `environment.php` in your config directory which will be evaluated last.

Get a config object

	$config = \Skeleton\Core\Config::get();

Skeleton needs at least these config items to operate properly:

	'application_path': The root path where skeleton can find Skeleton
	Applications


### Application detection
	
The package will automatically detect "applications", which are separate parts
of your project. The following application types are available:

  - [skeleton-application-web](https://github.com/tigron/skeleton-application-web):
  A web application.
  - [skeleton-application-api](https://github.com/tigron/skeleton-application-api):
  An Openapi interface
  - [skeleton-application-dav](https://github.com/tigron/skeleton-application-dav):
  A webdav interface

Each application will listen on one or more hostnames. Skeleton-core will find
the requested application and runs it. It is the responsibility of the
application to finish the HTTP request.
Applications are identified in the $application_path and should respect at least
the following directory structure:

    - $application_path 
      - APP_NAME
        - config
		- event

The application config directory should contain the application-specific 
configuration files. The following configuration directives should at least be
set:
|Configuration|Description|Default value|Example values|
|----|----|----|----|
|application_type|(optional)Sets the application to the required type|\Skeleton\Application\Web||
|hostnames|(required)an array containing the hostnames to listen for. Wildcards can be used via `*`.| []| [ 'www.example.be, '*.example.be' ]|
|base_uri|Specifies the base uri for the application. If a base_uri is specified, it will be included in the reverse url generation|'/'|'/v1'|
|session_name|The name given to your session|'App'|any string|
|sticky_session_name|The key in your session where sticky session information is stored|'sys_sticky_session'|any string|


### HTTP toolkit

Altough skeleton can be used for a console application, it has an HTTP toolkit
available. It can:

  - accept an HTTP request and pass it to the correct application
  - serve media files
  - session management
  - handle CSRF and Replay attack security

#### CSRF

The `skeleton-core` package can take care of automatically injecting and
validating CSRF tokens for every `POST` request it receives. Various events have
been defined, with which you can control the CSRF flow. A list of these events
can be found further down.

CSRF is disabled globally by default. If you would like to enable it, simply
flip the `csrf_enabled` flag to true, via configuration directive `csrf_enabled`

Once enabled, it is enabled for all your applications. If you want to disable it
for specific applications only, flip the `csrf_enabled` flag to `false` in the
application's configuration.

Several events are available to control the CSRF behaviour, these have been
documented below.

When enabled, hidden form elements with the correct token as a value will
automatically be injected into every `<form>...</form>` block found. This allows
for it to work without needing to change your code.

If you need access to the token value and names, you can access them from the
`env` variable which is automatically assigned to your template. The available
variables are listed below:

- env.csrf_header_token_name
- env.csrf_post_token_name
- env.csrf_session_token_name
- env.csrf_token

One caveat are `XMLHttpRequest` calls (or `AJAX`). If your application is using
`jQuery`, you can use the example below to automatically inject a header for
every relevant `XMLHttpRequest`.

First, make the token value and names available to your view. A good place to do
so, might be the document's `<head>...</head>` block.

    <!-- CSRF token values -->
    <meta name="csrf-header-token-name" content="{{ env.csrf_header_token_name }}">
    <meta name="csrf-token" content="{{ env.csrf_token }}">

Next, we can make use of `jQuery`'s `$.ajaxSend()`. This allows you to
configure settings which will be applied for every subsequent `$.ajax()` call
(or derivatives thereof, such as `$.post()`).

    $(document).ajaxSend(function(e, xhr, settings) {
        if (!(/^(GET|HEAD|OPTIONS|TRACE)$/.test(settings.type)) && !this.crossDomain) {
		    xhr.setRequestHeader($('meta[name="csrf-header-token-name"]').attr('content'), $('meta[name="csrf-token"]').attr('content'));
		}
    });

Notice the check for the request type and cross domain requests. This avoids
sending your token along with requests which don't need it.

#### Replay

The built-in replay detection tries to work around duplicate form submissions by
users double-clicking the submit button. Often, this is not caught in the UI.

Replay detection is disabled by default, if you would like to enable it, flip
the `replay_enabled` configuration directive to true.

You can disable replay detection for individual applications by setting the
`replay_enabled` flag to `false` in their respective configuration.

When the replay detection is enabled, it will inject a hidden `__replay-token`
element into every `form` element it can find. Each token will be unique. Once
submited, the token is added to a list of tokens seen before. If the same token
appears again within 30 seconds, the replay detection will be triggered.

If your application has defined a `replay_detected` event, this will be called.
It is up to the application to decide what action to take. One suggestion is to
redirect the user to the value HTTP referrer, if present.

### Events

Events can be created to perform a task at specific key points during the
application's execution.

Events are defined in `Event` context classes. These classes are optional, but
when they are used, they should be located in the `event` directory of your
application. The filename should be in the form of `Context_name.php`, for
example `Application.php`.

The class should extend from `Skeleton\Core\Application\Event\{Context}` and the classname should be
within the namespace `\App\APP_NAME\Event\{Context}`, where
`APP_NAME` is the name of your application, and `Context` is one of the
available contexts:

- Application
- Error
- I18n
- Media
- Module
- Security

Example of a `Module` event class for an application named `admin`:

    <?php
    /**
     * Module events for the "admin" application
     */

    namespace App\Admin\Event;

    class Module extends \Skeleton\Core\Application\Event\Module {

        /**
         * Access denied
         *
         * @access public
         */
        public function access_denied() {
            \Skeleton\Core\Web\Session::redirect('/reset');
        }

    }

The different contexts and their events are described below.

#### Application context

##### bootstrap

The bootstrap method is called before loading the application module.

	public function bootstrap(\Skeleton\Core\Application\Web\Module $module): void


##### teardown

The teardown method is called after the application's run is over.

	public function teardown(\Skeleton\Core\Application\Web\Module $module): void

##### detect

The detect method is called on every request to determine if the application
should handle the request, or if it should be skipped based on, for example, the
requested hostname and the request's URI.

This event should return `true` in order to proceed with this application.

	public function detect($hostname, $request_uri): bool

#### Error context

This context is only available if skeleton-error is installed.

##### exception

The exception method is called on every exeption/error. The method should 
return a boolean, indicating if skeleton-error should proceed to other 
error handlers

	public function exception(\Throwable $exception): bool

##### sentry_before_send

The sentry_before_send method can be used to enrich the data that will be sent
to Sentry with application-specific data (ex the user that logged in)

	public function sentry_before_send(\Sentry\Event $event)

#### I18n context

##### get_translator_extractor

Get a Translator\Extractor for this application. If not provided, a 
Translator\Extractor\Twig is created for the template-directory of the 
application.

	public function get_translator_extractor(): \Skeleton\I18n\Translator\Extractor

Get a Translator\Storage for this application. If not provided, a 
Translator\Storage\Po is created, but only if a default storage path is 
configured.

	public function get_translator_storage(): \Skeleton\I18n\Translator\Storage

Get a Translator object for this application. If no translation is needed, 
return null. By default, a translator is created with the storage and 
extractor of the above methods.

	public function get_translator(): ?\Skeleton\I18n\Translator
	
Detect the language for the application. By default, a language is negotiated
between $_SERVER['HTTP_ACCEPT_LANGUAGE'] and all available languages.
If a language is returned, it will be stored in the session so this will only
be triggered the first request.	
	
	public function detect_language(): \Skeleton\I18n\LanguageInterface	

#### Media context

##### not_found

The `not_found` method is called whenever a media file is requested which could
not be found.

	public function not_found(): void

#### Module context

##### access_denied

The `access_denied` method is called whenever a module is requested which can
not be accessed by the user. The optional `secure()` method in the module
indicates whether the user is granted access or not.

	public function access_denied(\Skeleton\Core\Web\Module $module): void

##### not_found

The `not_found` method is called whenever a module is requested which does not
exist.

	public function not_found(): void

#### Security context

##### csrf_validate_enabled

The `csrf_validate_enabled` method overrides the complete execution of the
validation, which useful to exclude specific paths. An example implementation
can be found below.

    public function csrf_validate_enabled(): bool {
        $excluded_paths = [
            '/no/csrf/*',
        ];

        foreach ($excluded_paths as $excluded_path) {
            if (fnmatch ($excluded_path, $_SERVER['REQUEST_URI']) === true) {
                return false;
            }
        }

        return true;
    }

##### csrf_validate_success

The `csrf_validate_success` method allows you to override the check result after
a successful validation. It expects a boolean as a return value.

	public function csrf_validate_success(): bool


##### csrf_validation_failed

The `csrf_validation_failed` method allows you to override the check result
after a failed validation. It expects a boolean as a return value.

	public function csrf_validation_failed(): bool {


##### csrf_generate_session_token

The `csrf_generate_session_token` method allows you to override the generation
of the session token, and generate a custom value instead. It expects a string
as a return value.

	public function csrf_generate_session_token(): string


##### csrf_inject

The `csrf_inject` method allows you to override the automatic injection of the
hidden CSRF token elements in the HTML forms of the rendered template. It
expects a string as a return value, containing the rendered HTML to be sent back
to the client.

	public function csrf_inject($html, $post_token_name, $post_token): string


##### csrf_validate

The `csrf_validate` method allows you to override the validation process of the
CSRF token. It expects a boolean as a return value.

	public function csrf_validate($submitted_token, $session_token): bool


##### replay_detected

The `replay_detected` method allows you to catch replay detection events. For
example, you could redirect the user to the value of the HTTP referrer header
if it is present:

    public function replay_detected() {
        if (!empty($_SERVER['HTTP_REFERER'])) {
            Session::redirect($_SERVER['HTTP_REFERER'], false);
        } else {
            Session::redirect('/');
        }
    }

##### replay_inject

The `replay_inject` method allows you to override the automatic injection of the
hidden replay token elements in the HTML forms of the rendered template. It
expects a string as a return value, containing the rendered HTML to be sent back
to the client.

	public function csrf_inject($html, $post_token_name, $post_token): string

##### session_cookie

The `session_cookie` method allows you to set session cookie parameters before
the session is started. Typically, this would be used to SameSite cookie
attribute.

	public function session_cookie(): void

