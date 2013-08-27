<?php
/**
 * Plugin Name: WDS Twitter Widget
 * Plugin URI:  http://webdevstudios.com
 * Description: WordPress Twitter widget
 * Version:     0.1.0
 * Author:      WebDevStudios
 * Author URI:  http://webdevstudios.com
 * Donate link: http://webdevstudios.com
 * License:     GPLv2+
 * Text Domain: wds_twwi
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2013 WebDevStudios (email : contact@webdevstudios.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Built using grunt-wp-plugin
 * Copyright (c) 2013 10up, LLC
 * https://github.com/10up/grunt-wp-plugin
 */

// Useful global constants
define( 'WDS_TWWI_VERSION', '0.1.0' );
define( 'WDS_TWWI_URL',     plugin_dir_url( __FILE__ ) );
define( 'WDS_TWWI_PATH',    dirname( __FILE__ ) . '/' );


class Wds_Twitter_Widget {

	// A single instance of this class.
	private static $instance = null;

	/**
	 * Creates or returns an instance of this class.
	 * @since  0.1.0
	 * @return Wds_Twitter_Widget A single instance of this class.
	 */
	public static function init() {
		if ( null == self::$instance )
			self::$instance = new self();

		return self::$instance;
	}

	/**
	 * Sets up our plugin
	 * @since  0.1.0
	 */
	private function __construct() {

		add_action( 'init', array( $this, 'hooks' )  );
		add_action( 'admin_init', array( $this, 'admin_hooks' )  );
		add_action( 'widgets_init', array( $this, 'widget' )  );
		// Wireup filters
		// Wireup shortcodes
	}

	/**
	 * Init hooks
	 * @since  0.1.0
	 * @return null
	 */
	public function hooks() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'wds_twwi' );
		load_textdomain( 'wds_twwi', WP_LANG_DIR . '/wds_twwi/wds_twwi-' . $locale . '.mo' );
		load_plugin_textdomain( 'wds_twwi', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Hooks for the Admin
	 * @since  0.1.0
	 * @return null
	 */
	public function admin_hooks() {
	}

	public function widget() {
		register_widget( 'WDS_Latest_Tweets_Widget' );
	}

}

// init our class
Wds_Twitter_Widget::init();


/**
 * Activate the plugin
 */
