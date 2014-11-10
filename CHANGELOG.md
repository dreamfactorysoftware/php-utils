# DreamFactory Services Platform&trade; Change Log

### More information can now be found on the DSP project wiki [CHANGELOG](https://github.com/dreamfactorysoftware/dsp-core/wiki/CHANGELOG)

## v1.6.1 (Release 2014-06-24)
###Fixes
* Pull Common library DataFormat class to this library as DataFormatter and fix csv file ending detection. Update references.

## v1.6.0 (Release 2014-06-20)
###New!
* Server Side Lookups changed so private can only be used by service configuration, and non-private for other things like filtering
* Server Side Lookups can now be used for database filter parameter replacement.
* Server Side Lookups can now be used for database record field creation and modification.
* Role's Service and System Accesses now use REST verb selection instead of access string.
* Server Side Scripting gets major overhaul and adds detailed request and response handling and call outs to REST API and external HTTP calls.
* Major Swagger update for db services to expose the various *ByFilter and *ByIds options.

###Fixes, Updates, and Upgrades
* Changed Swagger output to be locked down by valid application api_name only, no session required because of sdk usage.
* App import bug fix, also removing default description from swagger service
* Fix for AWS container creation
* Schema service changes to support PUT properly, deletes any fields not in posted data.
* Additional lookup usage in NoSQL DB cases
* Improvements for CouchDb view support
* Improvements for Postgresql support
* Cleanup use of MERGE vs. PATCH, allowing both.
* Changes to database services to remedy the xml translation and lack of record wrapper problem.
* Expose auto-login option to swagger for password reset and registration
* Remove pass by reference use


## v1.5.12 (Release 2014-05-09)
### Fixes
* System Config caching issue
* MongoDB support for MongoDB style filters in url filter parameter, and fix IN support
* Importing data records from app package
* User display name on registration
* Parameter include_count usage on system object queries has been corrected to be total count

