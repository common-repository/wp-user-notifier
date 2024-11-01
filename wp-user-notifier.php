<?php
/*
Plugin Name: WP User Notifier
Plugin URI: http://ml.lviv.ua/projects/plugins/wp-user-notifier
Description: Notify a user about when he sent comment or form via Contact Form 7 
Tags: alerts, comment, customize, email, from, html, letter, mail, notice, notifications, notify, pingback, plain, shortcode, tag, trackback, user, users, wp_mail
Version: 1.0
Requires at least: 3.0
Author: Mike Luskavets
Author URI: http://ml.lviv.ua
License: GPL2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wp-user-notifier
Domain Path: /languages/
*/

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

include_once dirname( __FILE__ ) . '/classes/class-wp-user-notifier.php';

new WpUserNotifier();