function wds_twwi_activate() {
	// First load the init scripts in case any rewrite functionality is being loaded
	Wds_Twitter_Widget::init();

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wds_twwi_activate' );

/**
 * Deactivate the plugin
 * Uninstall routines should be in uninstall.php
 */
function wds_twwi_deactivate() {

}
register_deactivation_hook( __FILE__, 'wds_twwi_deactivate' );


/**
 * Genesis Latest Tweets widget class.
 *
 * @category Genesis
 * @package Widgets
 *
 * @since 0.1.8
 */
class WDS_Latest_Tweets_Widget extends WP_Widget {

	/**
	 * Holds widget settings defaults, populated in constructor.
	 *
	 * @var array
	 */
	protected $defaults;

	/**
	 * Constructor. Set the default widget options and create widget.
	 *
	 * @since 0.1.8
	 */
	function __construct() {

		$this->defaults = array(
			'title'                => '',
			'twitter_id'           => '',
			'twitter_num'          => '',
			'twitter_duration'     => '',
			'twitter_hide_replies' => 0,
			'follow_link_show'     => 0,
			'follow_link_text'     => '',
			'consumer_key'         => '',
			'consumer_secret'      => '',
			'access_token'         => '',
			'access_token_secret'  => '',
		);

		$widget_ops = array(
			'classname'   => 'latest-tweets icon-twitter',
			'description' => __( 'Display a list of your latest tweets.', 'wds_twwi' ),
		);

		$control_ops = array(
			'id_base' => 'latest-tweets',
			'width'   => 200,
			'height'  => 250,
		);

		$this->WP_Widget( 'latest-tweets', __('WDS - Latest Tweets', 'wds_twwi'), $widget_ops, $control_ops );

	}

	/**
	 * Echo the widget content.
	 *
	 * @since 0.1.8
	 *
	 * @param array $args Display arguments including before_title, after_title, before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget
	 */
	function widget( $args, $instance ) {

		extract( $args );

		/** Merge with defaults */
		$instance = wp_parse_args( (array) $instance, $this->defaults );

		echo $before_widget;

		if ( $instance['title'] )
			echo $before_title . apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base ) . $after_title;

		echo '<ul>' . "\n";

		$tweets = get_transient( $instance['twitter_id'] . '-' . $instance['twitter_num'] . '-' . $instance['twitter_duration'] );

		if ( ! $tweets ) {
		wp_die( '<xmp>test: '. print_r( $tweets, true ) .'</xmp>' );
			$count   = isset( $instance['twitter_hide_replies'] ) ? (int) $instance['twitter_num'] + 100 : (int) $instance['twitter_num'];

			// Make sure we have our Twitter class
			if ( ! class_exists( 'TwitterWP' ) )
				require_once( plugin_dir_path( __FILE__ ) .'TwitterWP/lib/TwitterWP.php' );


			$app = array(
				'consumer_key'        => $instance['consumer_key'],
				'consumer_secret'     => $instance['consumer_secret'],
				'access_token'        => $instance['access_token'],
				'access_token_secret' => $instance['access_token_secret'],
			);
			// initiate your app
			$tw = TwitterWP::start( $app );
			wp_die( '<xmp>'. print_r( $tw, true ) .'</xmp>' );
			$twitter = $tw->get_tweets( $instance['twitter_id'], $count );

			if ( ! $twitter ) {
				$twitter[] = '<li>' . __( 'The Twitter API is taking too long to respond. Please try again later.', 'wds_twwi' ) . '</li>' . "\n";
			}
			elseif ( is_wp_error( $twitter ) ) {
				$twitter[] = '<li>' . __( 'There was an error while attempting to contact the Twitter API. Please try again.', 'wds_twwi' ) . '</li>' . "\n";
			}
			else {
				/** Build the tweets array */
				foreach ( (array) $twitter as $tweet ) {
					/** Don't include @ replies (if applicable) */
					if ( $instance['twitter_hide_replies'] && $tweet->in_reply_to_user_id )
						continue;

					/** Stop the loop if we've got enough tweets */
					if ( ! empty( $tweets[(int)$instance['twitter_num'] - 1] ) )
						break;

					/** Add tweet to array */
					$timeago = sprintf( __( 'about %s ago', 'wds_twwi' ), human_time_diff( strtotime( $tweet->created_at ) ) );
					$timeago_link = sprintf( '<a href="%s" rel="nofollow">%s</a>', esc_url( sprintf( 'http://twitter.com/%s/status/%s', $instance['twitter_id'], $tweet->id_str ) ), esc_html( $timeago ) );

					$tweets[] = '<li>' . genesis_tweet_linkify( $tweet->text ) . ' <span style="font-size: 85%;">' . $timeago_link . '</span></li>' . "\n";
				}

				/** Just in case */
				$tweets = array_slice( (array) $tweets, 0, (int) $instance['twitter_num'] );

				if ( $instance['follow_link_show'] && $instance['follow_link_text'] )
					$tweets[] = '<li class="last"><a href="' . esc_url( 'http://twitter.com/'.$instance['twitter_id'] ).'">'. esc_html( $instance['follow_link_text'] ) .'</a></li>';

				$time = ( absint( $instance['twitter_duration'] ) * 60 );

				/** Save them in transient */
				set_transient( $instance['twitter_id'].'-'.$instance['twitter_num'].'-'.$instance['twitter_duration'], $tweets, $time );
			}
		}
		foreach( (array) $tweets as $tweet )
			echo $tweet;

		echo '</ul>' . "\n";

		echo $after_widget;

	}

	/**
	 * Update a particular instance.
	 *
	 * This function should check that $new_instance is set correctly.
	 * The newly calculated value of $instance should be returned.
	 * If "false" is returned, the instance won't be saved/updated.
	 *
	 * @since 0.1.8
	 *
	 * @param array $new_instance New settings for this instance as input by the user via form()
	 * @param array $old_instance Old settings for this instance
	 * @return array Settings to save or bool false to cancel saving
	 */
	function update( $new_instance, $old_instance ) {

		/** Force the transient to refresh */
		delete_transient( $old_instance['twitter_id'].'-'.$old_instance['twitter_num'].'-'.$old_instance['twitter_duration'] );
		$new_instance['title'] = strip_tags( $new_instance['title'] );
		return $new_instance;

	}

	/**
	 * Echo the settings update form.
	 *
	 * @since 0.1.8
	 *
	 * @param array $instance Current settings
	 */
	function form( $instance ) {

		/** Merge with defaults */
		$instance = wp_parse_args( (array) $instance, $this->defaults );

		?>
		<h3><?php _e( 'Twitter App Credentials', 'wds_twwi' ); ?></h3>
		<p>
			<label for="<?php echo $this->get_field_id( 'consumer_key' ); ?>"><?php _e( 'Consumer Key', 'wds_twwi' ); ?>:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'consumer_key' ); ?>" name="<?php echo $this->get_field_name( 'consumer_key' ); ?>" value="<?php echo esc_attr( $instance['consumer_key'] ); ?>" class="widefat" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'consumer_secret' ); ?>"><?php _e( 'Consumer Secret', 'wds_twwi' ); ?>:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'consumer_secret' ); ?>" name="<?php echo $this->get_field_name( 'consumer_secret' ); ?>" value="<?php echo esc_attr( $instance['consumer_secret'] ); ?>" class="widefat" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'access_token' ); ?>"><?php _e( 'Access Token', 'wds_twwi' ); ?>:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'access_token' ); ?>" name="<?php echo $this->get_field_name( 'access_token' ); ?>" value="<?php echo esc_attr( $instance['access_token'] ); ?>" class="widefat" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'access_token_secret' ); ?>"><?php _e( 'Access Token Secret', 'wds_twwi' ); ?>:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'access_token_secret' ); ?>" name="<?php echo $this->get_field_name( 'access_token_secret' ); ?>" value="<?php echo esc_attr( $instance['access_token_secret'] ); ?>" class="widefat" />
		</p>

		<h3><?php _e( 'Widget Options', 'wds_twwi' ); ?></h3>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'wds_twwi' ); ?>:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'twitter_id' ); ?>"><?php _e( 'Twitter Username', 'wds_twwi' ); ?>:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'twitter_id' ); ?>" name="<?php echo $this->get_field_name( 'twitter_id' ); ?>" value="<?php echo esc_attr( $instance['twitter_id'] ); ?>" class="widefat" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'twitter_num' ); ?>"><?php _e( 'Number of Tweets to Show', 'wds_twwi' ); ?>:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'twitter_num' ); ?>" name="<?php echo $this->get_field_name( 'twitter_num' ); ?>" value="<?php echo esc_attr( $instance['twitter_num'] ); ?>" size="3" />
		</p>

		<p>
			<input id="<?php echo $this->get_field_id( 'twitter_hide_replies' ); ?>" type="checkbox" name="<?php echo $this->get_field_name( 'twitter_hide_replies' ); ?>" value="1" <?php checked( $instance['twitter_hide_replies'] ); ?>/>
			<label for="<?php echo $this->get_field_id( 'twitter_hide_replies' ); ?>"><?php _e( 'Hide @ Replies', 'wds_twwi' ); ?></label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'twitter_duration' ); ?>"><?php _e( 'Load new Tweets every', 'wds_twwi' ); ?></label>
			<select name="<?php echo $this->get_field_name( 'twitter_duration' ); ?>" id="<?php echo $this->get_field_id( 'twitter_duration' ); ?>">
				<option value="5" <?php selected( 5, $instance['twitter_duration'] ); ?>><?php _e( '5 Min.' , 'wds_twwi' ); ?></option>
				<option value="15" <?php selected( 15, $instance['twitter_duration'] ); ?>><?php _e( '15 Minutes' , 'wds_twwi' ); ?></option>
				<option value="30" <?php selected( 30, $instance['twitter_duration'] ); ?>><?php _e( '30 Minutes' , 'wds_twwi' ); ?></option>
				<option value="60" <?php selected( 60, $instance['twitter_duration'] ); ?>><?php _e( '1 Hour' , 'wds_twwi' ); ?></option>
				<option value="120" <?php selected( 120, $instance['twitter_duration'] ); ?>><?php _e( '2 Hours' , 'wds_twwi' ); ?></option>
				<option value="240" <?php selected( 240, $instance['twitter_duration'] ); ?>><?php _e( '4 Hours' , 'wds_twwi' ); ?></option>
				<option value="720" <?php selected( 720, $instance['twitter_duration'] ); ?>><?php _e( '12 Hours' , 'wds_twwi' ); ?></option>
				<option value="1440" <?php selected( 1440, $instance['twitter_duration'] ); ?>><?php _e( '24 Hours' , 'wds_twwi' ); ?></option>
			</select>
		</p>

		<p>
			<input id="<?php echo $this->get_field_id( 'follow_link_show' ); ?>" type="checkbox" name="<?php echo $this->get_field_name( 'follow_link_show' ); ?>" value="1" <?php checked( $instance['follow_link_show'] ); ?>/>
			<label for="<?php echo $this->get_field_id( 'follow_link_show' ); ?>"><?php _e( 'Include link to twitter page?', 'wds_twwi' ); ?></label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'follow_link_text' ); ?>"><?php _e( 'Link Text (required)', 'wds_twwi' ); ?>:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'follow_link_text' ); ?>" name="<?php echo $this->get_field_name( 'follow_link_text' ); ?>" value="<?php echo esc_attr( $instance['follow_link_text'] ); ?>" class="widefat" />
		</p>
		<?php

	}

}