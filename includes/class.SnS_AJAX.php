<?php
class SnS_AJAX
{
	function init() {
		// Keep track of current tab.
		add_action( 'wp_ajax_sns_update_tab', array( __CLASS__, 'update_tab' ) );
		// TinyMCE requests a css file.
		add_action( 'wp_ajax_sns_tinymce_styles', array( __CLASS__, 'tinymce_styles' ) );
		
		// Ajax Saves.
		add_action( 'wp_ajax_sns_classes', array( __CLASS__, 'classes' ) );
		add_action( 'wp_ajax_sns_scripts', array( __CLASS__, 'scripts' ) );
		add_action( 'wp_ajax_sns_styles', array( __CLASS__, 'styles' ) );
		add_action( 'wp_ajax_sns_dropdown', array( __CLASS__, 'dropdown' ) );
		add_action( 'wp_ajax_sns_delete_class', array( __CLASS__, 'delete_class' ) );
	}
	function update_tab() {
		check_ajax_referer( Scripts_n_Styles::$file );
		
		$active_tab = isset( $_POST[ 'active_tab' ] ) ? (int)$_POST[ 'active_tab' ] : 0;
		
		if ( ! $user = wp_get_current_user() ) exit( 'Bad User' );
		
		$success = update_user_option( $user->ID, "current-sns-tab", $active_tab, true);
		exit( $success );
	}
	function tinymce_styles() {
		check_ajax_referer( 'sns_tinymce_styles' );
		
		if ( empty( $_REQUEST[ 'post_id' ] ) ) exit( 'Bad post ID.' );
		$post_id = absint( $_REQUEST[ 'post_id' ] );
		
		$options = get_option( 'SnS_options' );
		$styles = get_post_meta( $post_id, '_SnS_styles', true );
		
		header('Content-Type: text/css; charset=' . get_option('blog_charset'));
		
		if ( ! empty( $options[ 'styles' ] ) ) echo $options[ 'styles' ];
		
		if ( ! empty( $styles[ 'styles' ] ) ) echo $styles[ 'styles' ];
		
		exit();
	}
	
