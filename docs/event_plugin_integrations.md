
## Event Plugin Integrations

First you need to add some basic information about your event plugin. Just create a new file in `./includes/integrations/my-event-plugin.php`. Implement at least all abstract functions of the `Event_Plugin_Integration` class.

### Basic Event Plugin Integration

```php
  namespace Event_Bridge_For_ActivityPub\Integrations;

  // Exit if accessed directly.
  defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

  /**
   * Integration information for My Event Plugin
   *
   * This class defines necessary meta information is for the integration of My Event Plugin with the ActivityPub plugin.
   */
  final class My_Event_Plugin extends Event_Plugin_Integration {
```

### Registering an Event Plugin Integration

Then you need to tell the Event Bridge for ActivityPub about that class by adding it to the `EVENT_PLUGIN_INTEGRATIONS` constant in the `includes/setup.php` file:

```php
	private const EVENT_PLUGIN_INTEGRATIONS = array(
		...
		\Event_Bridge_For_ActivityPub\Integrations\My_Event_Plugin::class,
	);
```

### Additional Feature: Event Sources

Not all _Event Plugin Integrations_ support the event-sources feature. To add support for it an integration must implement the `Feature_Event_Sources` interface.

```php
namespace Event_Bridge_For_ActivityPub\Integrations;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

  /**
   * Integration information for My Event Plugin.
   *
   * This class defines necessary meta information is for the integration of My Event Plugin with the ActivityPub plugin.
   * This integration supports the Event Sources Feature.
   */
final class GatherPress extends Event_Plugin_Integration implements Feature_Event_Sources {
```
