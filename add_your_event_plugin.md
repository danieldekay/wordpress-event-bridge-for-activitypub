# Write a specialized ActivityPub transformer for an Event-Custom-Post-Type

> **_NOTE:_** This documentation is also likely to be useful for content types other than events.

The ActivityPub plugin offers a basic support for all post types out of the box, but it also allows the registration of external transformers. A transformer is a class that implements the [abstract transformer class](https://github.com/Automattic/wordpress-activitypub/blob/fb0e23e8854d149fdedaca7a9ea856f5fd965ec9/includes/transformer/class-base.php) and is responsible for generating the ActivityPub JSON representation of an WordPress post or comment object. 

## How it works

To make the WordPress ActivityPub plugin use a custom transformer simply add a filter to the `activitypub_transformer` hook which provides access to the transformer factory. The [transformer factory](https://github.com/Automattic/wordpress-activitypub/blob/master/includes/transformer/class-factory.php#L12) determines which transformer is used to transform a WordPress object to ActivityPub. We provide a parent event transformer, that comes with common tasks needed for events. Furthermore, we provide admin notices, to prevent users from misconfiguration issues.

## Add your event plugin

First you need to add some basic information about your event plugin in the constant `SUPPORTED_EVENT_PLUGIN` in the file `includes/class-setup.php`:

```php
    // Example from the Events Manager plugin.
    'events_manager' => array( // Choose any key you like.
        'plugin_file'       => 'events-manager/events-manager.php',
        'post_type'         => 'event',
        'settings_page'     => 'options-general.php?page=vsel',
        'transformer_class' => 'Events_Manager', // Points to the class in the file `includes/activitypub/transformer/class-events-manager.php`.
    ),
```

The Plugin takes care of applying the transformer, so you can jump right into implementing it.

## Writing an event transformer class

If you are writing a transformer for a custom post type it is recommended to start by extending the provided [event transformer](./includes/activitypub/transformer/class-event.php). It is an extension of the default generic post transformer and inherits useful default implementations for generating the [attachments](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-attachment), rendering a proper [content](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-content) in HTML from either blocks or the classic editor, extracting [tags](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-tag) and more.

```php
namespace Activitypub_Event_Extensions\Activitypub\Transformer;

/**
 * ActivityPub Transformer for my_event_post_type.
 */
class My_Event_Post_Type_Transformer extends Event; {
```

The main function which controls the transformation is `to_object`. This one is called by the ActivityPub plugin to get the resulting ActivityPub representation as an PHP-array. The conversion to JSON-LD takes place later, and you don't need to cover that. You might just want to start by applying the parent function, but the chances are high, you don't even need to extend this functions functionality.

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

The ActivityPub object classes contain dynamic getter and setter functions: `set_<property-name>` and `get_<property-name>`. Of course, the property with `property-name` must exist for these to work. The function `transform_object_properties` tries to set all properties known to the ActivityPub object where a function called `get_<property-name>` exists in the current transformer class.

### How to add new properties

Adding new properties is not encouraged to do at the transformer level. It's recommended to create a proper target ActivityPub object first. The target ActivityPub object also controls the JSON-LD context via the constant `JSON_LD_CONTEXT`. [Example](https://github.com/Automattic/wordpress-activitypub/blob/fb0e23e8854d149fdedaca7a9ea856f5fd965ec9/includes/activity/extended-object/class-event.php#L21).


### Properties

> **_NOTE:_** Within PHP all properties are snake_case, they will be transformed to the according CamelCase by the ActivityPub plugin. So if to you set `start_time` by using the ActivityPub objects class function `set_start_time` or implementing a getter function in the transformer class called `get_start_time` the property `startTime` will be set accordingly in the JSON representation of the resulting ActivityPub object.

You can find all available event related properties in the [event class](https://github.com/Automattic/wordpress-activitypub/blob/master/includes/activity/extended-object/class-event.php) along documentation and with links to the specifications.

#### Mandatory Properties for an Event

In order to ensure your events are compatible with other ActivityPub Event implementations there are several required properties that must be set by your transformer.

* **[`type`](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-type)**: if using the `Activitypub\Activity\Extended_Object\Event` class the type will default to `Event` without doing anything.

* **[`startTime`](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-startTime)**: the events start time

* **[`name`](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-name)**: the title of the event

#### Recommended properties for an Event in order to achieve good interoperability with other ActivityPub platforms

* **[summary](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-summary)**: Other ActivityPub platforms that don't natively support event should use the summary (and the `name`) to display it as a converted object type. For example Mastodon converts an `Event` object to a `Note`. It is recommended to write the summary as text-centered with minimal HTML markup and that it contains the most important event details like place, time, etc.

* **`isOnline`**:
