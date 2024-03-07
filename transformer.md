# Write a specialized ActivityPub transformer for an Event-Custom-Post-Type

> **_NOTE:_** This documentation is also likely to be userful for content types other than events.

The ActivityPub plugin offers a basic support for all post types out of the box, but it also allows the registration of external transformers. A transformer is a class that implements the [abstract transformer class](https://github.com/Automattic/wordpress-activitypub/blob/fb0e23e8854d149fdedaca7a9ea856f5fd965ec9/includes/transformer/class-base.php) and is responsible for generating the ActivityPub JSON representation of an WordPress post or comment object. 

## Hooks

To make the WordPress ActivityPub plugin use a custom transformer simply add a filter to the `activitypub_transformer` hook which provides access to the transformer factory. The [transformer factory](https://github.com/Automattic/wordpress-activitypub/blob/master/includes/transformer/class-factory.php#L12) determines which transformer is used to transform a WordPress object to ActivityPub.

```php
function register_custom_transformer_for_my_post_type( $transformer, $wp_object, $object_class ) {
    // Return a custom transformer, if the WordPress object is a post and the post type matches.
    if ( 'WP_Post' === $object_class  && 'my_event_post_type'  === $wp_object->post_type ) {
        require_once __DIR__ . '/includes/activitypub/transformer/class-my-custom-transformer.php';
        return new My_Event_Post_Type_Transformer( $wp_object );
    }
    return $transformer; // Return the default transformer.
}

add_filter(
    'activitypub_transformer',
    'register_custom_transformer_for_my_post_type',
    10,
    3
)
```

> **_NOTE:_** issues could arrise if this filter gets applied multiple times, be sure to take the [priority argument](https://developer.wordpress.org/reference/functions/add_filter/#parameters) of the `add_filter` into account.

## Writing an event transformer class

If you are writing a transformer for a custom post type it is recommended to start by extending the upstream generic [post transformer](https://github.com/Automattic/wordpress-activitypub/blob/fb0e23e8854d149fdedaca7a9ea856f5fd965ec9/includes/transformer/class-post.php). It contains useful default implementations for generating the [attachments](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-attachment), rendering a proper [content](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-content) in HTML from either blocks or the classic editor, extracting [tags](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-tag) and more.

```php
/**
 * ActivityPub Transformer for my_event_post_type.
 */
class My_Event_Post_Type_Transformer extends Activitypub\Transformer\Post; {
```

The main function which controls the transformation is `to_object`. This one is called by the ActivityPub plugin to get the resulting ActivityPub representation as an PHP-array. The convertion to JSON-LD takes place later and you don't need to cover that.

```php
/**
  * Transform the WordPress Object into an ActivityPub Event Object.
  *
  * @return Activitypub\Activity\Extended_Object\Event
  */
public function to_object() {
  $activitypub_object = new Activitypub\Activity\Extended_Object\Event();
  $activitypub_object = $this->transform_object_properties( $activitypub_object );

  return $activitypub_object;
}
```

The ActivityPub object classes contain dynamic getter and setter functions: `set_<property-name>` and `get_<property-name>`. Of course, the property with `property-name` must exist for these to work. The function `transform_object_properties` tries to set all properties known to the ActivityPub object where a function called `get_<property-name>` exists in the current transformer class.

### How to add new properties

Adding new properties is not encouraged to do at the transformer level. It's recommended to create a proper target ActivityPub object first. The target ActivityPub object also controls the the JSON-LD context via the constant `JSON_LD_CONTEXT`. [Example](https://github.com/Automattic/wordpress-activitypub/blob/fb0e23e8854d149fdedaca7a9ea856f5fd965ec9/includes/activity/extended-object/class-event.php#L21).


### Properties

> **_NOTE:_** Within PHP all properties are snake_case, they will be transformed to the according CamelCase by the ActivityPub plugin. So if to you set `start_time` by using the ActivityPub objects class nfunction `set_start_time` or implementing a getter function in the transformer class called `get_start_time` the property `startTime` will be set accordingly in the JSON representation of the resulting ActivityPub object.

You can find all available event related properties in the [event class](https://github.com/Automattic/wordpress-activitypub/blob/master/includes/activity/extended-object/class-event.php) along documentation and with links to the specifications.

#### Mandatory Properties for an Event

In order to ensure your events are comatible with other ActivityPub Event implementations there are several required properties that must be set by your transformer.

* **[type](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-type)**: if using the `Activitypub\Activity\Extended_Object\Event` class the type will default to `Event` without doing anything.

* **[startTime](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-startTime)**: the events start time

* **[name](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-name)**: the title of the event

#### Recommended properties for an Event in order to achieve good interoperability with other ActivityPub platforms

* **[summary](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-summary)**: Other ActivityPub platforms that don't natively support event should use the summary (and the `name`) to display it as an converted object type. For example Mastodon converts an `Event` object to a `Note`. It is recommended to write the summary as text-centered with minimal HTML markup and that it contains the most important event details like place, time, etc.

* **isOnline**:

...



#### 

