=== ActivityPub Event Bridge ===
Contributors: andremenrath
Tags: events, fediverse, activitypub, activitystreams, calendar
Requires at least: 6.5
Tested up to: 6.6
Stable tag: 0.1.0
Requires PHP: 8.1
License: AGPL-3.0-or-later
License URI: https://www.gnu.org/licenses/agpl-3.0.html
Integrating popular event plugins with the ActivityPub plugin.

== Description ==

Make your events more discoverable, expand your reach effortlessly while being independent of other (commercial) platforms, and be a part of the growing decentralized web (the Fediverse).
With the ActivityPub Event Bridge Plugin for WordPress, your events can be automatically followed, aggregated and displayed across decentralized platforms like [Mastodon](https://joinmastodon.org) or [Gancio](https://gancio.org), without any extra work.
Forget the hassle of managing multiple social media accounts just to keep your audience informed.

This plugin is not an event managing plugin but an add-on to popular event plugins. It extends their functionality to fully support the [ActivityPub plugin](https://wordpress.org/plugins/activitypub/).
With the ActivityPub plugin people can follow your website directly and engage with your events just as they would on social media: liking, boosting and even commenting if you enable it.
You retain full ownership of your content. By integrating into your existing setup, it ensures no extra work is needed while enhancing your events' visibility across the web.

= How It Works =

With the ActivityPub Event Bridge WordPress plugin, sharing your events is effortless and automatic!
Once you create an event on your WordPress site, it is seamlessly shared across the decentralized web using the ActivityPub protocol.

![](./.wordpress-org/event-activitypub-publishing.gif)

Your events can be automatically delivered to platforms that fully support events, such as [Mobilizon](https://joinmobilizon.org/), [Gancio](https://gancio.org), [Friendica](https://friendi.ca), [Hubzilla](https://hubzilla.org), and [Pleroma](https://pleroma.social/).
These platforms create public event calendars by pulling in events from various sources, including your website. Any updates you make to your events are synced across these platforms—so you only need to manage your events on your own site, with no extra work required.

![](./.wordpress-org/decentralized-event-calenders.gif)


== Installation ==

This plugin depends on the [ActivityPub plugin](https://wordpress.org/plugins/activitypub/). Additionally, you need to use one of the supported event Plugins.

= Supported Event Plugins =

* [The Events Calendar](https://de.wordpress.org/plugins/the-events-calendar/)
* [VS Event List](https://de.wordpress.org/plugins/very-simple-event-list/)
* [Events Manager](https://de.wordpress.org/plugins/events-manager/)

== Configuration ==

If you’re new to the [ActivityPub plugin](https://wordpress.org/plugins/activitypub/), it’s recommended to spend a few minutes reading through its documentation to familiarize yourself with its setup and functionality.

== Frequently Asked Questions ==

= Do I need to install another event plugin to use the Event Federation Plugin? =

Yes, this plugin works as an add-on and requires both the ActivityPub plugin a supported event plugin such as The Events Calendar, VS Event List, or Events Manager to manage your events.

= What platforms can follow my events? =
Your events can be followed on platforms that support ActivityPub like [Mobilizon](https://joinmobilizon.org/), [Gancio](https://gancio.org), [Friendica](https://friendi.ca), [Hubzilla](https://hubzilla.org), and [Pleroma](https://pleroma.social/). Even other applications like [Mastodon](https://joinmastodon.org), which don’t fully support events yet, will display all important information about the events.

= How much extra work is required to maintain my events across the decentralized Web? =

None! Once the plugin is set up, your events are automatically sent to all connected platforms or account that follow you (your Website). Any updates you make to your events are synced without additional effort.

= Can I still use social media to promote my events? =

Yes, you can still use traditional social media if you wish. However, this plugin helps reduce reliance on commercial platforms by connecting your events to the decentralized Fediverse.

= Will this plugin work if I don't use the ActivityPub plugin? =

No, the Event Federation Plugin depends on the [ActivityPub plugin](https://wordpress.org/plugins/activitypub/) to deliver your events across decentralized platforms, so it's essential to have it installed and configured.

= My event plugin is not supported, what can I do? =

If you know about coding have a look at the documentation of how to add your plugin or open an [issue](https://code.event-federation.eu/Event-Federation/wordpress-activitypub-event-bridge/issues), if we can spare some free hours we might add it.

= What if I experience problems? =

We're always interested in your feedback. Feel free to reach out to us via [E-Mail](https://event-federation.eu/contact/) or create an [issue](https://code.event-federation.eu/Event-Federation/wordpress-activitypub-event-bridge/issues).

== Changelog ==

= [0.1.0] 2024-09-01 =

* Initial alpha release on WordPress.org
