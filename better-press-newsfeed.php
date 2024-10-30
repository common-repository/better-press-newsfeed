<?php
/*
Plugin Name: Better Press Newsfeed
Plugin URI: http://reaktivstudios.com/custom-plugins/
Description: A plugin to provide a dashboard widget for WP Tavern and Post Status news
Author: Andrew Norcross
Version: 1.0.0
Requires at least: 3.8
Author URI: http://andrewnorcross.com
*/
/*  Copyright 2014 Andrew Norcross

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License (GPL v2) only.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


if( ! defined( 'BTFD_BASE ' ) )
	define( 'BTFD_BASE', plugin_basename(__FILE__) );

if( ! defined( 'BTFD_VER' ) )
	define( 'BTFD_VER', '1.0.0' );


class Better_Press_Newsfeed
{

	/**
	 * Static property to hold our singleton instance
	 * @var Better_Press_Newsfeed
	 */
	static $instance = false;

	/**
	 * FIRE IT UP KIDS
	 */
	private function __construct() {

		add_action			(	'plugins_loaded',					array(	$this,	'textdomain'				)			);
		add_action			(	'wp_dashboard_setup',				array(	$this,	'dashboard_widgets'			)			);

	}

	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return
	 */

	public static function getInstance() {

		if ( !self::$instance )
			self::$instance = new self;
		return self::$instance;

	}

	/**
	 * load the translation domain
	 * @return void
	 */
	public function textdomain() {

		load_plugin_textdomain( 'better-press-newsfeed', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	}

	/**
	 * load the dashboard widgets
	 * @return [type] [description]
	 */
	public function dashboard_widgets() {

		wp_add_dashboard_widget( 'btfd-poststatus', __( 'Latest Poststat.us News', 'better-press-newsfeed' ), array( $this, 'build_status' ) );
		wp_add_dashboard_widget( 'btfd-wp-tavern', __( 'Latest WP Tavern News', 'better-press-newsfeed' ), array( $this, 'build_tavern' ) );

	}

	/**
	 * build the Post Status widget item
	 * @return HTML the feed list, or error message
	 */
	public function build_status() {

		// load our RSS feed
		$feed	= fetch_feed( 'http://poststat.us/feed/' );

		// opening markup
		echo '<div class="rss-widget">';

		// Checks that the object is created correctly
		if ( is_wp_error( $feed ) ) {
			echo $this->display_feed_error( $feed );
			return;
		} else {
			echo $this->display_feed_items( $feed );
		}

		echo '</div>';

	}

	/**
	 * build the WP Tavern widget item
	 * @return HTML the feed list, or error message
	 */
	public function build_tavern() {

		// load our RSS feed
		$feed	= fetch_feed( 'http://wptavern.com/feed/' );

		// opening markup
		echo '<div class="rss-widget">';

		// Checks that the object is created correctly
		if ( is_wp_error( $feed ) ) {
			echo $this->display_feed_error( $feed );
			return;
		} else {
			echo $this->display_feed_items( $feed );
		}

		echo '</div>';

	}

	/**
	 * build and return the individual feed items
	 * @param  object	$feed 	the SimplePie feed object
	 * @return html				the list of feed post items
	 */
	public function display_feed_items( $feed ) {

		// set our summary, count, and date format with optional filters
		$count		= apply_filters( 'better_press_newsfeed_item_count', 5 );
		$format		= apply_filters( 'better_press_newsfeed_date_format', 'F jS, Y' );
		$summary	= apply_filters( 'better_press_newsfeed_show_summary', false );

		// fetch our items and count our feed
		$max	= $feed->get_item_quantity( absint( $count ) );
		$items	= $feed->get_items( 0, $max );

		if ( $max == 0 ) {

			return '<p>' . __( 'No current items', 'better-press-newsfeed' ) . '</p>';

		} else {

			// start the list markup
			$list	= '<ul>';

			// loop our items
			foreach ( $items as $item ) {

				// fetch our items from the feed object
				$link	= $item->get_permalink();
				$title	= $item->get_title();
				$date	= $item->get_date( $format );
				$text	= $item->get_description();

				// build the individual item
				$list	.= '<li>';

					// feed title
					$list	.= '<a class="rsswidget" target="_blank" href="' . esc_url ( $link ) . '" title="' . __( 'Posted on:', 'better-press-newsfeed' ) . ' '. esc_attr( $date ) . '">' . esc_html( $title ) . '</a>';

					// feed date inline
					$list	.= '<span class="rss-date">'. esc_attr( $date ) . '</span>';

					// feed summary if enabled
					if ( $summary ) {
						$list	.= '<div class="rssSummary">'.$text.'</div>';
					}

				$list	.= '</li>';

			} // end foreach

			$list	.= '</ul>';

		}

		// send it back
		return $list;

	}

	/**
	 * build and return the error message returned from the feed
	 * @param  object	$feed 	the SimplePie feed object
	 * @return html				the error message
	 */
	public function display_feed_error( $feed ) {

		// bail if no feed got passed
		if ( ! $feed ) {
			return '<p>' . __( '<strong>RSS Error</strong>: Feed Error', 'better-press-newsfeed' ) . '</p>';
		}

		// fetch our error message
		$error	= $feed->get_error_message();

		// bail if no error message is there
		if ( ! $error || empty( $error ) ) {
			return '<p>' . __( '<strong>RSS Error</strong>: Feed Error', 'better-press-newsfeed' ) . '</p>';
		}

		// build and return the message
		$message	= '<p>';
		$message	.= sprintf (__('<strong>RSS Error</strong>: %s'), $error );
		$message	.= '<p>';

		return $message;

	}

/// end class
}

// Instantiate our class
$Better_Press_Newsfeed = Better_Press_Newsfeed::getInstance();