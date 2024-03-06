# Write a specialized ActivityPub transformer for an Event Custom Post Type

> **_NOTE:_** This documentation is also likely to be userfu for other types other than events.

The ActivityPub plugin offers a basic support for all post types out of the box, but it also allows the registration of external transformers. A transformer is a class that implements the [abstract transformer class](https://github.com/Automattic/wordpress-activitypub/blob/fb0e23e8854d149fdedaca7a9ea856f5fd965ec9/includes/transformer/class-base.php) and is responsible for generating the ActivityPub JSON representation of an WordPress post or comment. 

## Hooks

To make the WordPress ActivityPub plugin use our custom transformer we simply add a filter to the `activitypub_transformer` hook which provides access to the transformer factory. The [transformer factory](https://github.com/Automattic/wordpress-activitypub/blob/master/includes/transformer/class-factory.php#L12) determines which transformer is used to transform a WordPress object to ActivityPub.

```php
function register_custom_transformer_for_my_post_type( $transformer, $wp_object, $object_class ) {
    // Return a custom transformer, if the WordPress object is a post and the post type matches.
    if ( 'WP_Post' === $object_class  && $wp_object->post_type === 'my_event_post_type' ) {
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

> **_NOTE:_** issues could arrise if this filter gets applied multiple times, be sure to take the priority argument of the `add_filter` into account.

## Writing an event transformer class

If you are writing a transformer for your custom post type it is recommended to start by extending the upstream generic [post transformer](https://github.com/Automattic/wordpress-activitypub/blob/fb0e23e8854d149fdedaca7a9ea856f5fd965ec9/includes/transformer/class-post.php). It contains useful default implementations for generating the [attachments](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-attachment), rendering a proper [content](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-content) in HTML from either blocks or the classic editor, extracting [tags](https://www.w3.org/TR/activitystreams-vocabulary/#dfn-tag) and more.

```php
/**
 * ActivityPub Transformer for my_event_post_type.
 */
class My_Event_Post_Type_Transformer extends Activitypub\Transformer\Post; {
```

The main function which controls the transformation is `to_object`. This one is called by the ActivityPub plugin to get the resulting ActivityPub representation as an PHP-array. The convertion to json takes place later and you don't need to cover that.

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


### How to add new properties

### How to customize the JSON-LD context



### Mandatory properties

#### type
```php
	/**
	 * Returns the ActivityStreams 2.0 Object-Type for an Event.
	 *
	 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-event
	 * @return string The Event Object-Type.
	 */
	protected function get_type() {
		return 'Event';
	}
```

#### 

