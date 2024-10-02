# Write a specialized ActivityPub transformer for an Event-Custom-Post-Type

> **_NOTE:_** This documentation is also likely to be useful for content types other than events.

The ActivityPub plugin offers a basic support for all post types out of the box, but it also allows the registration of external transformers. A transformer is a class that implements the [abstract transformer class](https://github.com/Automattic/wordpress-activitypub/blob/fb0e23e8854d149fdedaca7a9ea856f5fd965ec9/includes/transformer/class-base.php) and is responsible for generating the ActivityPub JSON representation of an WordPress post or comment object. 

## How it works

To make the WordPress ActivityPub plugin use a custom transformer simply add a filter to the `activitypub_transformer` hook which provides access to the transformer factory. The [transformer factory](https://github.com/Automattic/wordpress-activitypub/blob/master/includes/transformer/class-factory.php#L12) determines which transformer is used to transform a WordPress object to ActivityPub. We provide a parent event transformer, that comes with common tasks needed for events. Furthermore, we provide admin notices, to prevent users from misconfiguration issues.

## Add your event plugin

First you need to add some basic information about your event plugin. Just create a new file in `./includes/plugins/my-event-plugin.php`. Implement at least all abstract functions of the `Event_Plugin` class.

```php
  namespace ActivityPub_Event_Bridge\Plugins;

  // Exit if accessed directly.
  defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

  /**
   * Integration information for My Event Plugin
   *
   * This class defines necessary meta information is for the integration of My Event Plugin with the ActivityPub plugin.
   *
   * @since 1.0.0
   */
  final class My_Event_Plugin extends Event_Plugin {
```

The ActivityPub Event Bridge then takes care of applying the transformer, so you can jump right into implementing it.

## Writing an event transformer class

Within WordPress most content types are stored as a custom post type in the posts table. The ActivityPub plugin offers a basic support for all post types out of the box. So-called transformers take care of converting WordPress WP_Post objects to ActivityStreams JSON. The ActivityPub plugin offers a generic transformer for all post types. Additionally, custom transformers can be implemented to better fit a custom post type, and they can be easily registered with the ActivityPub plugin.

If you are writing a transformer for your event post type we recommend to start by extending the provided [event transformer](./includes/activitypub/transformer/class-event.php). It is an extension of the default generic post transformer and inherits useful default implementations for generating the [attachments](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-attachment), rendering a proper [content](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-content) in HTML from either blocks or the classic editor, extracting [tags](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-tag) and more. Furthermore, it offers functions which are likely to be shared by multiple event plugins, so you do not need to reimplement those, or you can fork and extend them to your needs.

So create a new file at `./includes/activitypub/transformer/my-event-plugin.php`.

```php
namespace ActivityPub_Event_Bridge\Activitypub\Transformer;

use ActivityPub_Event_Bridge\Activitypub\Transformer\Event as Event_Transformer;

/**
 * ActivityPub Transformer for My Event Plugin' event post type.
 */
class My_Event_Plugin extends Event_Transformer; {
```

The main function which controls the transformation is `to_object`. This one is called by the ActivityPub plugin to get the resulting ActivityStreams represented by a PHP-object (`\Activitypub\Activity\Object\Extended_Object\Event`). The conversion to the actual JSON-LD takes place later, and you don't need to cover that (> `to_array` > associative array > `to_json` > JSON).
The chances are good that you will not need to override that function.


```php
/**
  * Transform the WordPress Object into an ActivityPub Event Object.
  *
  * @return Activitypub\Activity\Extended_Object\Event
  */
public function to_object() {
  $activitypub_object = parent::to_object();
  // ... your additions.
  return $activitypub_object;
}
```

We also recommend extending the constructor of the transformer class and set a specialized API object of the event, if it is available. For instance:

```php
	public function __construct( $wp_object, $wp_taxonomy ) {
		parent::__construct( $wp_object, $wp_taxonomy );
		$this->event_api = new My_Event_Object_API( $wp_object );
	}
```

The ActivityPub object classes contain dynamic getter and setter functions: `set_<property>()` and `get_<property>()`. The function `transform_object_properties()` usually called by `to_object()` tries to set all properties known to the target ActivityPub object where a function called `get_<property>` exists in the current transformer class.

### How to add new properties

Adding new properties is not encouraged to do at the transformer level. It's recommended to create a proper target ActivityPub object first. The target ActivityPub object also controls the JSON-LD context via the constant `JSON_LD_CONTEXT`. [Example](https://github.com/Automattic/wordpress-activitypub/blob/fb0e23e8854d149fdedaca7a9ea856f5fd965ec9/includes/activity/extended-object/class-event.php#L21).


### Properties

> **_NOTE:_** Within PHP all properties are snake_case, they will be transformed to the according CamelCase by the ActivityPub plugin. So if to you set `start_time` by using the ActivityPub objects class function `set_start_time` or implementing a getter function in the transformer class called `get_start_time` the property `startTime` will be set accordingly in the JSON representation of the resulting ActivityPub object.

You can find all available event related properties in the [event class](https://github.com/Automattic/wordpress-activitypub/blob/master/includes/activity/extended-object/class-event.php) along documentation and with links to the specifications.

####  Mandatory fields

In order to ensure your events are compatible with other ActivityPub Event implementations there are several required properties that must be set by your transformer.

* **[`type`](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-type)**: if using the `Activitypub\Activity\Extended_Object\Event` class the type will default to `Event` without doing anything.

* **[`startTime`](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-startTime)**: the events start time

* **[`name`](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-name)**: the title of the event

#### Checklist for properties you SHOULD at least consider writing a getter functions for

* **`endTime`**
* **`location`** – Note: the `address` within can be both a `string` or a `PostalAddress`.
* **`isOnline`**
* **`status`**
* **`get_tag`**
* **`timezone`**
* **`commentsEnabled`**
