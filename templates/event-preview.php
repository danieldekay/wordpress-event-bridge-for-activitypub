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
			#gancio .v-list-item{
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
		<div id="gancio" class="application-preview">
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
									<span class="ml-2 text-uppercase">Tuesday, Mar 11, 05:00 PM-09:00 PM</span>
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
