Make use of:

https://docs.theeventscalendar.com/reference/classes/tribe__events__api/get_event_terms/

for getting all tags/terms for an event!

```
public static function get_event_terms( $event_id, array $args = array() ) {
    $terms = array();
    foreach ( get_post_taxonomies( $event_id ) as $taxonomy ) {
        $tax_terms = wp_get_object_terms( $event_id, $taxonomy, $args );
        $terms[ $taxonomy ] = $tax_terms;
    }
    return $terms;
}
```
