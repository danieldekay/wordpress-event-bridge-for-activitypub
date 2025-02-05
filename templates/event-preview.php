<?php
/**
 * ActivityPub Post JSON template.
 *
 * @package Activitypub
 */

use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event\Event;

use function Activitypub\object_to_uri;


$post        = \get_post(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$transformer = \Activitypub\Transformer\Factory::get_transformer( $post );

if ( \is_wp_error( $transformer ) ) {
	\wp_die(
		esc_html( $transformer->get_error_message() ),
		404
	);
}

if ( $transformer instanceof Event ) {
	$object   = $transformer->to_object();
	$user     = $transformer->get_actor_object();
	$address  = $transformer->get_formatted_address();
	$location = $transformer->get_location();
	if ( $location ) {
		$location_name = $location->get_name();
	} else {
		$location_name = '';
	}
} else {
	\wp_die(
		'Wrong ActivityPub preview template.',
		404
	);
}

$first_image_attachment = null;

?>
<DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title><?php echo \esc_html( $object->get_name() ); ?>HUU</title>
		<style>
			html,body { min-height:100%; }
			body {
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
				font-size: 1em;
				line-height: 1.5;
				margin: 0;
				padding: 0;
				height: 100%;
			}
			.columns {
				display: flex;
				flex-direction: row;
				justify-content: space-between;
				margin: 0 auto;
				max-width: 1200px;
			}
			.sidebar {
				flex: 1;
				padding: 1em;
				max-width: 285px;
			}
			.sidebar input[type="search"],
			.sidebar textarea {
				background-color: #f6f6f6;
				border: 1px solid #ccc;
				border-radius: 4px;
				box-sizing: border-box;
				color: #333;
				display: block;
				font-size: 1em;
				margin-bottom: 1em;
				padding: 0.5em;
				width: 100%;
			}
			.sidebar > div,
			main address {
				align-items: center;
				display: flex;
				margin-bottom: 1em;
				font-style: normal;
			}
			main address .name,
			main address .webfinger {
				color: #000;
			}
			.name {
				color: #ccc;
				font-weight: bold;
				display: block;
			}
			.webfinger {
				color: #ccc;
				font-size: 0.8em;
				font-weight: bold;
				display: block;
				margin-top: 0.5em;
			}
			address img, .sidebar .fake-image {
				border-radius: 8px;
				margin-right: 1em;
				width: 48px;
				height: 48px;
				background-color: #333;
			}
			main {
				flex: 1;
				border: 1px solid #ccc;
				border-radius: 4px;
				background-color: #fff;
				margin: 1em;
				max-width: 600px;
			}
			main p {
				margin-bottom: 1em;
			}
			.sidebar h1 {
				font-size: 1.5em;
				margin-bottom: 1em;
				margin-top: 0;
				padding: 5px 10px;
				border-radius: 4px;
				background-color: #6364ff;
				color: #fff;
				display: inline-block;
			}
			hr {
				background: transparent;
				border: 0;
				border-top: 1px solid #ccc;
				flex: 0 0 auto;
				margin: 10px 0;
			}
			.sidebar ul {
				list-style-type: none;
				padding: 0;
			}
			.sidebar ul li {
				padding: 5px;
				color: #ccc;
			}
			main article {
				padding: 1em;
			}
			main .content {
				margin: 1em 0;
				font-size: 1.2em;
			}
			main .content h2 {
				font-size: 1.2em;
			}
			main .attachments {
				border-radius: 8px;
				box-sizing: border-box;
				display: grid;
				gap: 2px;
				grid-template-columns: 1fr 1fr;
				grid-template-rows: auto;
				margin: 20px 0;
				min-height: 64px;
				overflow: hidden;
				position: relative;
				width: 100%;
			}
			main .attachments img {
				max-width: 100%;
				height: 100%;
				margin: 1em 0;
				display: inline-block;
				object-fit: cover;
				overflow: hidden;
			}
			main .tags a {
				background-color: #f6f6f6;
				border-radius: 4px;
				color: #333;
				display: inline-block;
				margin-right: 0.5em;
				padding: 0.5em;
				text-decoration: none;
			}
			main .tags a:hover {
				background-color: #e6e6e6;
				text-decoration: underline;
			}
			main .column-header {
				font-size: 1.5em;
				margin: 0;
				padding: 5px 10px;
				border-bottom: 1px solid #ccc;
				vertical-align: middle;
			}
			.sidebar .mastodon-logo {
				height: 30px;
				width: auto;
			}
			header {
				display: flex;
				justify-content: center;
				align-items: center;
				flex-direction: column;
				padding: 10px;
				background: #fff;
				padding-bottom: 0.25rem;
				border-bottom: solid;
				border-color:#6b7280;
				border-width: 3px;
			}

			header h1 {
				margin-bottom: 0.75rem;
				font-weight: 600;
				font-size: 1.5rem;
			}

			header ul {
				display: flex;
				flex-direction: row;
				list-style: none;
				margin: 0;
				padding: 0;
			}
			.application-preview {
				min-height: 100%;
			}
			header ul > li {
				padding: 3px 10px;
				margin: 5px;
				cursor: pointer;
				border: none;
				height: 35px;
				background: inherit;
				font-size: 1.2rem;
				position: relative;
				padding-bottom: 17px;
				background: none;
				border: none;
				cursor: pointer;
				line-height: 30px;
			}

			header a {
				text-decoration: none;
					color: inherit;
			}

			header img {
				height: 100%;
			}

			#gancio .font-weight-light {
				font-weight: 300!important;
			}
			#gancio {
				background: #121212;
				color: #fff !important;
			}
			#gancio .title {
				font-size: 2.125rem !important;
				letter-spacing: .0073529412em !important;
				line-height: 2.5rem;
				font-family: Roboto,sans-serif !important;
				font-weight: 400;
				text-align: center !important;
				padding: 24px !important;
			}
			#gancio .title > strong {
				color: #fff !important;
				font-weight: bolder;
			}
			#gancio-event {
				word-wrap: break-word;
				max-width: 1200px;
				margin-left: auto;
				margin-right: auto;
				padding: 8px !important;
			}
			#gancio .col {
				padding: 12px;
			}
			#gancio .row {
				display: flex;
				flex: 1 1 auto;
				flex-wrap: wrap;
				margin: -12px auto;
			}
			#gancio .row .col-md-8 {
				flex: 0 0 64.6666666667%;
				max-width: 64.6666666667%;
			}
			#gancio .row .col-md-8 img {
				background-size: 100%;
				max-height: 125vh;
				max-width: 100%;
				-o-object-fit: contain;
				object-fit: contain;
				opacity: 1;
				transition: opacity .5s;
				height: auto;
				overflow: hidden;
				width: 100%;
			}
			#gancio .v-divider {
				border-width: thin 0 0;
				display: block;
				flex: 1 1 0px;
				height: 0;
				max-height: 0;
				max-width: 100%;
				transition: inherit;
				border-color: hsla(0,0%,100%,.12);
			}
			.mb-3 {
				margin-bottom: 12px !important;
			}
			#gancio .row .col-md-4 {
				flex: 0 0 31.3333333333%;
				max-width: 31.3333333333%;
			}
			#gancio .pr-md-0 {
				padding-right: 0 !important;
				padding-bottom: 200px;
			}
			#gancio .v-card {
				background-color: #1e1e1e;
				color: #fff;
				border: thin solid hsla(0,0%,100%,.12);
			}
			.text-decoration-none {
				text-decoration: none;
			}
			#gancio .v-card .v-icon:after {
				background-color: currentColor;
				border-radius: 50%;
				content: "";
				display: inline-block;
				height: 100%;
				left: 0;
				opacity: 0;
				pointer-events: none;
				position: absolute;
				top: 0;
				transform: scale(1.3);
				transition: opacity .2s cubic-bezier(.4,0,.6,1);
				width: 100%;
			}
			#gancio .v-card .v-icon {
				font-feature-settings: "liga";
				align-items: center;
				display: inline-flex;
				font-size: 24px;
				justify-content: center;
				letter-spacing: normal;
				line-height: 1;
				position: relative;
				text-indent: 0;
				transition: .3s cubic-bezier(.25,.8,.5,1),visibility 0s;
				-webkit-user-select: none;
				-moz-user-select: none;
				user-select: none;
				vertical-align: middle;
				fill: #a5a5a5;
			}
			.text-uppercase {
				text-transform: uppercase;
			}
			#gancio a {
				color: #ff6e40!important;
			}
			#gancio .v-chip {
				align-items: center;
				cursor: default;
				display: inline-flex;
				line-height: 20px;
				max-width: 100%;
				outline: none;
				overflow: hidden;
				padding: 0 12px;
				position: relative;
				-webkit-text-decoration: none;
				text-decoration: none;
				transition-duration: .28s;
				transition-property: box-shadow,opacity;
				transition-timing-function: cubic-bezier(.4,0,.2,1);
				vertical-align: middle;
				white-space: nowrap;
				border-radius: 5px;
			}
			#gancio .img {
				background-size: contain;
				position: relative;
			}
			#gancio .v-chip.v-chip--outlined {
				border-style: solid;
				border-width: thin;
				font-size: 12px;
				height: 24px;
			}
			#gancio .v-application .ml-1 {
				margin-left: 4px !important;
			}
			#gancio .v-application .mt-1 {
				margin-top: 4px !important;
			}
			#gancio-title {
				font-size: 2rem;
				font-weight: 600;
				-webkit-text-decoration: none;
				text-decoration: none;
				word-break: break-all;
			}
			.text-center {
				text-align: center !important;
			}
			#gancio .text-body-1 {
				font-size: 1rem !important;
				letter-spacing: .03125em !important;
				line-height: 1.5rem;
				font-family: Roboto,sans-serif !important;
				font-weight: 400;
				margin-top: 0.75rem;
			}
			.pb-3 {
				padding-bottom: 12px !important;
			}
			#gancio .container {
				margin-left: auto;
				margin-right: auto;
				padding: 12px;
				width: 100%;
			}
			#gancio .v-list-item__icon {
				height: 24px;
				margin-bottom: 8px;
				margin-top: 8px;
				margin-right: 20px !important;
			}
			.v-icon__svg {
				height: 24px;
				width: 24px;
			}
			#gancio .v-list-item {
				align-items: center;
				display: flex;
				flex: 1 1 100%;
				letter-spacing: normal;
				min-height: 48px;
				outline: none;
				padding: 0 16px;
				position: relative;
				-webkit-text-decoration: none;
				text-decoration: none;
			}
			.flex {
				display: flex;
			}
			.mx-auto {
				margin-left: auto;
				margin-right: auto;
			}
			.flex-col {
				flex-direction: column;
			}
			.mb-3 {
				margin-bottom: .75rem;
			}
			.justify-center {
				justify-content: center;
			}
			.max-h-80 {
				max-height: 20rem;
			}
			.flex-1 {
				flex: 1 1 0%;
			}
			.w-full {
				width: 100%;
			}
			.min-h-\[10rem\] {
				min-height: 10rem;
			}
			.h-full {
				height: 100%;
			}
			.pb-2 {
				padding-bottom: .5rem;
			}
			.bg-white {
				--tw-bg-opacity: 1;
				background-color: rgb(255 255 255 / var(--tw-bg-opacity));
			}
			.rounded {
				border-radius: .25rem;
			}
			.my-4 {
				margin-top: 1rem;
				margin-bottom: 1rem;
			}
			.relative {
				position: relative;
			}
			#mobilizon {
				--tw-bg-opacity: 1;
				background-color: rgb(239 238 244 / var(--tw-bg-opacity));
				font-family: ui-sans-serif,system-ui,sans-serif,"Apple Color Emoji","Segoe UI Emoji",Segoe UI Symbol,"Noto Color Emoji";
			}
			#mobilizon blockquote, dl, dd, h1, h2, h3, h4, h5, h6, hr, figure, p, pre {
				margin: 0;
			}
			@media (min-width: 1024px) {
				#mobilizon .container {
					max-width: 1024px;
				}
			}
			#mobilizon .datetime-container {
				width: calc(40px * var(--small));
				box-shadow: 0 0 12px #0003;
				height: calc(40px * var(--small));
				--small: 2;
			}
			.text-violet-3, .text-violet-title {
				--tw-text-opacity: 1;
				color: rgb(60 55 110 / var(--tw-text-opacity));
			}
			.rounded-lg {
				border-radius: .5rem;
			}
			.overflow-hidden {
				overflow: hidden;
			}
			.items-stretch {
				align-items: stretch;
			}
			.left-3 {
				left: .75rem;
			}
			.-top-16 {
				top: -4rem;
			}
			.absolute {
				position: absolute;
			}
			div.datetime-container .datetime-container-header {
				height: calc(10px * var(--small));
				background: #f3425f;
			}
			div.datetime-container .datetime-container-content {
				height: calc(30px * var(--small));
			}
			div.datetime-container time.day {
				font-size: calc(1rem * var(--small));
				line-height: calc(1rem * var(--small));
			}
			.font-semibold {
				font-weight: 600;
			}
			.block {
				display: block;
			}
			div.datetime-container time.month[data-v-dddab252] {
				font-size: 12px;
				line-height: 12p
			}
			.uppercase {
				text-transform: uppercase;
			}
			.font-semibold {
				font-weight: 600;
			}
			.py-1 {
				padding-top: .25rem;
				padding-bottom: .25rem;
			}
			.px-0 {
				padding-left: 0;
				padding-right: 0;
			}
			.pt-4 {
				padding-top: 1rem;
			}
			.px-2 {
				padding-left: .5rem;
				padding-right: .5rem;
			}
			.gap-4 {
				gap: 1rem;
			}
			@media (min-width: 768px) {
				.md\:flex-row-reverse {
					flex-direction: row-reverse;
				}
			}
			.shadow-md {
				--tw-shadow: 0 4px 6px -1px rgb(0 0 0 / .1), 0 2px 4px -2px rgb(0 0 0 / .1);
				--tw-shadow-colored: 0 4px 6px -1px var(--tw-shadow-color), 0 2px 4px -2px var(--tw-shadow-color);
				box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000),var(--tw-ring-shadow, 0 0 #0000),var(--tw-shadow);
			}
			.w-full {
				width: 100%;
			}
			.min-h-40, .min-h-\[10rem\] {
				min-height: 10rem;
			}
			.h-full {
				height: 100%;
			}
			div.starttime-container {
				width: auto;
				box-shadow: 0 0 12px #0003;
				padding: .25rem;
				font-size: calc(1rem * var(--small));
			}
			.right-3 {
				right: .75rem;
			}
			.-top-16 {
				top: -4rem;
			}
			.font-semibold {
				font-weight: 600;
			}
			.pt-4 {
				padding-top: 1rem;
			}
			.px-2 {
				padding-left: .5rem;
				padding-right: .5rem;
			}
			.justify-end {
				justify-content: flex-end;
			}
			.flex-wrap {
				flex-wrap: wrap;
			}
			.flex-1 {
				flex: 1 1 0%;
			}
			.min-w-\[300px\] {
				min-width: 300px;
			}
			.font-bold {
				font-weight: 700;
			}
			.text-4xl {
				font-size: 2.25rem;
				line-height: 2.5rem;
			}
			.m-0 {
				margin: 0;
			}
			.inline {
				display: inline;
			}
			.gap-1 {
				gap: .25rem;
			}
			.inline-flex {
				display: inline-flex;
			}
			.text-black {
				--tw-text-opacity: 1;
				color: rgb(0 0 0 / var(--tw-text-opacity));
			}
			.text-sm {
				font-size: .875rem;
				line-height: 1.25rem;
			}
			.py-1 {
				padding-top: .25rem;
				padding-bottom: .25rem;
			}
			.px-2 {
				padding-left: .5rem;
				padding-right: .5rem;
			}
			.bg-mbz-info {
				--tw-bg-opacity: 1;
				background-color: rgb(54 188 212 / var(--tw-bg-opacity));
			}
			.rounded-md {
				border-radius: .375rem;
			}
			.truncate {
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
			}
			.bg-purple-3 {
				--tw-bg-opacity: 1;
				background-color: rgb(230 228 244 / var(--tw-bg-opacity));
			}
			#mobilizon a:link {
				text-decoration: none;
			}
			.gap-y-4 {
				row-gap: 1rem;
			}
			.gap-2 {
				gap: .5rem;
				row-gap: 0.5rem;
			}
			.w-min {
				width: min-content;
			}
			.ml-auto {
				margin-left: auto;
			}
			#mobilizon .btn {
				height: 2.5rem;
				border-radius: .25rem;
				--tw-bg-opacity: 1;
				background-color: rgb(30 125 151 / var(--tw-bg-opacity));
				padding: .5rem 1rem;
				font-weight: 700;
				--tw-text-opacity: 1;
				color: rgb(255 255 255 / var(--tw-text-opacity));
				outline: 2px solid transparent;
				outline-offset: 2px;
				--tw-ring-opacity: 1;
				--tw-ring-color: rgb(147 197 253 / var(--tw-ring-opacity));
				--tw-ring-offset-width: 1px;
				--tw-ring-offset-color: #f8fafc;
			}
			#mobilizon .o-btn {
				-moz-appearance: none;
				-webkit-appearance: none;
				position: relative;
				display: inline-flex;
				cursor: pointer;
				text-align: center;
				white-space: nowrap;
				align-items: center;
				justify-content: center;
				vertical-align: top;
				text-decoration: none;
				-webkit-touch-callout: none;
				-webkit-user-select: none;
				user-select: none;
			}
			.pt-1 {
				padding-top: .25rem;
			}
			.pb-3 {
				padding-bottom: .75rem;
			}
			.px-3 {
				padding-left: .75rem;
				padding-right: .75rem;
			}
			.bg-white {
				--tw-bg-opacity: 1;
				background-color: rgb(255 255 255 / var(--tw-bg-opacity));
			}
			.rounded {
				border-radius: .25rem;
			}
			.mb-4 {
				margin-bottom: 1rem;
			}
			.text-2xl {
				font-size: 1.5rem;
				line-height: 2rem;
			}
			#mobilizon h2 {
				margin-top: .5rem;
				font-size: 1.25rem;
				line-height: 1.75rem;
				margin-bottom: 0.2rem;
			}
			@media (min-width: 1024px) {
				.lg\:prose-xl {
					font-size: 1.25rem;
					line-height: 1.8;
				}
			}
			@media (min-width: 768px) {
				.md\:prose-lg {
					font-size: 1.125rem;
					line-height: 1.7777778;
				}
			}
			.mt-4 {
				margin-top: 1rem;
			}
			.prose {
				--tw-prose-body: #374151;
				--tw-prose-headings: #111827;
				--tw-prose-lead: #4b5563;
				--tw-prose-links: #111827;
				--tw-prose-bold: #111827;
				--tw-prose-counters: #6b7280;
				--tw-prose-bullets: #d1d5db;
				--tw-prose-hr: #e5e7eb;
				--tw-prose-quotes: #111827;
				--tw-prose-quote-borders: #e5e7eb;
				--tw-prose-captions: #6b7280;
				--tw-prose-kbd: #111827;
				--tw-prose-kbd-shadows: 17 24 39;
				--tw-prose-code: #111827;
				--tw-prose-pre-code: #e5e7eb;
				--tw-prose-pre-bg: #1f2937;
				--tw-prose-th-borders: #d1d5db;
				--tw-prose-td-borders: #e5e7eb;
				--tw-prose-invert-body: #d1d5db;
				--tw-prose-invert-headings: #fff;
				--tw-prose-invert-lead: #9ca3af;
				--tw-prose-invert-links: #fff;
				--tw-prose-invert-bold: #fff;
				--tw-prose-invert-counters: #9ca3af;
				--tw-prose-invert-bullets: #4b5563;
				--tw-prose-invert-hr: #374151;
				--tw-prose-invert-quotes: #f3f4f6;
				--tw-prose-invert-quote-borders: #374151;
				--tw-prose-invert-captions: #9ca3af;
				--tw-prose-invert-kbd: #fff;
				--tw-prose-invert-kbd-shadows: 255 255 255;
				--tw-prose-invert-code: #fff;
				--tw-prose-invert-pre-code: #d1d5db;
				--tw-prose-invert-pre-bg: rgb(0 0 0 / 50%);
				--tw-prose-invert-th-borders: #4b5563;
				--tw-prose-invert-td-borders: #374151;
				font-size: 1rem;
				line-height: 1.75;
			}
			#mobilizon .prose {
				color: var(--tw-prose-body);
				max-width: 65ch;
			}
			#mobilizon .event-description a {
				display: inline-block;
				--tw-bg-opacity: 1;
				background-color: rgb(242 242 242 / var(--tw-bg-opacity));
				padding: .25rem;
				--tw-text-opacity: 1;
				color: rgb(0 0 0 / var(--tw-text-opacity));
			}
			#mobilizon .prose :where(a):not(:where([class~="not-prose"], [class~="not-prose"] *)) {
				color: var(--tw-prose-links);
				text-decoration: underline;
				font-weight: 500;
			}
			.mt-20 {
				margin-top: 5rem;
			}
			.mb-10 {
				margin-bottom: 2.5rem;
			}
			#mobilizon a {
				color: inherit;
				text-decoration: inherit;
			}
			.items-center {
				align-items: center;
			}
			#mobilizon .max-w-screen-sm {
				max-width: 440px;
			}
			#mobilizon .h-min {
				height: min-content;
			}
			.p-4 {
				padding: 1rem;
			}
			.sticky {
				position: sticky;
			}
			.pl-2 {
				padding-left: .5rem;
			}
			.w-12 {
				width: 3rem;
			}
			.h-12 {
				height: 3rem;
			}
			.object-cover {
				object-fit: cover;
			}
			.rounded-full {
				border-radius: 9999px;
			}
			.h-full {
				height: 100%;
			}
			#mobilizon div.eventMetadataBlock .content-wrapper {
				max-width: calc(100vw - 52px);
			}
			#mobilizon div.address-wrapper div.address .map-show-button[data-v-3aec49f0] {
				cursor: pointer;
			}
			#mobilizon .btn-text {
				border-color: transparent;
				background-color: transparent;
				font-weight: 400;
				--tw-text-opacity: 1;
				color: rgb(0 0 0 / var(--tw-text-opacity));
				text-decoration-line: underline;
			}
			#mobilizon .o-btn__wrapper {
				margin-left: -.1875em;
				margin-right: -.1875em;
				display: inline-flex;
				align-items: center;
				justify-content: center;
				position: relative;
				width: 100%;
			}
			#mobilizon .btn-text:hover {
				--tw-bg-opacity: 1;
				background-color: rgb(228 228 231 / var(--tw-bg-opacity));
				--tw-text-opacity: 1;
				color: rgb(0 0 0 / var(--tw-text-opacity));
			}
			#mobilizon .btn:hover {
				--tw-text-opacity: 1;
				color: rgb(226 232 240 / var(--tw-text-opacity));
			}
			#mobilizon .btn:hover {
				--tw-bg-opacity: 1;
				background-color: rgb(21 86 104 / var(--tw-bg-opacity));
			}
			.space-x-4 > :not([hidden]) ~ :not([hidden]) {
				--tw-space-x-reverse: 0;
				margin-right: calc(1rem * var(--tw-space-x-reverse));
				margin-left: calc(1rem * calc(1 - var(--tw-space-x-reverse)));
			}
			.text-gray-900 {
				--tw-text-opacity: 1;
				color: rgb(17 24 39 / var(--tw-text-opacity));
			}
			.tracking-tight {
				letter-spacing: -.025em;
			}
			.font-medium {
				font-weight: 500;
			}
			.text-xl {
				font-size: 1.25rem;
				line-height: 1.75rem;
			}
			.whitespace-pre-line {
				white-space: pre-line;
			}
			.line-clamp-2 {
				overflow: hidden;
				display: -webkit-box;
				-webkit-box-orient: vertical;
				-webkit-line-clamp: 2;
			}
			.text-gray-500 {
				--tw-text-opacity: 1;
				color: rgb(107 114 128 / var(--tw-text-opacity));
			}
			.truncate {
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
			}
			#mobilizon h2 {
				margin-top: .5rem;
				font-size: 1.25rem;
				line-height: 1.75rem;
			}
		</style>
	</head>
	<body>
		<header>
			<h1><?php esc_html_e( 'See how your event appears on different Fediverse platforms', 'event-bridge-for-activitypub' ); ?></h1>
			<nav role="navigation" aria-label="Main" class="menu">
				<ul class="menu__list">
					<li onclick="openPreviewForApplication('mobilizon')" class="application-navigation is-active">
						<svg aria-labelledby="mobilizon" xmlns="http://www.w3.org/2000/svg" height="30" viewBox="0 0 248.16 46.78"><g data-name="Calque 2"><g data-name="header"><path fill="#474467" d="M0 45.82l3.18-40.8a29.88 29.88.0 015.07-.36 27.74 27.74.0 014.95.36l4.86 17.16a92.19 92.19.0 012.34 10.08h.36a92.19 92.19.0 012.34-10.08L28 5.02a29.23 29.23.0 015-.36 29.23 29.23.0 015 .36l3.18 40.8a13.61 13.61.0 01-3.63.42A23.41 23.41.0 0133.92 46l-1.2-19.92q-.36-5.52-.48-12.84h-.44l-7.32 26.51a25.62 25.62.0 01-4 .3 23.36 23.36.0 01-3.84-.3L9.36 13.24H9q-.3 8.94-.48 12.84L7.26 46a22.47 22.47.0 01-3.6.24A13.75 13.75.0 010 45.82zM74 31.06q0 8-4.26 12.3a12.21 12.21.0 01-9 3.42 12.21 12.21.0 01-9-3.42q-4.26-4.26-4.26-12.3t4.24-12.31a12.21 12.21.0 019-3.42 12.21 12.21.0 019 3.42Q74 23.02 74 31.06zM60.75 20.98q-5.67.0-5.67 10.08t5.67 10.08 5.67-10.08-5.67-10.08zm42.45-1.23q2.7 4.11 2.7 11.28T102 42.31a13.18 13.18.0 01-10 4.11 31.41 31.41.0 01-11.34-2V2.2l.4-.45h2.76A4 4 0 0187 2.83a5.38 5.38.0 01.93 3.57v11.94a12.08 12.08.0 017.56-2.7 8.71 8.71.0 017.71 4.11zm-9.72 2a7.28 7.28.0 00-5.58 2.82v16a15 15 0 004.08.54 5.25 5.25.0 004.68-2.67q1.68-2.67 1.68-7.59.0-9.03-4.86-9.1zM121 22v23.94a20.85 20.85.0 01-3.66.3 23 23 0 01-3.78-.3V24.75q0-3.24-2.7-3.24h-.72a9.32 9.32.0 01-.3-2.58 10.7 10.7.0 01.3-2.7 39.63 39.63.0 014.38-.24h1a5.19 5.19.0 014 1.62A6.27 6.27.0 01121 22z"></path><path fill="#ffd599" d="M119.82.84a7.37 7.37.0 01.6 3 7.37 7.37.0 01-.6 3 7.46 7.46.0 01-3.87.84 6.49 6.49.0 01-3.69-.93 7.37 7.37.0 01-.6-3 7.37 7.37.0 01.6-3 8.09 8.09.0 013.87-.84 7.05 7.05.0 013.69.93z"></path><path fill="#474467" d="M139.08 40.42h2a10.23 10.23.0 01.6 3.18 9.24 9.24.0 01-.18 2.1 38.47 38.47.0 01-5.64.54q-6.48.0-6.48-7v-37l.36-.42h2.88a3.94 3.94.0 013.12 1.05 5.52 5.52.0 01.9 3.57v31.31q-.02 2.67 2.44 2.67zM155.94 22v23.94a20.85 20.85.0 01-3.66.3 23 23 0 01-3.78-.3V24.75q0-3.24-2.7-3.24h-.72a9.32 9.32.0 01-.3-2.58 10.7 10.7.0 01.3-2.7 39.63 39.63.0 014.38-.24h1a5.19 5.19.0 014.05 1.62 6.27 6.27.0 011.43 4.39z"></path><path fill="#ffd599" d="M154.8 2.84a7.37 7.37.0 01.6 3 7.37 7.37.0 01-.6 3 7.46 7.46.0 01-3.87.84 6.49 6.49.0 01-3.69-.93 7.37 7.37.0 01-.6-3 7.37 7.37.0 01.6-3 8.09 8.09.0 013.87-.84 7.05 7.05.0 013.69.93z"></path><path fill="#474467" d="M163.08 39.22l8.76-11.82q1.32-1.8 4.8-5.7l-.18-.3a63.09 63.09.0 01-7.74.42H163a9.79 9.79.0 01-.24-2.34 15.8 15.8.0 01.42-3.3h20.4a16.31 16.31.0 011 4.26 4.1 4.1.0 01-.78 2.34L175 34.66a64.65 64.65.0 01-4.56 5.7l.18.24q3.12-.3 5.22-.3h2.58a15.35 15.35.0 006.12-.9 9.4 9.4.0 01.72 3.12q0 3.42-4.32 3.42h-18a14.27 14.27.0 01-.9-3.93 5.08 5.08.0 011.04-2.79zm52.8-8.16q0 8-4.26 12.3a13.63 13.63.0 01-18.06.0q-4.26-4.26-4.26-12.3t4.26-12.31a13.63 13.63.0 0118.06.0q4.26 4.27 4.26 12.31zm-13.29-10.08q-5.67.0-5.67 10.08t5.67 10.08 5.67-10.08-5.67-10.08zM247 25.84v13.32a11 11 0 001.2 5.64 7 7 0 01-4.41 1.56q-2.43.0-3.33-1.14a5.69 5.69.0 01-.9-3.54V27.4a7.74 7.74.0 00-.72-3.87 2.78 2.78.0 00-2.58-1.17 8.62 8.62.0 00-6.3 3v20.58a20.85 20.85.0 01-3.66.3 23 23 0 01-3.78-.3v-29.7l.42-.36h2.76q3.42.0 4.08 3.6 4.38-3.84 8.73-3.84t6.42 2.82a12.17 12.17.0 012.07 7.38z"></path><path fill="#ffd599" d="M57.26 10.75a7.37 7.37.0 01-.6-3 7.37 7.37.0 01.6-3 8.09 8.09.0 013.87-.84 7.05 7.05.0 013.69.84 7.37 7.37.0 01.6 3 7.37 7.37.0 01-.6 3 7.46 7.46.0 01-3.87.84 6.49 6.49.0 01-3.69-.84zm141 0a7.37 7.37.0 01-.6-3 7.37 7.37.0 01.6-3 8.09 8.09.0 013.87-.84 7.05 7.05.0 013.69.84 7.37 7.37.0 01.6 3 7.37 7.37.0 01-.6 3 7.46 7.46.0 01-3.87.84 6.49 6.49.0 01-3.69-.84z"></path></g></g></svg>
					</li>
					<li onclick="openPreviewForApplication('mastodon')" class="application-navigation">
						<svg aria-labelledby="mastodon" style="height:38px!important; padding-top:5px;" height="39" viewBox="0 0 713.35878 175.8678"><use xlink:href="#mastodon-svg-logo-full"></use><symbol id="mastodon-svg-logo-full" viewBox="0 0 713.35878 175.8678"><path d="M160.55476 105.43125c-2.4125 12.40625-21.5975 25.9825-43.63375 28.61375-11.49125 1.3725-22.80375 2.63125-34.8675 2.07875-19.73-.90375-35.2975-4.71-35.2975-4.71.0 1.92125.11875 3.75.355 5.46 2.565 19.47 19.3075 20.6375 35.16625 21.18125 16.00625.5475 30.2575-3.9475 30.2575-3.9475l.65875 14.4725s-11.19625 6.01125-31.14 7.11625c-10.99875.605-24.65375-.27625-40.56-4.485C6.99851 162.08 1.06601 125.31.15851 88-.11899 76.9225.05226 66.47625.05226 57.74125c0-38.1525 24.99625-49.335 24.99625-49.335C37.65226 2.6175 59.27976.18375 81.76351.0h.5525c22.48375.18375 44.125 2.6175 56.72875 8.40625.0.0 24.99625 11.1825 24.99625 49.335.0.0.3125 28.1475-3.48625 47.69" fill="#3088d4"></path><path fill="var(--primary)" d="M34.65751 48.494c0-5.55375 4.5025-10.055 10.055-10.055 5.55375.0 10.055 4.50125 10.055 10.055.0 5.5525-4.50125 10.055-10.055 10.055-5.5525.0-10.055-4.5025-10.055-10.055M178.86476 60.69975v46.195h-18.30125v-44.8375c0-9.4525-3.9775-14.24875-11.9325-14.24875-8.79375.0-13.2025 5.69125-13.2025 16.94375V89.2935h-18.19375V64.75225c0-11.2525-4.40875-16.94375-13.2025-16.94375-7.955.0-11.9325 4.79625-11.9325 14.24875v44.8375H73.79851v-46.195c0-9.44125 2.40375-16.94375 7.2325-22.495 4.98-5.55 11.50125-8.395 19.595-8.395 9.36625.0 16.45875 3.59875 21.14625 10.79875l4.56 7.6425 4.55875-7.6425c4.68875-7.2 11.78-10.79875 21.1475-10.79875 8.09375.0 14.61375 2.845 19.59375 8.395 4.82875 5.55125 7.2325 13.05375 7.2325 22.495m63.048 22.963875c3.77625-3.99 5.595-9.015 5.595-15.075s-1.81875-11.085-5.595-14.9275c-3.63625-3.99125-8.25375-5.91125-13.84875-5.91125-5.59625.0-10.2125 1.92-13.84875 5.91125-3.6375 3.8425-5.45625 8.8675-5.45625 14.9275s1.81875 11.085 5.45625 15.075c3.63625 3.8425 8.2525 5.76375 13.84875 5.76375 5.595.0 10.2125-1.92125 13.84875-5.76375m5.595-52.025h18.04625v73.9h-18.04625v-8.72125c-5.455 7.2425-13.01 10.79-22.80125 10.79-9.3725.0-17.34625-3.695-24.06125-11.23375-6.57375-7.5375-9.93125-16.84875-9.93125-27.785.0-10.78875 3.3575-20.10125 9.93125-27.63875 6.715-7.5375 14.68875-11.38 24.06125-11.38 9.79125.0 17.34625 3.5475 22.80125 10.78875v-8.72zm78.76175 35.62c5.315 3.99 7.97375 9.60625 7.83375 16.7.0 7.53875-2.65875 13.45-8.11375 17.58875-5.45625 3.99125-12.03 6.06-20.00375 6.06-14.40875.0-24.20125-5.9125-29.3775-17.58875l15.66875-9.31c2.0975 6.35375 6.71375 9.60625 13.70875 9.60625 6.43375.0 9.6525-2.07 9.6525-6.35625.0-3.10375-4.1975-5.91125-12.73-8.1275-3.21875-.8875-5.87625-1.77375-7.97375-2.51375-2.9375-1.18125-5.455-2.5125-7.55375-4.1375-5.17625-3.99-7.83375-9.3125-7.83375-16.11.0-7.2425 2.5175-13.00625 7.55375-17.145 5.17625-4.28625 11.47-6.355 19.025-6.355 12.03.0 20.84375 5.1725 26.5775 15.66625l-15.38625 8.8675c-2.23875-5.02375-6.015-7.53625-11.19125-7.53625-5.45625.0-8.11375 2.06875-8.11375 6.05875.0 3.10375 4.19625 5.91125 12.73 8.12875 6.575 1.4775 11.75 3.695 15.5275 6.50375M383.626635 49.966125h-15.8075v30.7425c0 3.695 1.4 5.91125 4.0575 6.945 1.95875.74 5.875.8875 11.75.59125v17.29375c-12.16875 1.4775-20.9825.295-26.15875-3.69625-5.175-3.8425-7.69375-10.93625-7.69375-21.13375v-30.7425h-12.17v-18.3275h12.17v-14.9275l18.045-5.76375v20.69125h15.8075v18.3275zM441.124885 83.2205c3.6375-3.84375 5.455-8.72125 5.455-14.6325.0-5.91125-1.8175-10.78875-5.455-14.63125-3.6375-3.84375-8.11375-5.76375-13.57-5.76375-5.455.0-9.93125 1.92-13.56875 5.76375-3.4975 3.99-5.31625 8.8675-5.31625 14.63125.0 5.765 1.81875 10.6425 5.31625 14.6325 3.6375 3.8425 8.11375 5.76375 13.56875 5.76375 5.45625.0 9.9325-1.92125 13.57-5.76375m-39.86875 13.15375c-7.13375-7.5375-10.63125-16.70125-10.63125-27.78625.0-10.9375 3.4975-20.1 10.63125-27.6375s15.9475-11.38 26.29875-11.38c10.3525.0 19.165 3.8425 26.3 11.38s10.77125 16.84875 10.77125 27.6375c0 10.9375-3.63625 20.24875-10.77125 27.78625-7.135 7.53875-15.8075 11.2325-26.3 11.2325-10.49125.0-19.165-3.69375-26.29875-11.2325M524.92126 83.663625c3.6375-3.99 5.455-9.015 5.455-15.075s-1.8175-11.085-5.455-14.9275c-3.63625-3.99125-8.25375-5.91125-13.84875-5.91125-5.59625.0-10.2125 1.92-13.98875 5.91125-3.63625 3.8425-5.45625 8.8675-5.45625 14.9275s1.82 11.085 5.45625 15.075c3.77625 3.8425 8.5325 5.76375 13.98875 5.76375 5.595.0 10.2125-1.92125 13.84875-5.76375m5.455-81.585h18.04625v103.46h-18.04625v-8.72125c-5.315 7.2425-12.87 10.79-22.66125 10.79-9.3725.0-17.485-3.695-24.2-11.23375-6.575-7.5375-9.9325-16.84875-9.9325-27.785.0-10.78875 3.3575-20.10125 9.9325-27.63875 6.715-7.5375 14.8275-11.38 24.2-11.38 9.79125.0 17.34625 3.5475 22.66125 10.78875v-38.28zm81.42 81.141875c3.63625-3.84375 5.455-8.72125 5.455-14.6325.0-5.91125-1.81875-10.78875-5.455-14.63125-3.6375-3.84375-8.11375-5.76375-13.57-5.76375-5.455.0-9.9325 1.92-13.56875 5.76375-3.49875 3.99-5.31625 8.8675-5.31625 14.63125.0 5.765 1.8175 10.6425 5.31625 14.6325 3.63625 3.8425 8.11375 5.76375 13.56875 5.76375 5.45625.0 9.9325-1.92125 13.57-5.76375m-39.86875 13.15375c-7.135-7.5375-10.63125-16.70125-10.63125-27.78625.0-10.9375 3.49625-20.1 10.63125-27.6375s15.9475-11.38 26.29875-11.38c10.3525.0 19.165 3.8425 26.3 11.38s10.77125 16.84875 10.77125 27.6375c0 10.9375-3.63625 20.24875-10.77125 27.78625-7.135 7.53875-15.8075 11.2325-26.3 11.2325-10.49125.0-19.16375-3.69375-26.29875-11.2325M713.35876 60.163875v45.37375h-18.04625v-43.00875c0-4.8775-1.25875-8.5725-3.77625-11.38-2.37875-2.5125-5.73625-3.84375-10.0725-3.84375-10.2125.0-15.3875 6.06-15.3875 18.3275v39.905h-18.04625v-73.89875h18.04625v8.27625c4.33625-6.94625 11.19-10.345 20.84375-10.345 7.69375.0 13.98875 2.66 18.885 8.12875 5.035 5.46875 7.55375 12.85875 7.55375 22.465"></path></symbol></svg>
					</li>
					<li onclick="openPreviewForApplication('gancio')" class="application-navigation">
						<img src="<?php echo esc_url( plugins_url( 'assets/img/gancio.png', EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_FILE ) ); ?>"><div style="display: none;">G</div>ancio
					</li>
				</ul>
			</nav>
		</header>
		<div id="mastodon" class="application-preview" style="display: none">
			<div class="columns">
			<aside class="sidebar">
				<input type="search" disabled="disabled" placeholder="<?php \esc_html_e( 'Search', 'event-bridge-for-activitypub' ); ?>" />
				<div>
					<div class="fake-image"></div>
					<div>
						<div class="name">
							████ ██████
						</div>
						<div class="webfinger">
							@█████@██████
						</div>
					</div>
				</div>
				<textarea rows="10" cols="50" disabled="disabled" placeholder="<?php \esc_html_e( 'What\'s up', 'event-bridge-for-activitypub' ); ?>"></textarea>
			</aside>
			<main>
				<h1 class="column-header">
					Home
				</h1>
				<article>
					<address>
						<img src="<?php echo \esc_url( $user->get_icon()['url'] ); ?>" alt="<?php echo \esc_attr( $user->get_name() ); ?>" />
						<div>
							<div class="name">
								<?php echo \esc_html( $user->get_name() ); ?>
							</div>
							<div class="webfinger">
								<?php echo \esc_html( '@' . $user->get_webfinger() ); ?>
							</div>
						</div>
					</address>
					<div class="content">
						<br>
						<h2><?php echo \esc_html( $object->get_name() ); ?></h2>
						<?php echo wp_kses( 'Event' === $object->get_type() ? $object->get_summary() : $object->get_summary(), ACTIVITYPUB_MASTODON_HTML_SANITIZER ); ?>
						<a href="<?php echo \esc_html( $object->get_id() ); ?>"><?php echo \esc_html( $object->get_url() ); ?></h2>
					</div>
					<div class="attachments">
						<?php foreach ( $object->get_attachment() as $attachment ) : ?>
							<?php if ( 'Image' === $attachment['type'] ) : ?>
								<?php
								if ( isset( $attachment['url'] ) ) {
									$first_image_attachment = $attachment;
								}
								?>
								<img src="<?php echo \esc_url( $attachment['url'] ); ?>" alt="<?php echo \esc_attr( isset( $attachment['name'] ) ? $attachment['name'] : '' ); ?>" />
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
					<div class="tags">
						<?php foreach ( $object->get_tag() as $hashtag ) : ?>
							<?php if ( 'Hashtag' === $hashtag['type'] ) : ?>
								<a href="<?php echo \esc_url( $hashtag['href'] ); ?>"><?php echo \esc_html( $hashtag['name'] ); ?></a>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</article>
			</main>
			<aside class="sidebar">
				<svg viewBox="0 0 261 66" class="mastodon-logo" role="img">
					<title>Mastodon</title>
					<use xlink:href="#logo-symbol-wordmark">
						<symbol id="logo-symbol-wordmark"><path d="M60.7539 14.4034C59.8143 7.41942 53.7273 1.91557 46.5117 0.849066C45.2943 0.668854 40.6819 0.0130005 29.9973 0.0130005H29.9175C19.2299 0.0130005 16.937 0.668854 15.7196 0.849066C8.70488 1.88602 2.29885 6.83152 0.744617 13.8982C-0.00294988 17.3784 -0.0827298 21.2367 0.0561464 24.7759C0.254119 29.8514 0.292531 34.918 0.753482 39.9728C1.07215 43.3305 1.62806 46.6614 2.41704 49.9406C3.89445 55.9969 9.87499 61.0369 15.7344 63.0931C22.0077 65.2374 28.7542 65.5934 35.2184 64.1212C35.9295 63.9558 36.6318 63.7638 37.3252 63.5451C38.8971 63.0459 40.738 62.4875 42.0913 61.5067C42.1099 61.4929 42.1251 61.4751 42.1358 61.4547C42.1466 61.4342 42.1526 61.4116 42.1534 61.3885V56.4903C42.153 56.4687 42.1479 56.4475 42.1383 56.4281C42.1287 56.4088 42.1149 56.3918 42.0979 56.3785C42.0809 56.3652 42.0611 56.3559 42.04 56.3512C42.019 56.3465 41.9971 56.3466 41.9761 56.3514C37.8345 57.3406 33.5905 57.8364 29.3324 57.8286C22.0045 57.8286 20.0336 54.3514 19.4693 52.9038C19.0156 51.6527 18.7275 50.3476 18.6124 49.0218C18.6112 48.9996 18.6153 48.9773 18.6243 48.9569C18.6333 48.9366 18.647 48.9186 18.6643 48.9045C18.6816 48.8904 18.7019 48.8805 18.7237 48.8758C18.7455 48.871 18.7681 48.8715 18.7897 48.8771C22.8622 49.8595 27.037 50.3553 31.2265 50.3542C32.234 50.3542 33.2387 50.3542 34.2463 50.3276C38.4598 50.2094 42.9009 49.9938 47.0465 49.1843C47.1499 49.1636 47.2534 49.1459 47.342 49.1193C53.881 47.8637 60.1038 43.9227 60.7362 33.9431C60.7598 33.5502 60.8189 29.8278 60.8189 29.4201C60.8218 28.0345 61.2651 19.5911 60.7539 14.4034Z" fill="url(#paint0_linear_89_11)"></path>
							<path d="M12.3442 18.3034C12.3442 16.2668 13.9777 14.6194 15.997 14.6194C18.0163 14.6194 19.6497 16.2668 19.6497 18.3034C19.6497 20.34 18.0163 21.9874 15.997 21.9874C13.9777 21.9874 12.3442 20.34 12.3442 18.3034Z" fill="currentColor"></path>
							<path d="M66.1484 21.4685V38.3839H59.4988V21.9744C59.4988 18.5109 58.0583 16.7597 55.1643 16.7597C51.9746 16.7597 50.3668 18.8482 50.3668 22.9603V31.9499H43.7687V22.9603C43.7687 18.8352 42.1738 16.7597 38.9712 16.7597C36.0901 16.7597 34.6367 18.5109 34.6367 21.9744V38.3839H28V21.4685C28 18.018 28.8746 15.268 30.6238 13.2314C32.4374 11.1948 34.8039 10.157 37.7365 10.157C41.132 10.157 43.7172 11.4802 45.415 14.1135L47.0742 16.9154L48.7334 14.1135C50.4311 11.4802 53.0035 10.157 56.4119 10.157C59.3444 10.157 61.711 11.1948 63.5246 13.2314C65.2738 15.268 66.1484 18.005 66.1484 21.4685ZM89.0297 29.8743C90.4059 28.4085 91.0619 26.5795 91.0619 24.3613C91.0619 22.1431 90.4059 20.3011 89.0297 18.9001C87.7049 17.4343 86.0329 16.7338 84.0007 16.7338C81.9685 16.7338 80.2965 17.4343 78.9717 18.9001C77.6469 20.3011 76.991 22.1431 76.991 24.3613C76.991 26.5795 77.6469 28.4215 78.9717 29.8743C80.2965 31.2753 81.9685 31.9888 84.0007 31.9888C86.0329 31.9888 87.7049 31.2883 89.0297 29.8743ZM91.0619 10.8316H97.6086V37.891H91.0619V34.6999C89.0811 37.3462 86.3416 38.6563 82.7788 38.6563C79.2161 38.6563 76.4765 37.3073 74.0456 34.5442C71.6533 31.7812 70.4443 28.3696 70.4443 24.3743C70.4443 20.3789 71.6661 17.0192 74.0456 14.2561C76.4893 11.4931 79.3833 10.0922 82.7788 10.0922C86.1744 10.0922 89.0811 11.3894 91.0619 14.0356V10.8445V10.8316ZM119.654 23.8683C121.583 25.3342 122.548 27.3837 122.496 29.9781C122.496 32.7411 121.532 34.9075 119.551 36.4122C117.57 37.878 115.178 38.6304 112.284 38.6304C107.049 38.6304 103.499 36.4641 101.621 32.1963L107.306 28.7847C108.065 31.1067 109.737 32.3001 112.284 32.3001C114.625 32.3001 115.782 31.5477 115.782 29.9781C115.782 28.8366 114.265 27.8118 111.165 27.0075C109.995 26.6833 109.03 26.359 108.271 26.0865C107.204 25.6585 106.29 25.1655 105.532 24.5688C103.654 23.103 102.689 21.1572 102.689 18.6666C102.689 16.0203 103.602 13.9059 105.429 12.3882C107.306 10.8186 109.596 10.0662 112.335 10.0662C116.709 10.0662 119.898 11.9601 121.982 15.7998L116.4 19.0428C115.59 17.2008 114.213 16.2798 112.335 16.2798C110.355 16.2798 109.39 17.0321 109.39 18.498C109.39 19.6395 110.908 20.6643 114.008 21.4685C116.4 22.0134 118.278 22.8176 119.641 23.8554L119.654 23.8683ZM140.477 17.538H134.741V28.7977C134.741 30.1468 135.255 30.964 136.22 31.3402C136.927 31.6126 138.355 31.6645 140.49 31.5607V37.891C136.079 38.4358 132.876 37.9948 130.998 36.5419C129.12 35.1409 128.207 32.5336 128.207 28.8106V17.538H123.795V10.8316H128.207V5.37038L134.754 3.25595V10.8316H140.49V17.538H140.477ZM161.352 29.7187C162.677 28.3177 163.333 26.5276 163.333 24.3613C163.333 22.195 162.677 20.4178 161.352 19.0039C160.027 17.6029 158.407 16.8894 156.426 16.8894C154.445 16.8894 152.825 17.5899 151.5 19.0039C150.227 20.4697 149.571 22.2469 149.571 24.3613C149.571 26.4757 150.227 28.2529 151.5 29.7187C152.825 31.1196 154.445 31.8331 156.426 31.8331C158.407 31.8331 160.027 31.1326 161.352 29.7187ZM146.883 34.5313C144.297 31.7682 143.024 28.4215 143.024 24.3613C143.024 20.3011 144.297 17.0062 146.883 14.2432C149.468 11.4802 152.67 10.0792 156.426 10.0792C160.182 10.0792 163.384 11.4802 165.97 14.2432C168.555 17.0062 169.88 20.4178 169.88 24.3613C169.88 28.3047 168.555 31.7682 165.97 34.5313C163.384 37.2943 160.233 38.6434 156.426 38.6434C152.619 38.6434 149.468 37.2943 146.883 34.5313ZM191.771 29.8743C193.095 28.4085 193.751 26.5795 193.751 24.3613C193.751 22.1431 193.095 20.3011 191.771 18.9001C190.446 17.4343 188.774 16.7338 186.742 16.7338C184.709 16.7338 183.037 17.4343 181.661 18.9001C180.336 20.3011 179.68 22.1431 179.68 24.3613C179.68 26.5795 180.336 28.4215 181.661 29.8743C183.037 31.2753 184.761 31.9888 186.742 31.9888C188.722 31.9888 190.446 31.2883 191.771 29.8743ZM193.751 0H200.298V37.891H193.751V34.6999C191.822 37.3462 189.082 38.6563 185.52 38.6563C181.957 38.6563 179.179 37.3073 176.735 34.5442C174.343 31.7812 173.134 28.3696 173.134 24.3743C173.134 20.3789 174.356 17.0192 176.735 14.2561C179.166 11.4931 182.111 10.0922 185.52 10.0922C188.928 10.0922 191.822 11.3894 193.751 14.0356V0.0129719V0ZM223.308 29.7057C224.633 28.3047 225.289 26.5146 225.289 24.3483C225.289 22.182 224.633 20.4048 223.308 18.9909C221.983 17.5899 220.363 16.8765 218.382 16.8765C216.401 16.8765 214.78 17.577 213.456 18.9909C212.182 20.4567 211.526 22.2339 211.526 24.3483C211.526 26.4627 212.182 28.2399 213.456 29.7057C214.78 31.1067 216.401 31.8201 218.382 31.8201C220.363 31.8201 221.983 31.1196 223.308 29.7057ZM208.838 34.5183C206.253 31.7553 204.98 28.4085 204.98 24.3483C204.98 20.2881 206.253 16.9932 208.838 14.2302C211.424 11.4672 214.626 10.0662 218.382 10.0662C222.137 10.0662 225.34 11.4672 227.925 14.2302C230.511 16.9932 231.835 20.4048 231.835 24.3483C231.835 28.2918 230.511 31.7553 227.925 34.5183C225.34 37.2813 222.189 38.6304 218.382 38.6304C214.575 38.6304 211.424 37.2813 208.838 34.5183ZM260.17 21.261V37.878H253.623V22.1301C253.623 20.34 253.173 18.9909 252.247 17.9661C251.385 17.0451 250.164 16.5651 248.594 16.5651C244.89 16.5651 243.012 18.7833 243.012 23.2716V37.878H236.466V10.8316H243.012V13.867C244.581 11.3245 247.077 10.0792 250.575 10.0792C253.366 10.0792 255.656 11.0521 257.431 13.0498C259.257 15.0474 260.17 17.7586 260.17 21.274" fill="currentColor"></path>
							<defs>
							<linearGradient id="paint0_linear_89_11" x1="30.5" y1="0.0130005" x2="30.5" y2="65.013" gradientUnits="userSpaceOnUse">
							<stop stop-color="#6364FF"></stop>
							<stop offset="1" stop-color="#563ACC"></stop>
							</linearGradient>
							</defs>
						</symbol>
					</use>
				</svg>
				<ul>
					<li>████████</li>
					<li>███████████</li>
					<li>██████████</li>
					<li>█████████</li>
					<li>███████</li>
					<li>████████</li>
					<li>████████████</li>
					<li>████████████</li>
					<li>██████████</li>
					<li>████████████</li>
				</ul>
				<hr />
				<ul>
					<li>███████████</li>
					<li>██████████████</li>
					<li>█████████</li>
				</ul>
				<hr />
				<ul>
					<li>██████████</li>
				</ul>
			</aside>
			</div>
		</div>
		<div id="gancio" class="application-preview" style="display:none">
			<div class="text-center">
				<a href="/" id="gancio-title" class="nuxt-link-active">Gancio</a>
				<div class="text-body-1 font-weight-light pb-3">A shared agenda for local communities</div>
			</div>
			<div id="gancio-event" itemscope="itemscope">
				<div class="title text-center text-md-h4 text-h5 pa-6">
					<strong itemprop="name" class="p-name text--primary">
						<?php echo \esc_html( $object->get_name() ); ?>
					</strong>
				</div>
				<div class="row">
					<div class="col-12 col-md-8 pr-sm-2 pr-md-0 col">
						<?php if ( $first_image_attachment ) { ?>
						<div class="img">
							<img alt="<?php echo \esc_attr( isset( $first_image_attachment['name'] ) ? $first_image_attachment['name'] : '' ); ?>" loading="eager" src="<?php echo \esc_url( $first_image_attachment['url'] ); ?>" itemprop="image" height="826" width="826" class="u-featured" style="object-position:50% 50%;">
						</div>
						<?php } ?>
						<div itemprop="description" class="p-description text-body-1 pa-3 rounded">
							<?php echo wp_kses( $object->get_content(), ACTIVITYPUB_MASTODON_HTML_SANITIZER ); ?>
						</div>
					</div>
					<div class="col-12 col-md-4 col">
						<div class="v-card v-sheet v-sheet--outlined theme--dark">
							<div class="container eventDetails">
								<time datetime="2025-03-11 17:00" itemprop="startDate" content="2025-03-11T17:00" class="dt-start">
									<span aria-hidden="true" class="v-icon notranslate theme--dark" style="font-size:16px;height:16px;width:16px;">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="img" aria-hidden="true" class="v-icon__svg" style="font-size:16px;height:16px;width:16px;">
											<path d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z"></path>
										</svg>
									</span>
									<span class="ml-2 text-uppercase"><?php echo \esc_html( \wp_date( 'l, M j, h:i A-h:i A', strtotime( $object->get_start_time() ) ) ); ?></span>
									<div itemprop="endDate" content="2025-03-11T21:00" class="d-none dt-end">2025-03-11T21:00</div>
								</time><div class="font-weight-light mb-3">in 1 month<!----></div><div itemprop="location" itemscope="itemscope" itemtype="https://schema.org/Place" class="p-location h-adr"><span aria-hidden="true" class="v-icon notranslate theme--dark" style="font-size:16px;height:16px;width:16px;">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="img" aria-hidden="true" class="v-icon__svg" style="font-size:16px;height:16px;width:16px;">
										<path d="M12,11.5A2.5,2.5 0 0,1 9.5,9A2.5,2.5 0 0,1 12,6.5A2.5,2.5 0 0,1 14.5,9A2.5,2.5 0 0,1 12,11.5M12,2A7,7 0 0,0 5,9C5,14.25 12,22 12,22C12,22 19,14.25 19,9A7,7 0 0,0 12,2Z"></path>
									</svg>
								</span><a href="#" class="vcard ml-2 p-name text-decoration-none text-uppercase">
									<span itemprop="name"><?php echo \esc_html( $location_name ); ?></span>
								</a>
								<div itemprop="address" class="font-weight-light p-street-address"><?php echo \esc_html( $address ); ?></div>
							</div>
							<br>
							<a href="<?php echo esc_url( $object->get_url() ); ?>"><?php echo esc_url( $object->get_url() ); ?></a>
						</div>
						<div class="container pt-0">
							<?php foreach ( $object->get_tag() as $hashtag ) : ?>
								<?php if ( 'Hashtag' === $hashtag['type'] ) : ?>
									<a href="#" draggable="false" class="p-category ml-1 mt-1 v-chip v-chip--clickable v-chip--label v-chip--link v-chip--outlined theme--dark v-size--small primary primary--text">
										<span class="v-chip__content"><?php echo \esc_html( trim( $hashtag['name'], ' #' ) ); ?></span>
									</a>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
						<hr class="v-divider role="separator" aria-orientation="horizontal">
						<div role="list" class="v-list v-sheet theme--dark v-list--dense v-list--nav transparent">
							<div tabindex="0" role="listitem" class="v-list-item v-list-item--link theme--dark">
								<div class="v-list-item__icon">
									<span aria-hidden="true" class="v-icon notranslate theme--dark">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="img" aria-hidden="true" class="v-icon__svg">
										<path d="M19,21H8V7H19M19,5H8A2,2 0 0,0 6,7V21A2,2 0 0,0 8,23H19A2,2 0 0,0 21,21V7A2,2 0 0,0 19,5M16,1H4A2,2 0 0,0 2,3V17H4V3H16V1Z"></path>
										</svg>
									</span>
								</div>
								<div class="v-list-item__content">
									<div class="v-list-item__title">Copy link</div>
								</div>
							</div>
							<!---->
							<a tabindex="0" href="#" role="listitem" class="v-list-item v-list-item--link theme--dark">
								<div class="v-list-item__icon">
									<span aria-hidden="true" class="v-icon notranslate theme--dark">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="img" aria-hidden="true" class="v-icon__svg">
										<path d="M12 22L16 18H13V12H11V18H8L12 22M19 3H18V1H16V3H8V1H6V3H5C3.9 3 3 3.9 3 5V19C3 20.11 3.9 21 5 21H8L6 19H5V8H19V19H18L16 21H19C20.11 21 21 20.11 21 19V5C21 3.9 20.11 3 19 3Z"></path>
										</svg>
									</span>
								</div>
								<div class="v-list-item__content">
									<div class="v-list-item__title">Add to calendar</div>
								</div>
							</a>
							<!---->
							<a tabindex="0" href="#" role="listitem" class="v-list-item v-list-item--link theme--dark">
								<div class="v-list-item__icon">
									<span aria-hidden="true" class="v-icon notranslate theme--dark">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="img" aria-hidden="true" class="v-icon__svg">
										<path d="M14,2L20,8V20A2,2 0 0,1 18,22H6A2,2 0 0,1 4,20V4A2,2 0 0,1 6,2H14M18,20V9H13V4H6V20H18M12,19L8,15H10.5V12H13.5V15H16L12,19Z"></path>
										</svg>
									</span>
								</div>
								<div class="v-list-item__content">
									<div class="v-list-item__title">Download flyer</div>
								</div>
							</a>
							<div tabindex="0" role="listitem" class="v-list-item v-list-item--link theme--dark">
								<div class="v-list-item__icon">
									<span aria-hidden="true" class="v-icon notranslate theme--dark">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="img" aria-hidden="true" class="v-icon__svg">
										<path d="M14.6,16.6L19.2,12L14.6,7.4L16,6L22,12L16,18L14.6,16.6M9.4,16.6L4.8,12L9.4,7.4L8,6L2,12L8,18L9.4,16.6Z"></path>
										</svg>
									</span>
								</div>
								<div class="v-list-item__content">
									<div class="v-list-item__title">Embed</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		</div>
		<div id="mobilizon" class="application-preview px-2 py-4">
			<div style="padding-bottom: 70px;" class="container mx-auto pt-4">
			<div class="flex flex-col mb-3">
				<div class="flex justify-center max-h-80">
					<div class="flex-1">
						<div class="h-full w-full max-w-100 min-h-[10rem]">
						<img style="display: block" class="transition-opacity duration-500 rounded-lg object-cover mx-auto h-full opacity-100" alt="<?php echo \esc_attr( isset( $first_image_attachment['name'] ) ? $first_image_attachment['name'] : '' ); ?>" loading="eager" src="<?php echo \esc_url( $first_image_attachment['url'] ); ?>" loading="lazy">
						</div>
					</div>
				</div>
				<div class="flex flex-col relative pb-2 bg-white dark:bg-zinc-700 my-4 rounded">
				<div class="date-calendar-icon-wrapper relative">
					<div data-v-734fd47b="" class="datetime-container flex flex-col rounded-lg text-center justify-center overflow-hidden items-stretch bg-white dark:bg-gray-700 text-violet-3 dark:text-white absolute left-3 -top-16" style="--small: 2;">
						<div data-v-734fd47b="" class="datetime-container-header">
							<time data-v-734fd47b="" datetime="2025-02-01T18:00:00.000Z" class="weekday">Sat</time>
						</div>
						<div data-v-734fd47b="" class="datetime-container-content">
							<time data-v-734fd47b="" datetime="2025-02-01T18:00:00.000Z" class="day block font-semibold">1</time>
							<time data-v-734fd47b="" datetime="2025-02-01T18:00:00.000Z" class="month font-semibold block uppercase py-1 px-0">Feb</time>
						</div>
					</div>
				</div>
				<div class="start-time-icon-wrapper relative">
					<div data-v-16bfa768="" class="starttime-container flex flex-col rounded-lg text-center justify-center overflow-hidden items-stretch bg-white dark:bg-gray-700 text-violet-3 dark:text-white absolute right-3 -top-16" style="--small: 2;">
					<div data-v-16bfa768="" class="starttime-container-content font-semibold">
						<span data-v-16bfa768="" class="clock-icon material-design-icon clock-time-ten-outline-icon clock-icon" aria-hidden="true" role="img">
						<svg fill="currentColor" class="material-design-icon__svg" width="24" height="24" viewBox="0 0 24 24">
							<path d="M12 20C16.4 20 20 16.4 20 12S16.4 4 12 4 4 7.6 4 12 7.6 20 12 20M12 2C17.5 2 22 6.5 22 12S17.5 22 12 22C6.5 22 2 17.5 2 12C2 6.5 6.5 2 12 2M12.5 13H11L7 10.7L7.8 9.4L11.1 11.3V7H12.6V13Z">
							<!---->
							</path>
						</svg>
						</span>
						<time data-v-16bfa768="" datetime="<?php echo esc_html( $object->get_start_time() ); ?>"><?php echo esc_html( \wp_date( 'g:i A', strtotime( $object->get_start_time() ) ) ); ?></time>
					</div>
					</div>
				</div>
				<section class="intro px-2 pt-4" dir="auto">
					<div class="flex flex-wrap gap-2 justify-end">
					<div class="flex-1 min-w-[300px]">
						<h1 class="text-4xl font-bold m-0" dir="auto" lang="fr"><?php echo \esc_html( $object->get_name() ); ?></h1>
						<div class="organizer">
						<span>
							<div class="v-popper v-popper--theme-menu v-popper--theme-dropdown popover inline clickable"><?php \esc_html_e( 'By', 'event-bridge-for-activitypub' ); ?> <a href="/#" class="" dir="ltr"><?php echo \esc_html( $user->get_name() ); ?></a>
							</div>
						</span>
						</div>
						<div class="flex flex-wrap items-center gap-2 gap-y-4 mt-2 my-3">
						<!---->
						<p class="inline-flex gap-1">
							<span aria-hidden="true" class="material-design-icon earth-icon" role="img">
							<svg fill="currentColor" class="material-design-icon__svg" width="24" height="24" viewBox="0 0 24 24">
								<path d="M17.9,17.39C17.64,16.59 16.89,16 16,16H15V13A1,1 0 0,0 14,12H8V10H10A1,1 0 0,0 11,9V7H13A2,2 0 0,0 15,5V4.59C17.93,5.77 20,8.64 20,12C20,14.08 19.2,15.97 17.9,17.39M11,19.93C7.05,19.44 4,16.08 4,12C4,11.38 4.08,10.78 4.21,10.21L9,15V16A2,2 0 0,0 11,18M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z">
								<!---->
								</path>
							</svg>
							</span> <?php \esc_html_e( 'Public event', 'event-bridge-for-activitypub' ); ?>
						</p>
						<!---->
						<!---->
						<p class="flex flex-wrap gap-1 items-center" dir="auto">
						<?php foreach ( $object->get_tag() as $hashtag ) : ?>
								<?php if ( 'Hashtag' === $hashtag['type'] ) : ?>
									<a href="#">
										<span data-v-3b8a8223="" class="rounded-md truncate text-sm text-black px-2 py-1 bg-purple-3 dark:text-violet-3 category"><?php echo \esc_html( trim( $hashtag['name'], ' #' ) ); ?></span>
									</a>
								<?php endif; ?>
							<?php endforeach; ?>
							</a>
						</p>
						<!---->
						</div>
					</div>
					<div class="">
						<div>
						<div class="event-participation">
							<div class="ml-auto w-min">
							<a href="#" class="o-btn btn o-btn--large &quot;btn-size-large o-btn--primary btn-primary" role="button" data-oruga="button" rel="nofollow">
								<span class="o-btn__wrapper">
								<!---->
								<span class="o-btn__label"><?php \esc_html_e( 'Participate', 'event-bridge-for-activitypub' ); ?> </span>
								<!---->
								</span>
							</a>
							</div>
							<!---->
							<!---->
						</div>
						</div>
						<div class="flex flex-col gap-1 mt-1">
						<!---->
						<div data-oruga="dropdown" class="o-drop dropdown o-drop--position-bottom-left ml-auto">
							<div tabindex="0" class="o-drop__trigger" aria-haspopup="true">
							</div>
							<!---->
							<div class="o-drop__menu dropdown-menu o-drop__menu--bottom-left" role="list" aria-hidden="true" aria-modal="true" style="display: none;">
							<!---->
							<!---->
							<!---->
							<!---->
							<!---->
							<!---->
							<div class="o-drop__item dropdown-item o-drop__item--clickable p-1" role="listitem" tabindex="0" data-oruga="dropdown-item">
								<span class="flex gap-1">
								<span aria-hidden="true" class="material-design-icon share-icon" role="img">
									<svg fill="currentColor" class="material-design-icon__svg" width="24" height="24" viewBox="0 0 24 24">
									<path d="M21,12L14,5V9C7,10 4,15 3,20C5.5,16.5 9,14.9 14,14.9V19L21,12Z">
										<!---->
									</path>
									</svg>
								</span> Share this event </span>
							</div>
							<div class="o-drop__item dropdown-item o-drop__item--clickable" role="listitem" tabindex="0" data-oruga="dropdown-item">
								<span class="flex gap-1">
								<span aria-hidden="true" class="material-design-icon calendar-plus-icon" role="img">
									<svg fill="currentColor" class="material-design-icon__svg" width="24" height="24" viewBox="0 0 24 24">
									<path d="M19 19V8H5V19H19M16 1H18V3H19C20.11 3 21 3.9 21 5V19C21 20.11 20.11 21 19 21H5C3.89 21 3 20.1 3 19V5C3 3.89 3.89 3 5 3H6V1H8V3H16V1M11 9.5H13V12.5H16V14.5H13V17.5H11V14.5H8V12.5H11V9.5Z">
										<!---->
									</path>
									</svg>
								</span> Add to my calendar </span>
							</div>
							<!---->
							</div>
						</div>
						</div>
					</div>
					</div>
				</section>
				</div>
				<div class="rounded-lg dark:border-violet-title flex flex-wrap flex-col md:flex-row-reverse gap-4">
				<aside class="rounded bg-white dark:bg-zinc-700 shadow-md h-min max-w-screen-sm">
					<div style="padding-top: 0.25rem" class="sticky p-4">
					<div >
						<div  icon="map-marker">
						<h2 data-v-9bd0e2da=""><?php \esc_html_e( 'Location', 'event-bridge-for-activitypub' ); ?></h2>
						<div class="flex items-center mb-3 gap-1 eventMetadataBlock">
							<span  class="o-icon" data-oruga="icon">
							<span class="material-design-icon map-marker-icon" aria-hidden="true" role="img">
								<svg fill="currentColor" class="material-design-icon__svg" width="36" height="36" viewBox="0 0 24 24">
								<path d="M12,11.5A2.5,2.5 0 0,1 9.5,9A2.5,2.5 0 0,1 12,6.5A2.5,2.5 0 0,1 14.5,9A2.5,2.5 0 0,1 12,11.5M12,2A7,7 0 0,0 5,9C5,14.25 12,22 12,22C12,22 19,14.25 19,9A7,7 0 0,0 12,2Z">
								</path>
								</svg>
							</span>
							</span>
							<div class="content-wrapper overflow-hidden w-full">
							<div  class="address-wrapper">
								<div  class="address">
								<address   dir="auto">
									<p >
									<span  class="addressDescription" title="<?php echo \esc_html( $location->get_name() ); ?>"><?php echo \esc_html( $location->get_name() ); ?></span>
									<br >
									<span ><?php echo \esc_html( $address ); ?></span>
									<br >
									</p>
								</address>
								<button  type="button" class="o-btn btn o-btn--text btn-text map-show-button" role="button" data-oruga="button">
									<span class="o-btn__wrapper">
									<span class="o-btn__label">Show map</span>
									</span>
								</button>
								</div>
							</div>
							</div>
						</div>
						</div>
						<div>
						<h2 data-v-9bd0e2da=""><?php \esc_html_e( 'Date and time', 'event-bridge-for-activitypub' ); ?></h2>
						<div class="flex items-center mb-3 gap-1 eventMetadataBlock">
							<span  aria-hidden="true" class="material-design-icon calendar-icon" role="img">
							<svg fill="currentColor" class="material-design-icon__svg" width="36" height="36" viewBox="0 0 24 24">
								<path d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z">
								</path>
							</svg>
							</span>
							<div class="content-wrapper overflow-hidden w-full">
							<?php
							$start_timestamp = strtotime( $object->get_start_time() );
							$formatted_date  = sprintf(
								/* translators: %1$s: Day of the week, %2$s: Month and day, %3$s: Year, %4$s: Time */
								__( 'On %1$s, %2$s %3$s starting at %4$s', 'event-bridge-for-activitypub' ),
								\wp_date( 'l', $start_timestamp ),
								\wp_date( 'F j,', $start_timestamp ),
								\wp_date( 'Y', $start_timestamp ),
								\wp_date( 'g:i A', $start_timestamp )
							);
							?>
							<p><?php echo \esc_html( $formatted_date ); ?></p>
							</div>
						</div>
						</div>
						<div class="metadata-organized-by">
						<h2 data-v-9bd0e2da=""><?php \esc_html_e( 'Organized by', 'event-bridge-for-activitypub' ); ?></h2>
						<div class="flex items-center mb-3 gap-1 eventMetadataBlock">
							<div class="content-wrapper overflow-hidden w-full">
							<a  href="/@groupe_le_ptit_clem" class="hover:underline">
								<div data-v-ec6d6c8c=""  class="bg-white dark:bg-mbz-purple rounded-lg flex space-x-4 items-center">
								<div data-v-ec6d6c8c="" class="flex pl-2">
									<figure data-v-ec6d6c8c="" class="w-12 h-12">
									<img data-v-ec6d6c8c="" class="rounded-full object-cover h-full" src="<?php echo esc_url( object_to_uri( $user->get_icon() ) ); ?>" alt="" width="48" height="48" loading="lazy">
									</figure>
								</div>
								<div data-v-ec6d6c8c="" class="overflow-hidden w-full">
									<h5 data-v-ec6d6c8c="" class="text-xl font-medium violet-title tracking-tight text-gray-900 dark:text-gray-200 whitespace-pre-line line-clamp-2"><?php echo esc_html( $user->get_name() ); ?></h5>
									<p data-v-ec6d6c8c="" class="text-gray-500 dark:text-gray-200 truncate">
									<span data-v-ec6d6c8c="" dir="ltr"><?php echo '@' . \esc_html( \wp_parse_url( \get_home_url(), PHP_URL_HOST ) ) . '@' . \esc_html( $user->get_preferred_username() ); ?></span>
									</p>
								</div>
								</div>
							</a>
							</div>
						</div>
						</div>
						<div >
							<h2 data-v-9bd0e2da=""><?php \esc_html_e( 'Website', 'event-bridge-for-activitypub' ); ?></h2>
							<div class="flex items-center mb-3 gap-1 eventMetadataBlock">
								<span  aria-hidden="true" class="material-design-icon link-icon" role="img">
									<svg fill="currentColor" class="material-design-icon__svg" width="36" height="36" viewBox="0 0 24 24"><path d="M3.9,12C3.9,10.29 5.29,8.9 7,8.9H11V7H7A5,5 0 0,0 2,12A5,5 0 0,0 7,17H11V15.1H7C5.29,15.1 3.9,13.71 3.9,12M8,13H16V11H8V13M17,7H13V8.9H17C18.71,8.9 20.1,10.29 20.1,12C20.1,13.71 18.71,15.1 17,15.1H13V17H17A5,5 0 0,0 22,12A5,5 0 0,0 17,7Z"><!----></path></svg>
								</span>
								<div class="content-wrapper overflow-hidden w-full">
									<?php
									$mobilizon_link_note = sprintf(
									/* translators: %1$s: The external host */
										__( 'View page on %1$s (in a new window)', 'event-bridge-for-activitypub' ),
										\wp_parse_url( $object->get_url(), PHP_URL_HOST )
									);
									?>
									<a  target="_blank" class="underline" rel="noopener noreferrer ugc" href="<?php echo esc_url( $object->get_url() ); ?>" title="<?php echo esc_html( $mobilizon_link_note ); ?>"><?php echo esc_html( $object->get_url() ); ?></a>
								</div>
							</div>
						</div>
					</div>
					</div>
				</aside>
				<div class="flex-1">
					<section class="event-description bg-white dark:bg-zinc-700 px-3 pt-1 pb-3 rounded mb-4">
					<h2 class="text-2xl"><?php \esc_html_e( 'About this event', 'event-bridge-for-activitypub' ); ?></h2>
					<!---->
					<!---->
					<div>
						<div lang="fr" dir="auto" class="mt-4 prose md:prose-lg lg:prose-xl dark:prose-invert prose-h1:text-xl prose-h1:font-semibold prose-h2:text-lg prose-h3:text-base md:prose-h1:text-2xl md:prose-h1:font-semibold md:prose-h2:text-xl md:prose-h3:text-lg lg:prose-h1:text-2xl lg:prose-h1:font-semibold lg:prose-h2:text-xl lg:prose-h3:text-lg">
							<?php echo wp_kses( $object->get_content(), ACTIVITYPUB_MASTODON_HTML_SANITIZER ); ?>
						</div>
					</div>
					</section>
					<section class="my-4"></section>
					<section class="bg-white dark:bg-zinc-700 px-3 pt-1 pb-3 rounded my-4">
					<a href="#comments">
						<h2 class="text-2xl" id="comments"><?php \esc_html_e( 'Comments', 'event-bridge-for-activitypub' ); ?></h2>
					</a>
					<div data-v-0aad39c9="">
						<!---->
						<div data-v-0aad39c9="" class="mt-2">
						<div data-v-0aad39c9="" class="flex flex-col items-center mt-20 mb-10" role="note">
							<span class="o-icon" data-oruga="icon">
							<span class="material-design-icon comment-icon" aria-hidden="true" role="img">
								<svg fill="currentColor" class="material-design-icon__svg" width="48" height="48" viewBox="0 0 24 24">
								<path d="M9,22A1,1 0 0,1 8,21V18H4A2,2 0 0,1 2,16V4C2,2.89 2.9,2 4,2H20A2,2 0 0,1 22,4V16A2,2 0 0,1 20,18H13.9L10.2,21.71C10,21.9 9.75,22 9.5,22V22H9Z">
									<!---->
								</path>
								</svg>
							</span>
							</span>
							<h2 class="mb-3">
							<span data-v-0aad39c9=""><?php \esc_html_e( 'No comments yet', 'event-bridge-for-activitypub' ); ?></span>
							</h2>
							<p class="" style="display: none;"></p>
						</div>
						</div>
					</div>
					</section>
				</div>
				</div>
				<!---->
				<div close-button-aria-label="Close" class="map-modal o-modal modal" has-modal-card="" can-cancel="escape,outside" data-oruga="modal" tabindex="-1" aria-modal="false" style="display: none;">
				<div class="o-modal__overlay" tabindex="-1" aria-hidden="true"></div>
				<div class="o-modal__content modal-content o-modal__content--full-screen">
					<!---->
					<span class="o-icon o-icon--clickable o-icon--medium o-modal__close" data-oruga="icon" style="display: none;">
					<span class="material-design-icon close-icon" aria-hidden="true" role="img">
						<svg fill="currentColor" class="material-design-icon__svg" width="18" height="18" viewBox="0 0 24 24">
						<path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z">
							<!---->
						</path>
						</svg>
					</span>
					</span>
				</div>
				</div>
				</div>
			</div>
		</div>
	</body>
	<script>
		function openPreviewForApplication(applicationName) {
			var i;
			var x = document.getElementsByClassName("application-preview");
			for (i = 0; i < x.length; i++) {
				x[i].style.display = "none";
			}
			document.getElementById(applicationName).style.display = "block";
		}
	</script>
</html>