## v1.5.11 (Release 2014-04-30)
### New!
* New DSP-level persistent storage mechanism interfaces with redis, xcache, memcache(d), etc.
* Added support for [libv8](https://github.com/v8) for server-side Javascript support
* Server-side event scripting with Javascript (Scripts live in /path/to/dsp/.private/scripts)
 * Server-side events are now live and being generated
  * Client event handler registration via new /rest/system/event API. See Live API for more info.
 * Server-side scripts now supported for REST events
  * Client event script registration via new /rest/system/script API. See Live API for more info.
* Lookup Key System enabling per-user permissions among other things.
* Local configuration file support (/path/to/dsp/.private/config)
* New configuration options for events and event logging control

### Fixes, Updates, and Upgrades
* Upgraded dependencies abound
* Session bug fix for validation with ticket
* Restored PEAR repository to composer.json because it is again required :(
* Myriad Javascript SDK and Admin application changes and fixes
* Returned data from GET ```/rest/system/config``` now includes more information about the environment
* **Azure** bug fixes and updates
* **DynamoDB** bug fixes
* **MongoDB** Full support added for rollback and continue options, batch error handling, and server-side filtering

#### Core Changes
* Standardized code formatting style based on a slightly modified PSR-1/2. One notable change is that we have dropped tabs for spaces.
* Leverage the [Symfony HttpFoundation](http://symfony.org) components in processing inbound requests in a drive towards framework neutrality.

#### Swagger Changes
* Event, Provider, ProviderUser, and Script resources added to Live API

#### Miscellaneous
* More code cleanup

## v1.4.x (Last Updated 2014-03-03)
### Major Foundational Changes
* Project Tree Reorganization
	* The [dsp-core](https://github.com/dreamfactorysoftware/dsp-core/) ```config/schema``` tree has been moved to this library.
	* The [Swagger](https://github.com/zircode/swagger-php/) storage area has been moved to a, now, user-editable location. Previously, it was hidden from hosted DSPs.
	* More functionality moved from launchpad/admin to back-end including login and password management
* Performance Improvements
	* Moved required PHPUnit dependencies ```"require-dev"``` section of ```composer.json``` so they can be more easily excluded in production.
	* Multiple performance improvements from consolidation/caching/removal of repetitive processes
	* Hosted DSPs will no longer check github for versioning

### New Features
* **New Data Formatter**: ```Components\AciTreeFormatter``` for data used in an [AciTree](http://plugins.jquery.com/aciTree/).
* **Guzzle Migration**: Begin migration from our [Curl](https://github.com/lucifurious/kisma/blob/master/src/Kisma/Core/Utility/Curl.php) class to [Guzzle](https://github.com/guzzle/guzzle/) .
* **Foundational Changes**: Laid groundwork for server-side events
* **Device Management**: New schema, models, and API added. Affects system and user services as well.
* **New Constants**:
	* [**LocalStorageTypes::SWAGGER_PATH**](https://bitbucket.org/dreamfactory/lib-php-common-platform/src/4ca33f4915ef2cacc340c1d74bf7ffc93e72fab9/Interfaces/PlatformStates.php?at=master) and [**LocalStorageTypes::TEMPLATE_PATH**](https://bitbucket.org/dreamfactory/lib-php-common-platform/src/4ca33f4915ef2cacc340c1d74bf7ffc93e72fab9/Interfaces/PlatformStates.php?at=master) and associated getters in [Platform](https://bitbucket.org/dreamfactory/lib-php-common-platform/src/4ca33f4915ef2cacc340c1d74bf7ffc93e72fab9/Utility/Platform.php?at=master)
	* [**PlatformStates::WELCOME_REQUIRED**](https://bitbucket.org/dreamfactory/lib-php-common-platform/src/4ca33f4915ef2cacc340c1d74bf7ffc93e72fab9/Interfaces/PlatformStates.php?at=master) platform state and modifying [SystemManager::initAdmin](https://bitbucket.org/dreamfactory/lib-php-common-platform/src/4ca33f4915ef2cacc340c1d74bf7ffc93e72fab9/Services/SystemManager.php?at=master) to be cleaner. NOTE: Requires new DSP version

### Bug Fixes
* Fix api login session bug
* Fix content type determination on file management
* Model corrections for swagger
* Better check for multi-row configs
* Fix some include problems, add search both directions to array_diff in Utility
* Override to setNativeFormat() to avoid invalid argument exception
* Fixes #57 by returning all columns from the model when constructing the response

## v1.3.3 (Released 2014-01-03)
* Installer and composer bug fixes

## v1.3.2 (Released 2013-12-31)
* Continued refactoring of Launchpad application into PHP core
* Login page now handled by PHP core
* Admin welcome screen and support registration added
* Portal service updated to allow for multiple portals to the same provider
* Bug fixes

## v1.2.3 (Released 2013-12-11)

### Major Foundational Changes
* Restructure of the project tree
	* The web/document root has been moved from `/web/public` to `/web`
	* `/web` now contains only publicly accessible code
	* All back-end server code has been moved from `/web/protected` to `/app`

* Management apps **app-launchpad** and **app-admin** have been merged into the core
	* Duplicate code removed
	* Libraries updated
	* Removed directory `/shared` and all associated links

* Back-end-served pages have been upgraded to use Bootstrap v3.x

### New Features
* User import/export available in the admin panel
* Support for CSV MIME type
* Import/export from/to file in JSON or XML
* Admin configuration support for default email template.
* Added remote database caching feature
* Application auto-start. Automatically loads an app when a session is active and the user has access
* System-wide maintenance notification added

### Bug Fixes
* Fix for SQL Server spatial/geography types. Return as strings and corrections for MS SQL connections
* Server will now send invite on user creation with **send_invite** url parameter, also supported on batch import.

### Miscellaneous
* `/app/controllers/RestController.php` refactored/simplified

## v1.1.3 (Released 2013-10-31)
* Portal and remote authentication support
* Bug fixes
* More to come

## v1.1.2
* Bug fixes

## v1.1.0

### Major Bug Fixes
* Add description to AppGroup and Role models
* Current user can no longer change their own role(s)
* Permission issues when saving new data
* All remaining open issues from version 1.0.6 fixed

### Major New Features
* Removed most DSP-specific code from Yii routing engine
* Most, if not all, resource access moved to a single class "ResourceStore"
* "/rest/system/constant" call to return system constants for list building
* Swagger annotations removed from code an placed into their own files
* Take "Accept" header into account for determination of content
* Config call now returns any available remote authentication providers
* Added new service to retrieve and update data from Salesforce
* New authentication provider service using the Oasys library
* Remote login services added to core and app-launchpad
* Added support for "global" authentication providers for enterprise customers
* Added ability to control CRUD access to system resources (User, App, etc.) through the Roles Admin Settings.

### Major Foundational Changes
* Most system types (i.e. service, storage, etc.) are now numeric instead of hard-coded strings
* Services now configured from /config/services.config.php instead of being hard-coded
* /src tree removed, replaced by new Composer libraries (lib-php-common-platform)
* Prep work for new "portal" service (future release)
* Prep work for moving to Bootstrap v3.x (future release)
* Prep work for new administration dashboard (future release)
