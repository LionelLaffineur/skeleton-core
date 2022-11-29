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

|Configuration|Description|Default value|Example values|
|----|----|----|----|
|application_path|Sets the directory where skeleton-core can find Applications||'app/'|
|asset_paths|An array containing optional paths to search for assets| [] | [ 'lib/external/assets' ] |


### Application detection
	
The package will automatically detect "applications", which are separate parts
of your project. The following application types are available:

  - [skeleton-application-web](https://github.com/tigron/skeleton-application-web):
  A web application.
  - [skeleton-application-api](https://github.com/tigron/skeleton-application-api):
  An Openapi interface
  - [skeleton-application-dav](https://github.com/tigron/skeleton-application-dav):
  A webdav interface

Based on the `Host`-header in the request, the correct application will be
started. This is where the `hostnames` array in the application's configuration
file (shown above) will come into play.

If `skeleton-core` could find a matching application based on the `Host`-header.
It is the responsibility of the application to finish the HTTP request.

Applications are identified in the $application_path and should respect at least
the following directory structure:

    - {application_path}
      - {APP_NAME}
        - config
		- event

The application config directory should contain the application-specific 
configuration files. The following configuration directives should at least be
set:
|Configuration|Description|Default value|Example values|
|----|----|----|----|
|application_type|(optional)Sets the application to the required type|\Skeleton\Application\Web||
|hostnames|(required)an array containing the hostnames to listen for. Wildcards can be used via `*`.| []| [ 'www.example.be, '*.example.be' ]|
|session_name|The name given to your session|'App'|any string|
|sticky_session_name|The key in your session where sticky session information is stored|'sys_sticky_session'|any string|


### HTTP toolkit

Altough skeleton can be used for a console application, it has an HTTP toolkit
available. It can:

  - accept an HTTP request and pass it to the correct application
  - serve media files
  - session management

#### HTTP handler

The HTTP handler will accept an incoming HTTP request and searches for the
correct application based on the HTTP host header.

To run the HTTP handler:

    \Skeleton\Core\Http\Handler::Run();

It will then pass the request over to the application. The application will
be set via:

    \Skeleton\Core\Application::set();

In the lifespan of the request, the application can always be retrieved via:

    \Skeleton\Core\Application::get();

#### Serve media

Media serving is only done for known filetypes. The known filetypes are:
css, map, pdf, txt, woff, woff2, ttf, otf, eot, gif, jpg, jpeg, png, ico, svg,
js, html, htm, mp4, mkv
Any other file extensions will be ignored.
Media serving can be requested via:

    \Skeleton\Core\Http\Media::detect($request_uri);

The asset will be searched for in the following order:

1. The media directory of the current Application
2. The configured asset_paths
3. The media directory of other skeleton packages

### Events

Events can be created to perform a task at specific key points during the
application's execution.

Events are defined in `Event` context classes. These classes are optional, but
when they are used, they should be located in the `event` directory of your
application. The filename should be in the form of `Context_name.php`, for
example `Application.php`.

The class should extend from `Skeleton\Core\Application\Event\{Context}` and 
the classname should be within the namespace `\App\APP_NAME\Event\{Context}`, 
where `APP_NAME` is the name of your application, and `Context` is one of the
available contexts:

- Application
- Error
- Media

Depending on the type of Application you are running, additional events could
be available. Please read the application-type Readme for more information.


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

#### Media context

##### not_found

The `not_found` method is called whenever a media file is requested which could
not be found.

	public function not_found(): void