	// AJAX handlers
	function classes() {
		check_ajax_referer( Scripts_n_Styles::$file );
		if ( ! current_user_can( 'unfiltered_html' ) || ! current_user_can( 'edit_posts' ) ) exit( 'Insufficient Privileges.' );
		
		if ( empty( $_REQUEST[ 'post_id' ] ) ) exit( 'Bad post ID.' );
		if ( ! isset( $_REQUEST[ 'classes_body' ], $_REQUEST[ 'classes_post' ] ) ) exit( 'Data missing.' );
		
		$post_id = absint( $_REQUEST[ 'post_id' ] );
		$styles = get_post_meta( $post_id, '_SnS_styles', true );
		
		$styles = self::maybe_set( $styles, 'classes_body' );
		$styles = self::maybe_set( $styles, 'classes_post' );
		
		self::maybe_update( $post_id, '_SnS_styles', $styles );
		
		header('Content-Type: application/json; charset=' . get_option('blog_charset'));
		echo json_encode( array(
			"classes_post" => $_REQUEST[ 'classes_post' ],
			"classes_body" => $_REQUEST[ 'classes_body' ]
		) );
		
		exit();
	}
	function scripts() {
		check_ajax_referer( Scripts_n_Styles::$file );
		if ( ! current_user_can( 'unfiltered_html' ) || ! current_user_can( 'edit_posts' ) ) exit( 'Insufficient Privileges.' );
		
		if ( empty( $_REQUEST[ 'post_id' ] ) ) exit( 'Bad post ID.' );
		if ( ! isset( $_REQUEST[ 'scripts' ], $_REQUEST[ 'scripts_in_head' ] ) ) exit( 'Data incorrectly sent.' );
		
		$post_id = absint( $_REQUEST[ 'post_id' ] );
		$scripts = get_post_meta( $post_id, '_SnS_scripts', true );
		
		$scripts = self::maybe_set( $scripts, 'scripts_in_head' );
		$scripts = self::maybe_set( $scripts, 'scripts' );
		
		self::maybe_update( $post_id, '_SnS_scripts', $scripts );
		
		header('Content-Type: application/json; charset=' . get_option('blog_charset'));
		echo json_encode( array(
			"scripts" => $_REQUEST[ 'scripts' ],
			"scripts_in_head" => $_REQUEST[ 'scripts_in_head' ],
		) );
		
		exit();
	}
	function styles() {
		check_ajax_referer( Scripts_n_Styles::$file );
		if ( ! current_user_can( 'unfiltered_html' ) || ! current_user_can( 'edit_posts' ) ) exit( 'Insufficient Privileges.' );
		
		if ( empty( $_REQUEST[ 'post_id' ] ) ) exit( 'Bad post ID.' );
		if ( ! isset( $_REQUEST[ 'styles' ] ) ) exit( 'Data incorrectly sent.' );
		
		$post_id = absint( $_REQUEST[ 'post_id' ] );
		$styles = get_post_meta( $post_id, '_SnS_styles', true );
		
		$styles = self::maybe_set( $styles, 'styles' );
		
		self::maybe_update( $post_id, '_SnS_styles', $styles );
		
		header('Content-Type: application/json; charset=' . get_option('blog_charset'));
		echo json_encode( array(
			"styles" => $_REQUEST[ 'styles' ],
		) );
		
		exit();
	}
	function dropdown() {
		check_ajax_referer( Scripts_n_Styles::$file );
		if ( ! current_user_can( 'unfiltered_html' ) || ! current_user_can( 'edit_posts' ) ) exit( 'Insufficient Privileges.' );
		
		if ( empty( $_REQUEST[ 'format' ] ) ) exit( 'Missing Format.' );
		if ( empty( $_REQUEST[ 'format' ][ 'title' ] ) ) exit( 'Title is required.' );
		if ( empty( $_REQUEST[ 'format' ][ 'classes' ] ) ) exit( 'Classes is required.' );
		if (
			empty( $_REQUEST[ 'format' ][ 'inline' ] ) &&
			empty( $_REQUEST[ 'format' ][ 'block' ] ) &&
			empty( $_REQUEST[ 'format' ][ 'selector' ] )
		) exit( 'A type is required.' );
		
		if ( empty( $_REQUEST[ 'post_id' ] ) ) exit( 'Bad post ID.' );
		$post_id = absint( $_REQUEST[ 'post_id' ] );
		
		$styles = get_post_meta( $post_id, '_SnS_styles', true );
		
		if ( ! isset( $styles[ 'classes_mce' ] ) ) $styles[ 'classes_mce' ] = array();
		
		// pass title as key to be able to delete.
		$styles[ 'classes_mce' ][ $_REQUEST[ 'format' ][ 'title' ] ] = $_REQUEST[ 'format' ];
		
		update_post_meta( $post_id, '_SnS_styles', $styles );
		
		header('Content-Type: application/json; charset=' . get_option('blog_charset'));
		echo json_encode( array(
			"classes_mce" => array_values( $styles[ 'classes_mce' ] )
		) );
		
		exit();
	}
	function delete_class() {
		check_ajax_referer( Scripts_n_Styles::$file );
		if ( ! current_user_can( 'unfiltered_html' ) || ! current_user_can( 'edit_posts' ) ) exit( 'Insufficient Privileges.' );
		
		if ( empty( $_REQUEST[ 'post_id' ] ) ) exit( 'Bad post ID.' );
		$post_id = absint( $_REQUEST[ 'post_id' ] );
		$styles = get_post_meta( $post_id, '_SnS_styles', true );
		
		$title = $_REQUEST[ 'delete' ];
		
		if ( isset( $styles[ 'classes_mce' ][ $title ] ) ) unset( $styles[ 'classes_mce' ][ $title ] );
		else exit ( 'No Format of that name.' );
		
		if ( empty( $styles[ 'classes_mce' ] ) ) unset( $styles[ 'classes_mce' ] );
		
		self::maybe_update( $post_id, '_SnS_styles', $styles );
		
		if ( ! isset( $styles[ 'classes_mce' ] ) ) $styles[ 'classes_mce' ] = array( 'Empty' );
		
		header('Content-Type: application/json; charset=' . get_option('blog_charset'));
		echo json_encode( array(
			"classes_mce" => array_values( $styles[ 'classes_mce' ] )
		) );
		
		exit();
	}
	
	// Differs from SnS_Admin_Meta_Box::maybe_set() in that this needs no prefix.
	function maybe_set( $o, $i ) {
		if ( empty( $_REQUEST[ $i ] ) ) {
			if ( isset( $o[ $i ] ) ) unset( $o[ $i ] );
		} else $o[ $i ] = $_REQUEST[ $i ];
		return $o;
	}
	function maybe_update( $id, $name, $meta ) {
		if ( empty( $meta ) ) delete_post_meta( $id, $name );
		else update_post_meta( $id, $name, $meta );
	}
}
?>