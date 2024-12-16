## Developer documentation

### Overview

The entry point of the plugin is the initialization of the singleton class `\Event_Bridge_For_ActivityPub\Setup` in the main plugin file `event-bridge-for-activitypub.php`.
The constructor of that class calls its `setup_hooks()` function. This function provides hooks that initialize all parts of the _Event Bridge For ActivityPub_ whenever needed.

### File structure

Note that almost all files and folder within the `activitypub` folders are structured the same way as in the WordPress ActivityPub plugin.

### Event Plugin Integrations

This plugin supports multiple event plugins, even at the same time. To add a new one you first need to add some basic information about your event plugin. Just create a new file in `./includes/integrations/my-event-plugin.php`. Implement at least all abstract functions of the `Event_Plugin_Integration` class.

#### Basic Event Plugin Integration

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

#### Registering an Event Plugin Integration

Then you need to tell the Event Bridge for ActivityPub about that class by adding it to the `EVENT_PLUGIN_INTEGRATIONS` constant in the `includes/setup.php` file:

```php
	private const EVENT_PLUGIN_INTEGRATIONS = array(
		...
		\Event_Bridge_For_ActivityPub\Integrations\My_Event_Plugin::class,
	);
```

#### Additional Feature: Event Sources

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

### Transformer

Transformers are the classes that convert an WordPress post type (e.g. one used for events) to _ActivityStreams_.
The Event Bridge for ActivityPub then takes care of applying the transformer, so you can jump right into [implementing it](./implement_an_activitypub_event_transformer.md).

### Event Sources Feature – Transmogrifier

The event sources feature allows to aggregate events from external ActivityPub actors. As the initialization of the Event-Sources feature is quite complex all of that initialization is done in the `init` function of the file `includes/class-event-sources.php` which is called by the `\Event_Bridge_For_ActivityPub\Setup` class (`includes/class-setup.php`).

In this plugin we call a **_Transmogrifier_** the **opposite** of a **_Transformer_**. It takes care of converting an ActivityPub (`Event`) object in _ActivityStreams_ to the WordPress representation of an event plugin. The transmogrifier classes are only used when the `Event Sources` feature is activated. The _Event Bridge For ActivityPub_ can register transformers for multiple event plugins at the same time, however only one transmogrifier, that is one target even plugin can be used as a target for incoming external ActivityPub `Event` objects.
