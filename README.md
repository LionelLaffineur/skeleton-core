# skeleton-core

## Description

This library contains the core functionality of `skeleton`. It handles the
bootstrapping and, when used in a web context, it will receive and handle the
HTTP request.

The package will automatically detect "applications", which are separate parts
of your project. Each application can implement its own user interface.
Applications are detected based on the URL requested by the user. Usually based
on the hostname, `skeleton-core` will search for the matching application.

For example, one could create one application for a public website while a
second application can handle the administrative interface. The public
application would for example listen on `www.example.com`, while the
administrative interface would listen for requests to `admin.example.com`.

The request will then be finalized by the matching module in the application. By
default, the module is matched by mapping the URI on the local filesystem path,
but this behaviour can be altered using `routes`.

One special case, is handling of so-called `media` files. When a media file is
requested, detection of which happens based on the requested file's extension,
it will be served from the `media/image`, `media/javascript` or `media/css` in
the matching application directory.

## Installation

Installation via composer:

    composer require tigron/skeleton-core

After installation you can start a skeleton project.

## Getting ready

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

Your webserver should rewrite every request to a single PHP file. This file
will start your skeleton project. It should include at least the following

    \Skeleton\Core\Config::include_path('config');
    \Skeleton\Core\Web\Handler::Run();

Altough a skeleton project can have any desired directory structure, we
encourage to use the following:

    - app
    - config
    - lib
      - model
      - external
        - package
        - asset

If more skeleton packages are installed, this structure can easily be extended
to support additional features (file storage/translations/migrations/...)

## Applications

The application dir will automatically be scanned for Skeleton Applications.
Each subdirectory will be seen as a fully independent \Skeleton\Core\Application

There are various types of applications. Skeleton-core includes the most-used:

	\Skeleton\Core\Application\Web

Other applications are available via skeleton packages (eg
[skeleton-application-api](https://github.com/tigron/skeleton-application-api)
or [skeleton-application-dav](https://github.com/tigron/skeleton-application-dav)
)

A Skeleton Application\Web is a common application that handles any type of
web interface. It has modules/templates/events and can contain its own
media.
For an Application\Web to work properly, it is important to respect the correct
directory structure within the application:

    - app
      - your application's folder
        - config
          - application_config1.php
          - application_config2.php
        - event
        - module
        - template
        - media
          - css
          - javascript
          - image

If you want media files to be served from additional paths, for example
if you have a package manager such as `bower`, `yarn` or even the
`fxp/composer-asset-plugin`, you can specify additional paths which will
be searched in addition to the default `media` one.

To enable media serving from an additional asset path, include the
following configuration directive in your skeleton project:

	'asset_paths': The asset paths where media files can be served from if
	they are not found in your application.


### Application configuration

Applications can have their own configuration. The application's configuration
is done via configuration files placed in the `config` directory of your app.
Similar as the global project configuration, every PHP file will be evaluated
in alphabetical order and should return an array with configuration directives.
If you have environment-specific configurations, they can be included in
`environment.php` which will be evaluated last.

The following optional configurations can be set:

|Configuration|Description|Default value|Example values|
|----|----|----|----|
|hostnames|(required)an array containing the hostnames to listen for. Wildcards can be used via `*`.| []| [ 'www.example.be, '*.example.be' ]|
|base_uri|Specifies the base uri for the application. If a base_uri is specified, it will be included in the reverse url generation|'/'|'/v1'|
|default_language|This is the ISO 639-1 code for the default language to be used, if no more specific one can be found|'en'|'en', 'nl', any language iso2 code provided by skeleton-i18n|
|session_name|The name given to your session|'App'|any string|
|sticky_session_name|The key in your session where sticky session information is stored|'sys_sticky_session'|any string|
|csrf_enabled|Enable CSRF|false|true/false|
|replay_enabled|Prevent replay attack|false|true/false|
|hostnames|Array with hostnames that should be handled by the application|[]|array with any hostname(s)|
|routes|Array with route information|[]| See routes |
|module_default|The default module to search for|'index'||
|module_404|The 404 module on fallback when no module is found|'404'||
|sticky_pager|Enable sticky pager|false|Only available if [skeleton-pager](https://github.com/tigron/skeleton-pager) is installed|
|route_resolver|Closure to provide module resolving based on requested path|Internal module resolver||

### Application namespaces

All PHP classes in your Skeleton application should be whitin your Application
Namespace, which is:

	\App\APP_NAME\

For modules, the specific namespace is

	\App\APP_NAME\Module

For events, the specific namespace is

	\App\APP_NAME\Event


### routes

An array which maps `routes` to `modules`. A route definition cab be used to
generate pretty URL's, or even translated versions. Usage is best described by
an example.

    [
        'web_module_index' => [
            '$language/default/route/to/index',
            '$language/default/route/to/index/$action',
            '$language/default/route/to/index/$action/$id',
            '$language[en]/test/routing/engine',
            '$language[en]/test/routing/engine/$action',
            '$language[en]/test/routing/engine/$action/$id',
            ],
    ],

### Usage

#### Routing to the correct application

Based on the `Host`-header in the request, the correct application will be
started. This is where the `hostnames` array in the application's configuration
file (shown above) will come into play.

If `skeleton-core` could find a matching application based on the `Host`-header
supplied in the request, this is the application that will be started.

If your application has `base_uri` configured, that will be taken into account
as well. For example: the application for a CMS can be distinguished by setting
its `base_uri` to `/admin`.

#### Routing to the correct module

Requests that do not have a file extension and thus do not match a `media` file,
will be routed to a module and a matching method. The module is determined based
on the request URI, excluding all $_GET parameters. The module is a class that
should be derived from `\Skeleton\Core\Application\Web\Module`.

This can be best explained with some examples:

| requested uri    | classname                  | filename             |
| ---------------- | -------------------------- | -------------------- |
| /user/management | \App\APP_NAME\Module\User\Management | /user/management.php |
| /                | \App\APP_NAME\Module\Index           | /index.php           |
| /user            | \App\APP_NAME\Module\User            | /user.php            |
| /user            | \App\APP_NAME\Module\User\Index      | /user/index.php      |

As you can see in the last two examples, the `index` modules are a bit special,
in that they can be used instead of the underlying one if they sit in a
subfolder. The `index` is configurable via configuration directive
`module_default`

### Routing to the correct method

A module can contain multiple methods that can handle the request. Each of
those requests have a method-name starting with 'display'. The method is defined
based on the $_GET['action'] variable.

Some examples:

| requested uri    | classname           | method               |
| -------------    | ---------           | --------             |
| /user            | \App\APP_NAME\Module\User     | display()            |
| /user?action=test| \App\APP_NAME\Module\User     | display_test()       |

### Handling of media files

If the requested url contains an extension which matches a known media type, the
requested file will be served from the `media/` directory of the application.

If the requested media file could not be found, `skeleton-core` will search for
a matching file in the folder specified by configuration directive `asset_dir`
(if any).

### CSRF

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

### Replay

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

