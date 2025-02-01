<?php
/**
 * ActivityPub Post JSON template.
 *
 * @package Activitypub
 */

use Event_Bridge_For_ActivityPub\ActivityPub\Transformer\Event;

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

?>
<DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title><?php echo esc_html( $object->get_name() ); ?>HUU</title>
		<style>
			html,body { min-height:100%; }
			body {
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
				font-size: 1em;
				line-height: 1.5;
				margin: 0;
				padding: 0;
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
			.preview-navigation {
				display: flex;
				justify-content: center;
				background: #0d1117;
				padding: 10px;
			}
			.preview-navigation > button {
				margin: 5px;
				cursor: pointer;
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
		<div class="preview-navigation">
			<button onclick="openPreviewForApplication('mastodon')">
				Mastodon
			</button>
			<button onclick="openPreviewForApplication('gancio')">Gancio</button>
			<button onclick="openPreviewForApplication('mobilizon')">Mobilizon</button>
		</div>
		<div id="mastodon" class="application-preview" style="display:none">
			<div class="columns">
			<aside class="sidebar">
				<input type="search" disabled="disabled" placeholder="<?php esc_html_e( 'Search', 'event-bridge-for-activitypub' ); ?>" />
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
				<textarea rows="10" cols="50" disabled="disabled" placeholder="<?php esc_html_e( 'What\'s up', 'event-bridge-for-activitypub' ); ?>"></textarea>
			</aside>
			<main>
				<h1 class="column-header">
					Home
				</h1>
				<article>
					<address>
						<img src="<?php echo esc_url( $user->get_icon()['url'] ); ?>" alt="<?php echo esc_attr( $user->get_name() ); ?>" />
						<div>
							<div class="name">
								<?php echo esc_html( $user->get_name() ); ?>
							</div>
							<div class="webfinger">
								<?php echo esc_html( '@' . $user->get_webfinger() ); ?>
							</div>
						</div>
					</address>
					<div class="content">
						<h2><?php echo esc_html( $object->get_name() ); ?></h2>
						<?php echo wp_kses( 'Event' === $object->get_type() ? $object->get_summary() : $object->get_summary(), ACTIVITYPUB_MASTODON_HTML_SANITIZER ); ?>
						<a href="<?php echo esc_html( $object->get_id() ); ?>"><?php echo esc_html( $object->get_url() ); ?></h2>
					</div>
					<div class="attachments">
						<?php foreach ( $object->get_attachment() as $attachment ) : ?>
							<?php if ( 'Image' === $attachment['type'] ) : ?>
								<img src="<?php echo esc_url( $attachment['url'] ); ?>" alt="<?php echo esc_attr( $attachment['name'] ?? '' ); ?>" />
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
					<div class="tags">
						<?php foreach ( $object->get_tag() as $hashtag ) : ?>
							<?php if ( 'Hashtag' === $hashtag['type'] ) : ?>
								<a href="<?php echo esc_url( $hashtag['href'] ); ?>"><?php echo esc_html( $hashtag['name'] ); ?></a>
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
						<?php echo esc_html( $object->get_name() ); ?>
					</strong>
				</div>
				<div class="row">
					<div class="col-12 col-md-8 pr-sm-2 pr-md-0 col">
						<?php if ( $attachment ) { ?>
						<div class="img">
							<img alt="<?php echo esc_attr( $attachment['name'] ) ?? ''; ?>" loading="eager" src="<?php echo esc_url( $attachment['url'] ); ?>" itemprop="image" height="826" width="826" class="u-featured" style="object-position:50% 50%;">
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
									<span class="ml-2 text-uppercase"><?php echo esc_html( \wp_date( 'l, M j, h:i A-h:i A', strtotime( $object->get_start_time() ) ) ); ?></span>
									<div itemprop="endDate" content="2025-03-11T21:00" class="d-none dt-end">2025-03-11T21:00</div>
								</time><div class="font-weight-light mb-3">in 1 month<!----></div><div itemprop="location" itemscope="itemscope" itemtype="https://schema.org/Place" class="p-location h-adr"><span aria-hidden="true" class="v-icon notranslate theme--dark" style="font-size:16px;height:16px;width:16px;">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="img" aria-hidden="true" class="v-icon__svg" style="font-size:16px;height:16px;width:16px;">
										<path d="M12,11.5A2.5,2.5 0 0,1 9.5,9A2.5,2.5 0 0,1 12,6.5A2.5,2.5 0 0,1 14.5,9A2.5,2.5 0 0,1 12,11.5M12,2A7,7 0 0,0 5,9C5,14.25 12,22 12,22C12,22 19,14.25 19,9A7,7 0 0,0 12,2Z"></path>
									</svg>
								</span><a href="/place/Mariahilferplatz%20Graz" class="vcard ml-2 p-name text-decoration-none text-uppercase">
									<span itemprop="name"><?php echo esc_html( $location_name ); ?></span>
								</a>
								<div itemprop="address" class="font-weight-light p-street-address"><?php echo esc_html( $address ); ?></div>
							</div>
						</div>
						<div class="container pt-0">
							<a href="/tag/Workout" draggable="false" class="p-category ml-1 mt-1 v-chip v-chip--clickable v-chip--label v-chip--link v-chip--outlined theme--dark v-size--small primary primary--text">
								<span class="v-chip__content">Workout</span>
							</a>
							<a href="/tag/Sports" draggable="false" class="p-category ml-1 mt-1 v-chip v-chip--clickable v-chip--label v-chip--link v-chip--outlined theme--dark v-size--small primary primary--text">
								<span class="v-chip__content">Sports</span>
							</a>
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
			<div class="container mx-auto">
			<div class="flex flex-col mb-3">
				<div class="flex justify-center max-h-80">
					<div class="flex-1">
						<div class="h-full w-full max-w-100 min-h-[10rem]">
						<img style="display: block" class="transition-opacity duration-500 rounded-lg object-cover mx-auto h-full opacity-100" alt="" src="https://lekalepin.fr/media/379410f9603a6fff8c165451c7fda37580f3a5c19c0a3690ebd5401bdecabeff.jpg?name=DUE%20DI%20COPPE.jpg" loading="lazy">
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
						<time data-v-16bfa768="" datetime="2025-02-07T19:00:00.000Z">8:00 PM</time>
					</div>
					</div>
				</div>
				<section class="intro px-2 pt-4" dir="auto">
					<div class="flex flex-wrap gap-2 justify-end">
					<div class="flex-1 min-w-[300px]">
						<h1 class="text-4xl font-bold m-0" dir="auto" lang="fr">Concert Due Di Coppe</h1>
						<div class="organizer">
						<span>
							<div class="v-popper v-popper--theme-menu v-popper--theme-dropdown popover inline clickable">By <a href="/@groupe_le_ptit_clem" class="" dir="ltr">Le P'tit Clem</a>
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
							</span> Public event
						</p>
						<!---->
						<!---->
						<p class="flex flex-wrap gap-1 items-center" dir="auto">
							<span data-v-3b8a8223="" class="rounded-md truncate text-sm text-black px-2 py-1 bg-purple-3 dark:text-violet-3 category">Music</span>
							<a href="/tag/concert" class="rounded-md truncate text-sm text-violet-title py-1 bg-purple-3 dark:text-violet-3 category">
							<span data-v-3b8a8223="" class="rounded-md truncate text-sm text-black px-2 py-1 bg-purple-3 dark:text-violet-3">concert</span>
							</a>
						</p>
						<!---->
						</div>
					</div>
					<div class="">
						<div>
						<div class="event-participation">
							<div class="ml-auto w-min">
							<a href="/events/ec075ac8-7ba9-4dcb-ba9a-4cb7dde1fcb2/participate/with-account" class="o-btn btn o-btn--large &quot;btn-size-large o-btn--primary btn-primary" role="button" data-oruga="button" rel="nofollow">
								<span class="o-btn__wrapper">
								<!---->
								<span class="o-btn__label">Participate</span>
								<!---->
								</span>
							</a>
							</div>
							<!---->
							<!---->
						</div>
						<div has-modal-card="" close-button-aria-label="Close" data-oruga="modal" class="o-modal modal" tabindex="-1" aria-modal="false" style="display: none;">
							<div class="o-modal__overlay" tabindex="-1" aria-hidden="true"></div>
							<div class="o-modal__content modal-content" style="max-width: 960px;">
							<div class="modal-card">
								<header class="modal-card-head">
								<p class="modal-card-title">About anonymous participation</p>
								</header>
								<section class="modal-card-body">
								<!---->
								<p>Your participation status is saved only on this device and will be deleted one month after the event's passed.</p>
								<p>You may clear all participation information for this device with the buttons below.</p>
								<div class="buttons">
									<button type="button" class="o-btn btn o-btn--danger btn-danger o-btn--outlined-danger btn-outlined-danger" role="button" data-oruga="button">
									<span class="o-btn__wrapper">
										<!---->
										<span class="o-btn__label">Clear participation data for this event</span>
										<!---->
									</span>
									</button>
									<button type="button" class="o-btn btn o-btn--danger btn-danger" role="button" data-oruga="button">
									<span class="o-btn__wrapper">
										<!---->
										<span class="o-btn__label">Clear participation data for all events</span>
										<!---->
									</span>
									</button>
								</div>
								</section>
							</div>
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
					<div has-modal-card="" close-button-aria-label="Close" data-oruga="modal" class="o-modal modal" tabindex="-1" aria-modal="false" style="display: none;">
						<div class="o-modal__overlay" tabindex="-1" aria-hidden="true"></div>
						<div class="o-modal__content modal-content" style="max-width: 960px;">
						<div data-v-64bca4d3="" class="p-2">
							<header data-v-64bca4d3="" class="mb-3">
							<h2 data-v-64bca4d3="" class="text-2xl">Report this event</h2>
							</header>
							<section data-v-64bca4d3="">
							<div data-v-64bca4d3="" class="flex gap-1 flex-row mb-3 bg-mbz-yellow dark:text-black p-3 rounded items-center">
								<span data-v-64bca4d3="" class="o-icon o-icon--warning icon-warning hidden md:block flex-1" data-oruga="icon">
								<span class="material-design-icon alert-icon" aria-hidden="true" role="img">
									<svg fill="currentColor" class="material-design-icon__svg" width="48" height="48" viewBox="0 0 24 24">
									<path d="M13 14H11V9H13M13 18H11V16H13M1 21H23L12 2L1 21Z">
										<!---->
									</path>
									</svg>
								</span>
								</span>
								<p data-v-64bca4d3="">The report will be sent to the moderators of your instance. You can explain why you report this content below.</p>
							</div>
							<div data-v-64bca4d3="">
								<!---->
								<div data-v-64bca4d3="" data-oruga="field" class="o-field field">
								<label for="additional-comments" class="o-field__label field-label">Additional comments</label>
								<div class="o-field__body">
									<div class="o-field field o-field--addons">
									<div data-v-64bca4d3="" data-oruga="input" class="o-input__wrapper o-input__wrapper--expanded">
										<textarea autofocus="" id="additional-comments" data-oruga-input="textarea" class="o-input input o-input__textarea"></textarea>
										<!---->
										<!---->
										<!---->
									</div>
									</div>
								</div>
								<!---->
								</div>
								<!---->
							</div>
							</section>
							<footer data-v-64bca4d3="" class="flex gap-2 py-3">
							<button data-v-64bca4d3="" type="button" class="o-btn btn o-btn--outlined btn-outlined-null" role="button" data-oruga="button">
								<span class="o-btn__wrapper">
								<!---->
								<span class="o-btn__label">Cancel</span>
								<!---->
								</span>
							</button>
							<button data-v-64bca4d3="" type="button" class="o-btn btn o-btn--primary btn-primary" role="button" data-oruga="button">
								<span class="o-btn__wrapper">
								<!---->
								<span class="o-btn__label">Send the report</span>
								<!---->
								</span>
							</button>
							</footer>
						</div>
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
					<div close-button-aria-label="Close" has-modal-card="" data-oruga="modal" class="o-modal modal" tabindex="-1" aria-modal="false" style="display: none;">
						<div class="o-modal__overlay" tabindex="-1" aria-hidden="true"></div>
						<div class="o-modal__content modal-content" style="max-width: 960px;">
						<div class="dark:text-white">
							<div data-v-dfd20edc="" class="dark:text-white p-4">
							<header data-v-dfd20edc="" class="">
								<h2 data-v-dfd20edc="" class="text-2xl">Share this event</h2>
							</header>
							<section data-v-dfd20edc="" class="flex">
								<div data-v-dfd20edc="" class="w-full">
								<div data-v-dfd20edc="" data-oruga="field" class="o-field field o-field--filled">
									<label for="url-text" class="o-field__label field-label">Event URL</label>
									<div class="o-field__body">
									<div class="o-field field o-field--addons">
										<div data-v-dfd20edc="" data-oruga="input" class="o-input__wrapper o-input__wrapper--expanded">
										<input id="url-text" data-oruga-input="text" type="text" class="o-input input" autocomplete="off">
										<!---->
										<!---->
										<!---->
										</div>
										<p data-v-dfd20edc="" class="control">
										<div data-v-dfd20edc="" class="o-tip tooltip" data-oruga="tooltip">
										<div class="o-tip__content tooltip-content o-tip__content--left o-tip__content--success tooltip-content-success" style="display: none;">
											<span class="o-tip__arrow tooltip-arrow o-tip__arrow--left o-tip__arrow--success"></span>URL copied to clipboard
										</div>
										<div class="o-tip__trigger" aria-haspopup="true"></div>
										</div>
										<button data-v-dfd20edc="" type="button" class="o-btn btn o-btn--primary btn-primary" role="button" data-oruga="button" title="Copy URL to clipboard">
										<span class="o-btn__wrapper">
											<!---->
											<!---->
											<span class="o-icon o-btn__icon o-btn__icon-right" data-oruga="icon">
											<span class="material-design-icon content-paste-icon" aria-hidden="true" role="img">
												<svg fill="currentColor" class="material-design-icon__svg" width="18" height="18" viewBox="0 0 24 24">
												<path d="M19,20H5V4H7V7H17V4H19M12,2A1,1 0 0,1 13,3A1,1 0 0,1 12,4A1,1 0 0,1 11,3A1,1 0 0,1 12,2M19,2H14.82C14.4,0.84 13.3,0 12,0C10.7,0 9.6,0.84 9.18,2H5A2,2 0 0,0 3,4V20A2,2 0 0,0 5,22H19A2,2 0 0,0 21,20V4A2,2 0 0,0 19,2Z">
													<!---->
												</path>
												</svg>
											</span>
											</span>
										</span>
										</button>
										</p>
									</div>
									</div>
									<!---->
								</div>
								<div data-v-dfd20edc="" class="flex flex-wrap gap-1">
									<a data-v-dfd20edc="" href="https://twitter.com/intent/tweet?url=https%3A%2F%2Flekalepin.fr%2Fevents%2Fec075ac8-7ba9-4dcb-ba9a-4cb7dde1fcb2&amp;text=Concert Due Di Coppe" target="_blank" rel="nofollow noopener" title="Twitter">
									<span data-v-dfd20edc="" class="dark:text-white material-design-icon twitter-icon dark:text-white" aria-hidden="true" role="img">
										<svg fill="currentColor" class="material-design-icon__svg" width="48" height="48" viewBox="0 0 24 24">
										<path d="M22.46,6C21.69,6.35 20.86,6.58 20,6.69C20.88,6.16 21.56,5.32 21.88,4.31C21.05,4.81 20.13,5.16 19.16,5.36C18.37,4.5 17.26,4 16,4C13.65,4 11.73,5.92 11.73,8.29C11.73,8.63 11.77,8.96 11.84,9.27C8.28,9.09 5.11,7.38 3,4.79C2.63,5.42 2.42,6.16 2.42,6.94C2.42,8.43 3.17,9.75 4.33,10.5C3.62,10.5 2.96,10.3 2.38,10C2.38,10 2.38,10 2.38,10.03C2.38,12.11 3.86,13.85 5.82,14.24C5.46,14.34 5.08,14.39 4.69,14.39C4.42,14.39 4.15,14.36 3.89,14.31C4.43,16 6,17.26 7.89,17.29C6.43,18.45 4.58,19.13 2.56,19.13C2.22,19.13 1.88,19.11 1.54,19.07C3.44,20.29 5.7,21 8.12,21C16,21 20.33,14.46 20.33,8.79C20.33,8.6 20.33,8.42 20.32,8.23C21.16,7.63 21.88,6.87 22.46,6Z">
											<!---->
										</path>
										</svg>
									</span>
									</a>
									<a data-v-dfd20edc="" href="https://toot.kytta.dev/?text=Concert%20Due%20Di%20Coppe%0D%0Ahttps%3A%2F%2Flekalepin.fr%2Fevents%2Fec075ac8-7ba9-4dcb-ba9a-4cb7dde1fcb2" class="mastodon" target="_blank" rel="nofollow noopener" title="Mastodon">
									<span data-v-dfd20edc="" class="text-primary dark:text-white">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 216.4144 232.00976">
										<title>Mastodon logo</title>
										<path d="M211.80734 139.0875c-3.18125 16.36625-28.4925 34.2775-57.5625 37.74875-15.15875 1.80875-30.08375 3.47125-45.99875 2.74125-26.0275-1.1925-46.565-6.2125-46.565-6.2125 0 2.53375.15625 4.94625.46875 7.2025 3.38375 25.68625 25.47 27.225 46.39125 27.9425 21.11625.7225 39.91875-5.20625 39.91875-5.20625l.8675 19.09s-14.77 7.93125-41.08125 9.39c-14.50875.7975-32.52375-.365-53.50625-5.91875C9.23234 213.82 1.40609 165.31125.20859 116.09125c-.365-14.61375-.14-28.39375-.14-39.91875 0-50.33 32.97625-65.0825 32.97625-65.0825C49.67234 3.45375 78.20359.2425 107.86484 0h.72875c29.66125.2425 58.21125 3.45375 74.8375 11.09 0 0 32.975 14.7525 32.975 65.0825 0 0 .41375 37.13375-4.59875 62.915"></path>
										<path d="M177.50984 80.077v60.94125h-24.14375v-59.15c0-12.46875-5.24625-18.7975-15.74-18.7975-11.6025 0-17.4175 7.5075-17.4175 22.3525v32.37625H96.20734V85.42325c0-14.845-5.81625-22.3525-17.41875-22.3525-10.49375 0-15.74 6.32875-15.74 18.7975v59.15H38.90484V80.077c0-12.455 3.17125-22.3525 9.54125-29.675 6.56875-7.3225 15.17125-11.07625 25.85-11.07625 12.355 0 21.71125 4.74875 27.8975 14.2475l6.01375 10.08125 6.015-10.08125c6.185-9.49875 15.54125-14.2475 27.8975-14.2475 10.6775 0 19.28 3.75375 25.85 11.07625 6.36875 7.3225 9.54 17.22 9.54 29.675" fill="#fff"></path>
										</svg>
									</span>
									</a>
									<a data-v-dfd20edc="" href="https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Flekalepin.fr%2Fevents%2Fec075ac8-7ba9-4dcb-ba9a-4cb7dde1fcb2" target="_blank" rel="nofollow noopener" title="Facebook">
									<span data-v-dfd20edc="" class="dark:text-white material-design-icon facebook-icon dark:text-white" aria-hidden="true" role="img">
										<svg fill="currentColor" class="material-design-icon__svg" width="48" height="48" viewBox="0 0 24 24">
										<path d="M12 2.04C6.5 2.04 2 6.53 2 12.06C2 17.06 5.66 21.21 10.44 21.96V14.96H7.9V12.06H10.44V9.85C10.44 7.34 11.93 5.96 14.22 5.96C15.31 5.96 16.45 6.15 16.45 6.15V8.62H15.19C13.95 8.62 13.56 9.39 13.56 10.18V12.06H16.34L15.89 14.96H13.56V21.96A10 10 0 0 0 22 12.06C22 6.53 17.5 2.04 12 2.04Z">
											<!---->
										</path>
										</svg>
									</span>
									</a>
									<a data-v-dfd20edc="" href="https://wa.me/?text=Concert%20Due%20Di%20Coppe%0D%0Ahttps%3A%2F%2Flekalepin.fr%2Fevents%2Fec075ac8-7ba9-4dcb-ba9a-4cb7dde1fcb2" target="_blank" rel="nofollow noopener" title="WhatsApp">
									<span data-v-dfd20edc="" class="dark:text-white material-design-icon whatsapp-icon dark:text-white" aria-hidden="true" role="img">
										<svg fill="currentColor" class="material-design-icon__svg" width="48" height="48" viewBox="0 0 24 24">
										<path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.03 14.69 2 12.04 2M12.05 3.67C14.25 3.67 16.31 4.53 17.87 6.09C19.42 7.65 20.28 9.72 20.28 11.92C20.28 16.46 16.58 20.15 12.04 20.15C10.56 20.15 9.11 19.76 7.85 19L7.55 18.83L4.43 19.65L5.26 16.61L5.06 16.29C4.24 15 3.8 13.47 3.8 11.91C3.81 7.37 7.5 3.67 12.05 3.67M8.53 7.33C8.37 7.33 8.1 7.39 7.87 7.64C7.65 7.89 7 8.5 7 9.71C7 10.93 7.89 12.1 8 12.27C8.14 12.44 9.76 14.94 12.25 16C12.84 16.27 13.3 16.42 13.66 16.53C14.25 16.72 14.79 16.69 15.22 16.63C15.7 16.56 16.68 16.03 16.89 15.45C17.1 14.87 17.1 14.38 17.04 14.27C16.97 14.17 16.81 14.11 16.56 14C16.31 13.86 15.09 13.26 14.87 13.18C14.64 13.1 14.5 13.06 14.31 13.3C14.15 13.55 13.67 14.11 13.53 14.27C13.38 14.44 13.24 14.46 13 14.34C12.74 14.21 11.94 13.95 11 13.11C10.26 12.45 9.77 11.64 9.62 11.39C9.5 11.15 9.61 11 9.73 10.89C9.84 10.78 10 10.6 10.1 10.45C10.23 10.31 10.27 10.2 10.35 10.04C10.43 9.87 10.39 9.73 10.33 9.61C10.27 9.5 9.77 8.26 9.56 7.77C9.36 7.29 9.16 7.35 9 7.34C8.86 7.34 8.7 7.33 8.53 7.33Z">
											<!---->
										</path>
										</svg>
									</span>
									</a>
									<a data-v-dfd20edc="" href="https://t.me/share/url?url=https%3A%2F%2Flekalepin.fr%2Fevents%2Fec075ac8-7ba9-4dcb-ba9a-4cb7dde1fcb2&amp;text=Concert%20Due%20Di%20Coppe" class="telegram" target="_blank" rel="nofollow noopener" title="Telegram">
									<span data-v-dfd20edc="" class="text-primary dark:text-white dark:fill-white">
										<svg role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
										<title>Telegram</title>
										<path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"></path>
										</svg>
									</span>
									</a>
									<a data-v-dfd20edc="" href="https://www.linkedin.com/shareArticle?mini=true&amp;url=https%3A%2F%2Flekalepin.fr%2Fevents%2Fec075ac8-7ba9-4dcb-ba9a-4cb7dde1fcb2&amp;title=Concert Due Di Coppe" target="_blank" rel="nofollow noopener" title="LinkedIn">
									<span data-v-dfd20edc="" class="dark:text-white material-design-icon linkedin-icon dark:text-white" aria-hidden="true" role="img">
										<svg fill="currentColor" class="material-design-icon__svg" width="48" height="48" viewBox="0 0 24 24">
										<path d="M19 3A2 2 0 0 1 21 5V19A2 2 0 0 1 19 21H5A2 2 0 0 1 3 19V5A2 2 0 0 1 5 3H19M18.5 18.5V13.2A3.26 3.26 0 0 0 15.24 9.94C14.39 9.94 13.4 10.46 12.92 11.24V10.13H10.13V18.5H12.92V13.57C12.92 12.8 13.54 12.17 14.31 12.17A1.4 1.4 0 0 1 15.71 13.57V18.5H18.5M6.88 8.56A1.68 1.68 0 0 0 8.56 6.88C8.56 5.95 7.81 5.19 6.88 5.19A1.69 1.69 0 0 0 5.19 6.88C5.19 7.81 5.95 8.56 6.88 8.56M8.27 18.5V10.13H5.5V18.5H8.27Z">
											<!---->
										</path>
										</svg>
									</span>
									</a>
									<a data-v-dfd20edc="" href="https://share.diasporafoundation.org/?title=Concert%20Due%20Di%20Coppe&amp;url=https%3A%2F%2Flekalepin.fr%2Fevents%2Fec075ac8-7ba9-4dcb-ba9a-4cb7dde1fcb2" class="diaspora" target="_blank" rel="nofollow noopener" title="Diaspora">
									<span data-v-dfd20edc="" class="text-black dark:text-white dark:fill-white">
										<svg version="1.1" viewBox="0 0 65.131 65.131" xmlns="http://www.w3.org/2000/svg">
										<title>Diaspora logo</title>
										<path d="m28.214 64.754c-6.9441-0.80647-14.478-4.7044-19.429-10.053-8.1024-8.7516-10.823-21.337-7.0178-32.463 3.8465-11.248 12.917-19.153 24.746-21.569 7.2561-1.4817 14.813-0.27619 21.622 3.4495 7.517 4.1126 12.568 10.251 15.291 18.582 5.5678 17.038-4.1941 35.667-21.417 40.87-4.6929 1.4178-8.7675 1.7673-13.795 1.1834zm0.43913-17.263c2.0058-2.7986 3.7663-5.0883 3.9123-5.0883 0.14591 0 1.9109 2.2959 3.9221 5.102 2.0112 2.8061 3.827 5.0577 4.0349 5.0035 0.90081-0.23467 8.2871-5.9034 8.1633-6.265-0.07527-0.21984-1.7555-2.6427-3.7338-5.3842-1.9783-2.7414-3.552-5.0223-3.497-5.0686 0.05497-0.04629 2.8095-0.97845 6.1211-2.0715 3.3117-1.093 6.0224-2.1432 6.0239-2.3338 0.0073-0.92502-2.9094-9.4312-3.283-9.5746-0.23567-0.09043-2.9906 0.68953-6.1221 1.7332-3.1315 1.0437-5.8046 1.8977-5.9404 1.8977-0.13575 0-0.28828-2.9385-0.33895-6.53l-0.09213-6.53h-10.516l-0.09213 6.53c-0.05067 3.5915-0.20809 6.53-0.34982 6.53s-2.9544-0.90204-6.2504-2.0045l-5.9927-2.0045-1.5444 4.6339c-0.8494 2.5487-1.5444 4.866-1.5444 5.1496 0 0.36743 1.7311 1.087 6.0212 2.503 3.3117 1.093 6.0662 2.0252 6.1211 2.0715 0.05497 0.04629-1.5187 2.3272-3.497 5.0686-1.9783 2.7415-3.6605 5.1643-3.7382 5.3842-0.14163 0.40073 7.4833 6.2827 8.1896 6.3175 0.20673 0.01021 2.017-2.2712 4.0228-5.0698z" stroke-width=".33922"></path>
										<path d="m23.631 51.953c-2.348-1.5418-6.9154-5.1737-7.0535-5.6088-0.06717-0.21164 0.45125-0.99318 3.3654-5.0734 2.269-3.177 3.7767-5.3581 3.7767-5.4637 0-0.03748-1.6061-0.60338-3.5691-1.2576-6.1342-2.0442-8.3916-2.9087-8.5288-3.2663-0.03264-0.08506 0.09511-0.68598 0.28388-1.3354 0.643-2.212 2.7038-8.4123 2.7959-8.4123 0.05052 0 2.6821 0.85982 5.848 1.9107 3.1659 1.0509 5.897 1.9222 6.0692 1.9362 0.3089 0.02514 0.31402 0.01925 0.38295-0.44107 0.09851-0.65784 0.26289-5.0029 0.2633-6.9599 1.87e-4 -0.90267 0.02801-2.5298 0.06184-3.6158l0.0615-1.9746h10.392l0.06492 4.4556c0.06287 4.3148 0.18835 7.8236 0.29865 8.3513 0.0295 0.14113 0.11236 0.2566 0.18412 0.2566 0.07176 0 1.6955-0.50861 3.6084-1.1303 4.5213-1.4693 6.2537-2.0038 7.3969-2.2822 0.87349-0.21269 0.94061-0.21704 1.0505-0.06806 0.45169 0.61222 3.3677 9.2365 3.1792 9.4025-0.33681 0.29628-2.492 1.1048-6.9823 2.6194-5.3005 1.7879-5.1321 1.7279-5.1321 1.8283 0 0.13754 0.95042 1.522 3.5468 5.1666 1.3162 1.8475 2.6802 3.7905 3.0311 4.3176l0.63804 0.95842-0.27216 0.28519c-1.1112 1.1644-7.3886 5.8693-7.8309 5.8693-0.22379 0-1.2647-1.2321-2.9284-3.4663-0.90374-1.2137-2.264-3.0402-3.0228-4.059-0.75878-1.0188-1.529-2.0203-1.7116-2.2256l-0.33201-0.37324-0.32674 0.37324c-0.43918 0.50169-2.226 2.867-3.8064 5.0388-2.1662 2.9767-3.6326 4.8055-3.8532 4.8055-0.05161 0-0.4788-0.25278-0.94931-0.56173z" fill="transparent" stroke-width=".093311"></path>
										</svg>
									</span>
									</a>
									<a data-v-dfd20edc="" href="mailto:?to=&amp;body=https://lekalepin.fr/events/ec075ac8-7ba9-4dcb-ba9a-4cb7dde1fcb2&amp;subject=Concert Due Di Coppe" target="_blank" rel="nofollow noopener" title="Email">
									<span data-v-dfd20edc="" class="dark:text-white material-design-icon email-icon dark:text-white" aria-hidden="true" role="img">
										<svg fill="currentColor" class="material-design-icon__svg" width="48" height="48" viewBox="0 0 24 24">
										<path d="M20,8L12,13L4,8V6L12,11L20,6M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z">
											<!---->
										</path>
										</svg>
									</span>
									</a>
								</div>
								</div>
							</section>
							</div>
						</div>
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
					<div has-modal-card="" close-button-aria-label="Close" data-oruga="modal" class="o-modal modal" tabindex="-1" aria-modal="false" style="display: none;">
						<div class="o-modal__overlay" tabindex="-1" aria-hidden="true"></div>
						<div class="o-modal__content modal-content" style="max-width: 960px;">
						<div>
							<header class="">
							<h2 class="">Pick an identity</h2>
							</header>
							<section class="">
							<ul class="grid grid-cols-1 gap-y-3 m-5 max-w-md"></ul>
							</section>
							<footer class="flex gap-2">
							<button type="button" class="o-btn btn o-btn--outlined btn-outlined-null" role="button" data-oruga="button">
								<span class="o-btn__wrapper">
								<!---->
								<span class="o-btn__label">Cancel</span>
								<!---->
								</span>
							</button>
							<button type="button" class="o-btn btn o-btn--primary btn-primary" role="button" data-oruga="button">
								<span class="o-btn__wrapper">
								<!---->
								<span class="o-btn__label">Confirm my particpation</span>
								<!---->
								</span>
							</button>
							</footer>
						</div>
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
					<div has-modal-card="" close-button-aria-label="Close" data-oruga="modal" class="o-modal modal" tabindex="-1" aria-modal="false" style="display: none;">
						<div class="o-modal__overlay" tabindex="-1" aria-hidden="true"></div>
						<div class="o-modal__content modal-content" style="max-width: 960px;">
						<div class="modal-card">
							<header class="modal-card-head">
							<p class="modal-card-title">Participation confirmation</p>
							</header>
							<section class="modal-card-body">
							<p>The event organiser has chosen to validate manually participations. Do you want to add a little note to explain why you want to participate to this event?</p>
							<form>
								<div data-oruga="field" class="o-field field">
								<label class="o-field__label field-label" for="7743wmcw10e">Message</label>
								<div class="o-field__body">
									<div class="o-field field o-field--addons">
									<div data-oruga="input" class="o-input__wrapper">
										<textarea minlength="10" id="7743wmcw10e" data-oruga-input="textarea" class="o-input input o-input--medium input-size-medium o-input__textarea"></textarea>
										<!---->
										<!---->
										<!---->
									</div>
									</div>
								</div>
								<!---->
								</div>
								<div class="buttons">
								<button type="button" class="o-btn btn button" role="button" data-oruga="button">
									<span class="o-btn__wrapper">
									<!---->
									<span class="o-btn__label">Cancel</span>
									<!---->
									</span>
								</button>
								<button type="submit" class="o-btn btn o-btn--primary btn-primary" role="button" data-oruga="button">
									<span class="o-btn__wrapper">
									<!---->
									<span class="o-btn__label">Confirm my participation</span>
									<!---->
									</span>
								</button>
								</div>
							</form>
							</section>
						</div>
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
				</section>
				</div>
				<div class="rounded-lg dark:border-violet-title flex flex-wrap flex-col md:flex-row-reverse gap-4">
				<aside class="rounded bg-white dark:bg-zinc-700 shadow-md h-min max-w-screen-sm">
					<div style="padding-top: 0.25rem" class="sticky p-4">
					<div data-v-3aec49f0="">
						<div data-v-9bd0e2da="" data-v-3aec49f0="" icon="map-marker">
						<h2 data-v-9bd0e2da="">Location</h2>
						<div data-v-9bd0e2da="" class="flex items-center mb-3 gap-1 eventMetadataBlock">
							<span data-v-3aec49f0="" class="o-icon" data-oruga="icon">
							<span class="material-design-icon map-marker-icon" aria-hidden="true" role="img">
								<svg fill="currentColor" class="material-design-icon__svg" width="36" height="36" viewBox="0 0 24 24">
								<path d="M12,11.5A2.5,2.5 0 0,1 9.5,9A2.5,2.5 0 0,1 12,6.5A2.5,2.5 0 0,1 14.5,9A2.5,2.5 0 0,1 12,11.5M12,2A7,7 0 0,0 5,9C5,14.25 12,22 12,22C12,22 19,14.25 19,9A7,7 0 0,0 12,2Z">
									<!---->
								</path>
								</svg>
							</span>
							</span>
							<div data-v-9bd0e2da="" class="content-wrapper overflow-hidden w-full">
							<div data-v-3aec49f0="" class="address-wrapper">
								<!---->
								<div data-v-3aec49f0="" class="address">
								<address data-v-f1e7c41f="" data-v-3aec49f0="" dir="auto">
									<!---->
									<p data-v-f1e7c41f="">
									<span data-v-f1e7c41f="" class="addressDescription" title="Le P'tit Clem">Le P'tit Clem</span>
									<br data-v-f1e7c41f="">
									<span data-v-f1e7c41f="">212 Grande Rue, 69930, Saint Clément Les Places</span>
									<br data-v-f1e7c41f="">
									<!---->
									</p>
								</address>
								<button data-v-3aec49f0="" type="button" class="o-btn btn o-btn--text btn-text map-show-button" role="button" data-oruga="button">
									<span class="o-btn__wrapper">
									<!---->
									<span class="o-btn__label">Show map</span>
									<!---->
									</span>
								</button>
								</div>
							</div>
							</div>
						</div>
						</div>
						<div data-v-9bd0e2da="" data-v-3aec49f0="">
						<h2 data-v-9bd0e2da="">Date and time</h2>
						<div data-v-9bd0e2da="" class="flex items-center mb-3 gap-1 eventMetadataBlock">
							<span data-v-3aec49f0="" aria-hidden="true" class="material-design-icon calendar-icon" role="img">
							<svg fill="currentColor" class="material-design-icon__svg" width="36" height="36" viewBox="0 0 24 24">
								<path d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z">
								<!---->
								</path>
							</svg>
							</span>
							<div data-v-9bd0e2da="" class="content-wrapper overflow-hidden w-full">
							<p>On Friday, February 7, 2025 starting at 8:00 PM</p>
							<!---->
							</div>
						</div>
						</div>
						<div data-v-9bd0e2da="" data-v-3aec49f0="" class="metadata-organized-by">
						<h2 data-v-9bd0e2da="">Organized by</h2>
						<div data-v-9bd0e2da="" class="flex items-center mb-3 gap-1 eventMetadataBlock">
							<div data-v-9bd0e2da="" class="content-wrapper overflow-hidden w-full">
							<a data-v-3aec49f0="" href="/@groupe_le_ptit_clem" class="hover:underline">
								<div data-v-ec6d6c8c="" data-v-3aec49f0="" class="bg-white dark:bg-mbz-purple rounded-lg flex space-x-4 items-center">
								<div data-v-ec6d6c8c="" class="flex pl-2">
									<figure data-v-ec6d6c8c="" class="w-12 h-12">
									<img data-v-ec6d6c8c="" class="rounded-full object-cover h-full" src="https://lekalepin.fr/media/26b04d58ce387b219b5cb46be874141dd7b8ae87c21201de94f476b53b3ca21a.jpg?name=P%27tit%20Clem_TEST%202_TEST%202%284%29.jpg" alt="" width="48" height="48" loading="lazy">
									</figure>
								</div>
								<div data-v-ec6d6c8c="" class="overflow-hidden w-full">
									<h5 data-v-ec6d6c8c="" class="text-xl font-medium violet-title tracking-tight text-gray-900 dark:text-gray-200 whitespace-pre-line line-clamp-2">Le P'tit Clem</h5>
									<p data-v-ec6d6c8c="" class="text-gray-500 dark:text-gray-200 truncate">
									<span data-v-ec6d6c8c="" dir="ltr">@groupe_le_ptit_clem</span>
									</p>
									<!---->
									<!---->
								</div>
								<!---->
								</div>
							</a>
							</div>
						</div>
						</div>
						<div data-v-9bd0e2da="" data-v-3aec49f0="">
							<h2 data-v-9bd0e2da="">Website</h2>
							<div data-v-9bd0e2da="" class="flex items-center mb-3 gap-1 eventMetadataBlock">
								<span data-v-3aec49f0="" aria-hidden="true" class="material-design-icon link-icon" role="img">
									<svg fill="currentColor" class="material-design-icon__svg" width="36" height="36" viewBox="0 0 24 24"><path d="M3.9,12C3.9,10.29 5.29,8.9 7,8.9H11V7H7A5,5 0 0,0 2,12A5,5 0 0,0 7,17H11V15.1H7C5.29,15.1 3.9,13.71 3.9,12M8,13H16V11H8V13M17,7H13V8.9H17C18.71,8.9 20.1,10.29 20.1,12C20.1,13.71 18.71,15.1 17,15.1H13V17H17A5,5 0 0,0 22,12A5,5 0 0,0 17,7Z"><!----></path></svg>
								</span>
								<div data-v-9bd0e2da="" class="content-wrapper overflow-hidden w-full">
									<a data-v-3aec49f0="" target="_blank" class="underline" rel="noopener noreferrer ugc" href="https://trotzallem.noblogs.org/post/2025/01/06/fr-7-februar-filmabend-feminism-wtf/" title="View page on trotzallem.noblogs.org (in a new window)">trotzallem.noblogs.org/post/2025/01/06/fr-7-februar-filmabend-feminism-wtf/</a>
								</div>
							</div>
						</div>
						<!---->
					</div>
					</div>
				</aside>
				<div class="flex-1">
					<section class="event-description bg-white dark:bg-zinc-700 px-3 pt-1 pb-3 rounded mb-4">
					<h2 class="text-2xl">About this event</h2>
					<!---->
					<!---->
					<div>
						<div lang="fr" dir="auto" class="mt-4 prose md:prose-lg lg:prose-xl dark:prose-invert prose-h1:text-xl prose-h1:font-semibold prose-h2:text-lg prose-h3:text-base md:prose-h1:text-2xl md:prose-h1:font-semibold md:prose-h2:text-xl md:prose-h3:text-lg lg:prose-h1:text-2xl lg:prose-h1:font-semibold lg:prose-h2:text-xl lg:prose-h3:text-lg">
						<p>Due di Coppe naît de l’envie commune de Florian Vella et Marco Carollo de renouer avec leurs racines italiennes. Du Piémont à la Sicile, les deux musiciens tissent un fil fait de chansons populaires d’osteria, de poésie, de travail, ou d’amour... </p>
						<p>Due di Coppe plonge les spectateurs dans l’ambiance décontractée et chaleureuse de la musique populaire italienne, en proposant des arrangements originaux forts de leurs nombreuses influences, mais aussi des histoires et des anecdotes de ce Pays contrasté et haut en couleurs.</p>
						</div>
					</div>
					</section>
					<section class="my-4"></section>
					<section class="bg-white dark:bg-zinc-700 px-3 pt-1 pb-3 rounded my-4">
					<a href="#comments">
						<h2 class="text-2xl" id="comments">Comments</h2>
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
							<span data-v-0aad39c9="">No comments yet</span>
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
