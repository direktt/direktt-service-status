<?php

/**
 * Plugin Name: Direktt Service Status
 * Description: Direktt Service Status Direktt Plugin
 * Version: 1.0.0
 * Author: Direktt
 * Author URI: https://direktt.com/
 * License: GPL2
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'plugins_loaded', 'direktt_service_status_activation_check', -20 );

function direktt_service_status_activation_check() {
	
	if ( ! function_exists( 'is_plugin_active' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $required_plugin = 'direktt/direktt.php';
    $is_required_active = is_plugin_active($required_plugin)
        || (is_multisite() && is_plugin_active_for_network($required_plugin));

    if (! $is_required_active) {
        // Deactivate this plugin
        deactivate_plugins(plugin_basename(__FILE__));

        // Prevent the “Plugin activated.” notice
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }

        // Show an error notice for this request
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error is-dismissible"><p>'
                . esc_html__('Direktt Service Status activation failed: The Direktt WordPress Plugin must be active first.', 'direktt-service-status')
                . '</p></div>';
        });

        // Optionally also show the inline row message in the plugins list
        add_action(
            'after_plugin_row_direktt-service-status/direktt-service-status.php',
            function () {
                echo '<tr class="plugin-update-tr"><td colspan="3" style="box-shadow:none;">'
                    . '<div style="color:#b32d2e;font-weight:bold;">'
                    . esc_html__('Direktt Service Status requires the Direktt WordPress Plugin to be active. Please activate it first.', 'direktt-service-status')
                    . '</div></td></tr>';
            },
            10,
            0
        );
    }
}

add_action( 'init', 'direktt_register_service_case_cpt' );

function direktt_register_service_case_cpt() {
	$labels = array(
		'name'               => esc_html__( 'Direktt Service Cases', 'direktt-service-status' ),
		'singular_name'      => esc_html__( 'Direktt Service Case', 'direktt-service-status' ),
		'menu_name'          => esc_html__( 'Direktt Service Cases', 'direktt-service-status' ),
		'name_admin_bar'     => esc_html__( 'Direktt Service Case', 'direktt-service-status' ),
		'add_new'            => esc_html__( 'Add New', 'direktt-service-status' ),
		'add_new_item'       => esc_html__( 'Add New Service Case', 'direktt-service-status' ),
		'new_item'           => esc_html__( 'New Service Case', 'direktt-service-status' ),
		'edit_item'          => esc_html__( 'Edit Service Case', 'direktt-service-status' ),
		'view_item'          => esc_html__( 'View Service Case', 'direktt-service-status' ),
		'all_items'          => esc_html__( 'Direktt Service Cases', 'direktt-service-status' ),
		'search_items'       => esc_html__( 'Search Service Cases', 'direktt-service-status' ),
		'not_found'          => esc_html__( 'No service cases found.', 'direktt-service-status' ),
		'not_found_in_trash' => esc_html__( 'No service cases found in Trash.', 'direktt-service-status' ),
	);

	$args = array(
		'labels'              => $labels,
		'public'              => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
		'show_ui'             => true,
		'show_in_menu'        => false,
		'menu_position'       => 20,
		'menu_icon'           => 'dashicons-hammer',
		'supports'            => array( 'title', 'editor' ),
	);

	register_post_type( 'direktt_service_case', $args );
}

add_action( 'admin_enqueue_scripts', 'direktt_dss_enqueue_admin_assets' );

function direktt_dss_enqueue_admin_assets( $hook ) {
	$screen = get_current_screen();
	if ( in_array( $hook, array( 'post.php', 'post-new.php' ) ) && $screen->post_type === 'direktt_service_case' ) {
		wp_register_style( 'jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css' );
		wp_enqueue_style( 'jquery-ui-css' );
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_script( 'direktt-service-status', plugins_url( 'direktt-service-status.js', __FILE__ ), array( 'jquery' ), filemtime( plugin_dir_path( __FILE__ ) . 'direktt-service-status.js' ), true );
		wp_enqueue_style( 'direktt-service-status-style', plugins_url( 'direktt-service-status.css', __FILE__ ), array(), filemtime( plugin_dir_path( __FILE__ ) . 'direktt-service-status.css' ) );
	}
}

add_action( 'wp_enqueue_scripts', 'direktt_dss_enqueue_fe_assets' );

function direktt_dss_enqueue_fe_assets( $hook ) {
	global $enqueue_direktt_case_script;
	if ( $enqueue_direktt_case_script ) {
		wp_register_style( 'jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css' );
		wp_enqueue_style( 'jquery-ui-css' );
		wp_enqueue_script( 'jquery-ui-autocomplete' );
	}
}

add_action( 'edit_form_after_title', 'direktt_dss_add_popup' );

function direktt_dss_add_popup( $post ) {
	if ( 'direktt_service_case' === $post->post_type ) {
		?>
		<div class="dsc-error-popup">
			<div class="dsc-error-popup-content">
				<p class="dsc-error-text"><?php echo esc_html__( 'Please enter valid Subscription ID.', 'direktt-service-status' ); ?></p>
				<button id="close-dsc-form-error"><?php echo esc_html__( 'Close', 'direktt-service-status' ); ?></button>
			</div>
		</div>
		<?php
	}
}

add_action( 'add_meta_boxes', 'direktt_add_dss_meta_boxes' );

function direktt_add_dss_meta_boxes() {
	add_meta_box(
		'dss_direktt_subscription_id',
		esc_html__( 'Subscription ID', 'direktt-service-status' ),
		'dss_direktt_subscription_id_meta_box_callback',
		'direktt_service_case',
		'side',
		'default'
	);

	add_meta_box(
		'dss_direktt_service_status_change_log',
		esc_html__( 'Service Status Change Log', 'direktt-service-status' ),
		'dss_direktt_service_status_change_log_meta_box_callback',
		'direktt_service_case',
		'normal',
		'default'
	);
}

function dss_direktt_subscription_id_meta_box_callback( $post ) {
	$subscription_id = get_post_meta( $post->ID, '_dss_direktt_subscription_id', true );

	$all_ids = array();
	$users   = get_posts(
		array(
			'post_type'      => 'direkttusers',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);
	foreach ( $users as $user_id ) {
		$all_ids[] = get_post_meta( $user_id, 'direktt_user_id', true );
	}

	?>
	<label for="dss_direktt_subscription_id_input"><?php echo esc_html__( 'Enter the ID:', 'direktt-service-status' ); ?></label>
	<input type="text" id="dss_direktt_subscription_id_input" name="dss_direktt_subscription_id_input" value="<?php echo esc_attr( $subscription_id ); ?>" placeholder="<?php echo esc_attr__( 'Enter the ID...', 'direktt' ); ?>" />
	<input type="hidden" id="dss_all_ids" name="dss_all_ids" value="<?php echo esc_attr( wp_json_encode( array_values( array_map( 'strval', $all_ids ) ) ) ); ?>" />
	<?php
}

function dss_direktt_service_status_change_log_meta_box_callback( $post ) {
	$log = get_post_meta( $post->ID, 'direktt_service_status_change_log', true ) ?: array();
	$log = array_reverse( $log );

	if ( ! empty( $log ) && is_array( $log ) ) {
		echo '<table class="widefat">';
		echo '<thead>';
			echo '<tr>';
				echo '<th>';
					echo esc_html__( 'User', 'direktt-service-status' );
				echo '</th>';
				echo '<th>';
					echo esc_html__( 'Time', 'direktt-service-status' );
				echo '</th>';
				echo '<th>';
					echo esc_html__( 'From', 'direktt-service-status' );
				echo '</th>';
				echo '<th>';
					echo esc_html__( 'To', 'direktt-service-status' );
				echo '</th>';
			echo '</tr>';
		echo '</thead>';
			echo '</tbody>';
		foreach ( $log as $entry ) {
			$user_id      = $entry['user_id'];
			$direktt_user = Direktt_User::get_user_by_subscription_id( $user_id );
			if ( $direktt_user ) {
				$user_name = $direktt_user['direktt_display_name'];
			} else {
				$user_info = get_userdata( $user_id );
				$user_name = $user_info ? $user_info->user_login : 'Unknown User';
			}
			if ( $entry['type'] === 'changed' ) {
				$old_term = $entry['old_term'] ? get_term( $entry['old_term'] )->name : 'None';
				$new_term = $entry['new_term'] ? get_term( $entry['new_term'] )->name : 'None';
				echo '<tr>';
					echo '<td>';
						echo wp_kses_post( '<strong>' . $user_name . '</strong> <br/><i>' . $user_id . '</i>' );
					echo '</td>';
					echo '<td>';
						echo esc_html( human_time_diff( strtotime( $entry['date'] ) ) . ' ago' );
					echo '</td>';
					echo '<td>';
						echo esc_html( $old_term );
					echo '</td>';
					echo '<td>';
						echo esc_html( $new_term );
					echo '</td>';
				echo '</tr>';
			} else {
				$status = $entry['status'] ? get_term( $entry['status'] )->name : 'None';
				echo '<tr>';
					echo '<td>';
						echo wp_kses_post( '<strong>' . $user_name . '</strong> <br/><i>' . $user_id . '</i>' );
					echo '</td>';
					echo '<td>';
						echo esc_html( human_time_diff( strtotime( $entry['date'] ) ) . ' ago' );
					echo '</td>';
					echo '<td>';
						echo esc_html( '/' );
					echo '</td>';
					echo '<td>';
						echo esc_html( $status );
					echo '</td>';
				echo '</tr>';
			}
		}
			echo '</tbody>';
		echo '</table>';
	} else {
		echo '<p>' . esc_html__( 'No status changes logged.', 'direktt-service-status' ) . '</p>';
	}
}

add_action( 'save_post_direktt_service_case', 'direktt_save_service_case_post' );

function direktt_save_service_case_post( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	$post = get_post( $post_id );

	if ( $post->post_status === 'trash' ) {
		return;
	}

	if ( trim( $post->post_title ) === '' ) {
		remove_action( 'save_post_direktt_service_case', 'direktt_save_service_case_post' );
		$default_title = 'DSC_' . $post_id;
		wp_update_post(
			array(
				'ID'         => $post_id,
				'post_title' => $default_title,
			)
		);
		add_action( 'save_post_direktt_service_case', 'direktt_save_service_case_post' );
		$post = get_post( $post_id );
	}

	$subscription_id = get_post_meta( $post_id, '_dss_direktt_subscription_id', true );

	if ( isset( $_POST['dss_direktt_subscription_id_input'] ) ) {
		$subscription_id = trim( sanitize_text_field( $_POST['dss_direktt_subscription_id_input'] ) );
		update_post_meta( $post_id, '_dss_direktt_subscription_id', $subscription_id );
	}

	$temp_transient = get_transient( 'dss_temp_id_transient' );
	if ( $temp_transient ) {
		$subscription_id = $temp_transient;
		update_post_meta( $post_id, '_dss_direktt_subscription_id', $subscription_id );
		delete_transient( 'dss_temp_id_transient' );
	}

	$case_opened_flag = get_post_meta( $post_id, '_dss_case_opened_flag', true );
	if ( empty( $case_opened_flag ) && ! empty( $subscription_id ) ) {
		$subscription_id   = get_post_meta( $post_id, '_dss_direktt_subscription_id', true );
		$new_case_template = intval( get_option( 'direktt_service_status_new_case_template', 0 ) );
		Direktt_Message::send_message_template(
			array( $subscription_id ),
			$new_case_template,
			array(
				'case-no'   => $post->post_title,
				'date-time' => current_time( 'mysql' ),
			)
		);
		update_post_meta( $post_id, '_dss_case_opened_flag', '1' );

		global $direktt_user;
		if ( $direktt_user ) {
			$user_id = $direktt_user['direktt_user_id'];
		} else {
			$wp_user         = wp_get_current_user();
			$direktt_user_wp = Direktt_User::get_direktt_user_by_wp_user( $wp_user );
			if ( $direktt_user_wp ) {
				$user_id = $direktt_user_wp['direktt_user_id'];
			} else {
				$user_id = $wp_user->ID;
			}
		}
		$log = get_post_meta( $post_id, 'direktt_service_status_change_log', true ) ?: array();
		if ( empty( $log ) ) {
			$case_status_terms = get_the_terms( $post_id, 'case_status' );
			if ( $case_status_terms ) {
				$case_status = ( $case_status_terms && ! is_wp_error( $case_status_terms ) ) ? $case_status_terms[0]->term_id : 0;
				$log[]       = array(
					'type'    => 'created',
					'user_id' => $user_id,
					'date'    => current_time( 'mysql' ),
					'status'  => $case_status,
				);
			}
		}
		update_post_meta( $post_id, 'direktt_service_status_change_log', $log );
	}

	$case_status = wp_get_post_terms( $post_id, 'case_status', array( 'fields' => 'names' ) );

	if ( empty( $case_status ) ) {
		$opening_status = intval( get_option( 'direktt_service_status_opening_status', 0 ) );
		if ( $opening_status !== 0 ) {
			wp_set_object_terms( $post_id, array( $opening_status ), 'case_status', false );
		}
	}
}

add_action( 'init', 'direktt_register_case_status_taxonomy' );

function direktt_register_case_status_taxonomy() {
	$labels = array(
		'name'              => esc_html__( 'Service Case Status', 'direktt-service-status' ),
		'singular_name'     => esc_html__( 'Service Case Status', 'direktt-service-status' ),
		'search_items'      => esc_html__( 'Search Case Statuses', 'direktt-service-status' ),
		'all_items'         => esc_html__( 'All Case Statuses', 'direktt-service-status' ),
		'parent_item'       => esc_html__( 'Parent Case Status', 'direktt-service-status' ),
		'parent_item_colon' => esc_html__( 'Parent Case Status:', 'direktt-service-status' ),
		'edit_item'         => esc_html__( 'Edit Case Status', 'direktt-service-status' ),
		'update_item'       => esc_html__( 'Update Case Status', 'direktt-service-status' ),
		'add_new_item'      => esc_html__( 'Add New Case Status', 'direktt-service-status' ),
		'new_item_name'     => esc_html__( 'New Case Status Name', 'direktt-service-status' ),
		'menu_name'         => esc_html__( 'Service Case Status', 'direktt-service-status' ),
	);

	$args = array(
		'hierarchical'      => true,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'case-status' ),
	);

	register_taxonomy( 'case_status', 'direktt_service_case', $args );
}

add_action( 'direktt_setup_admin_menu', 'direktt_add_case_status_submenu' );

function direktt_add_case_status_submenu() {
	add_submenu_page(
		'direktt-dashboard',
		__( 'Service Cases', 'direktt-service-status' ),
		__( 'Service Cases', 'direktt-service-status' ),
		'edit_posts',
		'edit.php?post_type=direktt_service_case',
		null,
		20
	);

	add_submenu_page(
		'direktt-dashboard',
		esc_html__( 'Service Case Status', 'direktt-service-status' ),
		esc_html__( 'Service Case Status', 'direktt-service-status' ),
		'manage_options',
		'edit-tags.php?taxonomy=case_status',
		null,
		21
	);
}

add_action( 'parent_file', 'highlight_direktt_submenu_service_status' );

function highlight_direktt_submenu_service_status( $parent_file ) {
	global $submenu_file, $current_screen, $pagenow;

	if ( $pagenow == 'edit-tags.php' && $current_screen->taxonomy == 'case_status' ) {
		$submenu_file = 'edit-tags.php?taxonomy=case_status';
		$parent_file  = 'direktt-dashboard';
	}

	return $parent_file;
}

add_action( 'set_object_terms', 'direktt_log_case_status_change', 10, 6 );

function direktt_log_case_status_change( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
	if ( $taxonomy !== 'case_status' ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( get_transient( 'direktt_serice_status_more_than_one' ) ) {
		delete_transient( 'direktt_serice_status_more_than_one' );
		return;
	}

	if ( get_transient( 'direktt_service_status_less_than_one' ) ) {
		delete_transient( 'direktt_service_status_less_than_one' );
		return;
	}

	$tt_ids = array_map( 'intval', (array) $tt_ids );

	if ( count( $tt_ids ) > 1 ) {
		set_transient( 'direktt_serice_status_more_than_one', true, 30 );
		$tt_ids = array_slice( $tt_ids, 0, 1 );
		wp_set_object_terms( $object_id, $tt_ids, 'case_status', false );
	}

	if ( count( $tt_ids ) === 0 ) {
		set_transient( 'direktt_service_status_less_than_one', true, 30 );
		$old_term = isset( $old_tt_ids[0] ) ? $old_tt_ids[0] : null;
		wp_set_object_terms( $object_id, $old_term, 'case_status', false );
		return;
	}

	$old_term = isset( $old_tt_ids[0] ) ? $old_tt_ids[0] : null;

	$new_term = isset( $tt_ids[0] ) ? $tt_ids[0] : null;
	if ( $new_term == $old_term ) {
		return;
	}

	$log = get_post_meta( $object_id, 'direktt_service_status_change_log', true ) ?: array();

	if ( $old_term !== null ) {
		global $direktt_user;
		if ( $direktt_user ) {
			$user_id = $direktt_user['direktt_user_id'];
		} else {
			$wp_user         = wp_get_current_user();
			$direktt_user_wp = Direktt_User::get_direktt_user_by_wp_user( $wp_user );
			if ( $direktt_user_wp ) {
				$user_id = $direktt_user_wp['direktt_user_id'];
			} else {
				$user_id = $wp_user->ID;
			}
		}
		$log[] = array(
			'type'     => 'changed',
			'user_id'  => $user_id,
			'old_term' => $old_term,
			'new_term' => $new_term,
			'date'     => current_time( 'mysql' ),
		);

		$post                 = get_post( $object_id );
		$subscription_id      = get_post_meta( $object_id, '_dss_direktt_subscription_id', true );
		$case_change_template = intval( get_option( 'direktt_service_status_case_change_template', 0 ) );
		Direktt_Message::send_message_template(
			array( $subscription_id ),
			$case_change_template,
			array(
				'case-no'    => $post->post_title,
				'date-time'  => current_time( 'mysql' ),
				'old-status' => $old_term ? get_term( $old_term )->name : 'None',
				'new-status' => $old_term ? get_term( $new_term )->name : 'None',
			)
		);
	}
	update_post_meta( $object_id, 'direktt_service_status_change_log', $log );
}

add_action( 'direktt_setup_settings_pages', 'setup_service_status_settings_page' );

function setup_service_status_settings_page() {
	Direktt::add_settings_page(
		array(
			'id'       => 'service-status',
			'label'    => esc_html__( 'Service Status Settings', 'direktt-service-status' ),
			'callback' => 'render_service_status_settings',
			'priority' => 2,
		)
	);
}

function render_service_status_settings() {
	$success = false;

	// Handle form submission
	if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['direktt_admin_service_status_nonce'] ) && wp_verify_nonce( $_POST['direktt_admin_service_status_nonce'], 'direktt_admin_service_status_save' ) ) {
		// update options based on form submission
		update_option( 'direktt_service_status_new_case_template', intval( $_POST['direktt_service_status_new_case_template'] ) );
		update_option( 'direktt_service_status_case_change_template', intval( $_POST['direktt_service_status_case_change_template'] ) );
		update_option( 'direktt_service_status_categories', isset( $_POST['direktt_service_status_categories'] ) ? intval( $_POST['direktt_service_status_categories'] ) : 0 );
		update_option( 'direktt_service_status_tags', isset( $_POST['direktt_service_status_tags'] ) ? intval( $_POST['direktt_service_status_tags'] ) : 0 );
		update_option( 'direktt_service_status_opening_status', isset( $_POST['direktt_service_status_opening_status'] ) ? intval( $_POST['direktt_service_status_opening_status'] ) : 0 );
		update_option( 'direktt_service_status_closing_status', isset( $_POST['direktt_service_status_closing_status'] ) ? intval( $_POST['direktt_service_status_closing_status'] ) : 0 );

		$success = true;
	}

	// Load stored values
	$new_case_template    = get_option( 'direktt_service_status_new_case_template', 0 );
	$case_change_template = get_option( 'direktt_service_status_case_change_template', 0 );
	$categories           = get_option( 'direktt_service_status_categories', 0 );
	$tags                 = get_option( 'direktt_service_status_tags', 0 );
	$opening_status       = get_option( 'direktt_service_status_opening_status', 0 );
	$closing_status       = get_option( 'direktt_service_status_closing_status', 0 );

	// Query for template posts
	$template_args  = array(
		'post_type'      => 'direkttmtemplates',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'meta_query'     => array(
			array(
				'key'     => 'direkttMTType',
				'value'   => array( 'all', 'none' ),
				'compare' => 'IN',
			),
		),
	);
	$template_posts = get_posts( $template_args );

	$all_categories = Direktt_User::get_all_user_categories();
	$all_tags       = Direktt_User::get_all_user_tags();
	$status_options = direktt_service_status_get_status_list();
	?>
	<div class="wrap">
		<?php if ( $success ) : ?>
			<div class="updated notice is-dismissible">
				<p><?php echo esc_html__( 'Settings saved successfully.', 'direktt-service-status' ); ?></p>
			</div>
		<?php endif; ?>
		<form method="post" action="">
			<?php wp_nonce_field( 'direktt_admin_service_status_save', 'direktt_admin_service_status_nonce' ); ?>

			<table class="form-table">
				<tr>
					<th scope="row"><label for="direktt_service_status_new_case_template"><?php echo esc_html__( 'New Case Message Template', 'direktt-service-status' ); ?></label></th>
					<td>
						<select name="direktt_service_status_new_case_template" id="direktt_service_status_new_case_template">
							<option value="0"><?php echo esc_html__( 'Select Template', 'direktt-service-status' ); ?></option>
							<?php foreach ( $template_posts as $post ) : ?>
								<option value="<?php echo esc_attr( $post->ID ); ?>" <?php selected( $new_case_template, $post->ID ); ?>>
									<?php echo esc_html( $post->post_title ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php echo esc_html__( 'In message template you can use', 'direktt-service-status' ); ?> <?php echo esc_html( '#case-no#' ); ?> <?php echo esc_html__( 'which will be replaced with case number', 'direktt-service-status' ); ?></p>
						<p class="description"><?php echo esc_html__( 'and', 'direktt-service-status' ); ?> <?php echo esc_html( '#date-time#' ); ?> <?php echo esc_html__( 'for date and time when case was opened.', 'direktt-service-status' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="direktt_service_status_case_change_template"><?php echo esc_html__( 'Case Status Change Message Template', 'direktt-service-status' ); ?></label></th>
					<td>
						<select name="direktt_service_status_case_change_template" id="direktt_service_status_case_change_template">
							<option value="0"><?php echo esc_html__( 'Select Template', 'direktt-service-status' ); ?></option>
							<?php foreach ( $template_posts as $post ) : ?>
								<option value="<?php echo esc_attr( $post->ID ); ?>" <?php selected( $case_change_template, $post->ID ); ?>>
									<?php echo esc_html( $post->post_title ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php echo esc_html__( 'In message template you can use', 'direktt-service-status' ); ?> <?php echo esc_html( '#case-no#' ); ?> <?php echo esc_html__( 'which will be replaced with case number,', 'direktt-service-status' ); ?></p>
						<p class="description"><?php echo esc_html( '#old-status#' ); ?> <?php echo esc_html__( 'for old status and', 'direktt-service-status' ); ?> <?php echo esc_html( '#new-status#' ); ?> <?php echo esc_html__( 'for new status', 'direktt-service-status' ); ?></p>
						<p class="description"><?php echo esc_html__( 'and', 'direktt-service-status' ); ?> <?php echo esc_html( '#date-time#' ); ?> <?php echo esc_html__( 'for date and time when case status was changed.', 'direktt-service-status' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="direktt_service_status_categories"><?php echo esc_html__( 'Category', 'direktt-service-status' ); ?></label></th>
					<td>
						<select name="direktt_service_status_categories" id="direktt_service_status_categories">
							<option value="0"><?php echo esc_html__( 'Select Category', 'direktt-service-status' ); ?></option>
							<?php foreach ( $all_categories as $category ) : ?>
								<option value="<?php echo esc_attr( $category['value'] ); ?>" <?php selected( $categories, $category['value'] ); ?>>
									<?php echo esc_html( $category['name'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php echo esc_html__( 'Users with this category will be able to open/manage service cases.', 'direktt-service-status' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="direktt_service_status_tags"><?php echo esc_html__( 'Tag', 'direktt-service-status' ); ?></label></th>
					<td>
						<select name="direktt_service_status_tags" id="direktt_service_status_tags">
							<option value="0"><?php echo esc_html__( 'Select Tag', 'direktt-service-status' ); ?></option>
							<?php foreach ( $all_tags as $tag ) : ?>
								<option value="<?php echo esc_attr( $tag['value'] ); ?>" <?php selected( $tags, $tag['value'] ); ?>>
									<?php echo esc_html( $tag['name'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php echo esc_html__( 'Users with this tag will be able to open/manage service cases.', 'direktt-service-status' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="direktt_service_status_opening_status"><?php echo esc_html__( 'Opening Status', 'direktt-service-status' ); ?></label></th>
					<td>
						<select name="direktt_service_status_opening_status" id="direktt_service_status_opening_status">
							<option value="0"><?php echo esc_html__( 'Select Status', 'direktt-service-status' ); ?></option>
							<?php foreach ( $status_options as $option ) : ?>
								<option value="<?php echo esc_attr( $option['value'] ); ?>" <?php selected( $opening_status, $option['value'] ); ?>>
									<?php echo esc_html( $option['name'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php echo esc_html__( 'This status will be assigned to new cases when they are opened.', 'direktt-service-status' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="direktt_service_status_closing_status"><?php echo esc_html__( 'Closing Status', 'direktt-service-status' ); ?></label></th>
					<td>
						<select name="direktt_service_status_closing_status" id="direktt_service_status_closing_status">
							<option value="0"><?php echo esc_html__( 'Select Status', 'direktt-service-status' ); ?></option>
							<?php foreach ( $status_options as $option ) : ?>
								<option value="<?php echo esc_attr( $option['value'] ); ?>" <?php selected( $closing_status, $option['value'] ); ?>>
									<?php echo esc_html( $option['name'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php echo esc_html__( 'This status will be used to mark cases as closed.', 'direktt-service-status' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button( esc_html__( 'Save Settings', 'direktt-service-status' ) ); ?>
		</form>
	</div>
	<?php
}

add_action( 'direktt_setup_profile_tools', 'setup_service_status_profile_tools' );

function setup_service_status_profile_tools() {
	$selected_category = intval( get_option( 'direktt_service_status_categories', 0 ) );
	$selected_tag      = intval( get_option( 'direktt_service_status_tags', 0 ) );

	if ( $selected_category !== 0 ) {
		$category      = get_term( $selected_category, 'direkttusercategories' );
		$category_slug = $category ? $category->slug : '';
	} else {
		$category_slug = '';
	}

	if ( $selected_tag !== 0 ) {
		$tag      = get_term( $selected_tag, 'direkttusertags' );
		$tag_slug = $tag ? $tag->slug : '';
	} else {
		$tag_slug = '';
	}

	Direktt_Profile::add_profile_tool(
		array(
			'id'              => 'service-status-tool',
			'label'           => esc_html__( 'Service Status', 'direktt-service-status' ),
			'callback'        => 'render_service_status_profile_tool',
			'categories'      => $category_slug ? array( $category_slug ) : array(),
			'tags'            => $tag_slug ? array( $tag_slug ) : array(),
			'priority'        => 2,
			'cssEnqueueArray' => array(
				array(
					'handle' => 'jquery-ui-css',
					'src'    => 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css',
				),
			),
			'jsEnqueueArray'  => array(
				array(
					'handle' => 'jquery-ui-autocomplete',
				),
			),
		)
	);
}

function render_service_status_profile_tool() {
	$subscription_id = isset( $_GET['subscriptionId'] ) ? sanitize_text_field( wp_unslash( $_GET['subscriptionId'] ) ) : false;
	$profile_user    = Direktt_User::get_user_by_subscription_id( $subscription_id );
	if ( ! $profile_user ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'User not found.', 'direktt' ) . '</p></div>';
		return;
	}
	$user_id = $profile_user['ID'];

	if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['direktt_service_status_nonce'] ) && wp_verify_nonce( $_POST['direktt_service_status_nonce'], 'direktt_service_status_action' ) ) {
		if ( isset( $_POST['add_service_case'] ) && intval( $_POST['add_service_case'] ) === 1 ) {
			$case_number      = sanitize_text_field( $_POST['case_number'] );
			$case_description = sanitize_textarea_field( $_POST['case_description'] );
			$case_status      = intval( $_POST['case_status'] );

			$new_case = array(
				'post_title'   => $case_number,
				'post_content' => $case_description,
				'post_status'  => 'publish',
				'post_type'    => 'direktt_service_case',
			);
			set_transient( 'dss_temp_id_transient', $subscription_id, 30 );
			$case_id = wp_insert_post( $new_case );

			if ( ! is_wp_error( $case_id ) ) {
				wp_set_object_terms( $case_id, array( $case_status ), 'case_status', false );

				$post = get_post( $case_id );

				$case_opened_flag = get_post_meta( $case_id, '_dss_case_opened_flag', true );
				if ( empty( $case_opened_flag ) ) {
					$new_case_template = intval( get_option( 'direktt_service_status_new_case_template', 0 ) );
					Direktt_Message::send_message_template(
						array( $subscription_id ),
						$new_case_template,
						array(
							'case-no'   => $post->post_title,
							'date-time' => current_time( 'mysql' ),
						)
					);
					update_post_meta( $case_id, '_dss_case_opened_flag', '1' );
				}

				global $direktt_user;
				if ( $direktt_user ) {
					$user_id = $direktt_user['direktt_user_id'];
				} else {
					$wp_user         = wp_get_current_user();
					$direktt_user_wp = Direktt_User::get_direktt_user_by_wp_user( $wp_user );
					if ( $direktt_user_wp ) {
						$user_id = $direktt_user_wp['direktt_user_id'];
					} else {
						$user_id = $wp_user->ID;
					}
				}
				$log = get_post_meta( $case_id, 'direktt_service_status_change_log', true ) ?: array();
				if ( empty( $log ) ) {
					$log[] = array(
						'type'    => 'created',
						'user_id' => $user_id,
						'date'    => current_time( 'mysql' ),
						'status'  => $case_status,
					);
				}
				update_post_meta( $case_id, 'direktt_service_status_change_log', $log );

                $redirect_url = add_query_arg( 'success_flag', '1', $_SERVER['REQUEST_URI'] );
                wp_safe_redirect( $redirect_url );
				exit;
			} else {
                $redirect_url = add_query_arg( 'success_flag', '0', $_SERVER['REQUEST_URI'] );
				wp_safe_redirect( $redirect_url );
				exit;
			}
		}

		if ( isset( $_POST['edit_service_case'] ) && intval( $_POST['edit_service_case'] ) === 1 ) {
			$case_id          = intval( $_POST['case_id'] );
			$case_description = sanitize_textarea_field( $_POST['case_description'] );
			$case_status      = intval( $_POST['case_status'] );

			$case_post = get_post( $case_id );
			if ( $case_post ) {
				wp_update_post(
					array(
						'ID'           => $case_id,
						'post_content' => $case_description,
					)
				);

				wp_set_object_terms( $case_id, array( $case_status ), 'case_status', false );

                $redirect_url = add_query_arg( 'success_flag', '2', $_SERVER['REQUEST_URI'] );
				wp_safe_redirect( $redirect_url );
				exit;
			} else {
                $redirect_url = add_query_arg( 'success_flag', '3', $_SERVER['REQUEST_URI'] );
				wp_safe_redirect( $redirect_url );
				exit;
			}
		}
	}

	$status_options = direktt_service_status_get_status_list();
	$opening_status = intval( get_option( 'direktt_service_status_opening_status', 0 ) );
	$closing_status = intval( get_option( 'direktt_service_status_closing_status', 0 ) );

	$case_list = array();

	$case_list_posts = get_posts(
		array(
			'post_type'      => 'direktt_service_case',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'   => '_dss_direktt_subscription_id',
					'value' => $subscription_id,
				),
			),
		)
	);

	if ( ! empty( $case_list_posts ) ) {
		foreach ( $case_list_posts as $case_post ) {
			$case_status_terms = get_the_terms( $case_post->ID, 'case_status' );
			$case_status_id    = $case_status_terms[0]->term_id;
			if ( $closing_status !== $case_status_id ) {
				$case_list[] = $case_post->ID;
			}
		}
	}

    if ( isset( $_GET['success_flag'] ) ) {
        $success_flag = sanitize_text_field( wp_unslash( $_GET['success_flag'] ) );
        $class = 'notice';
        if ( $success_flag === '0' ) {
            $message = __( 'Error adding service case. Please try again.', 'direktt-service-status' );
            $class .= ' notice-error';
        } elseif ( $success_flag === '1' ) {
            $message = __( 'Service case added successfully.', 'direktt-service-status' );
        } elseif ( $success_flag === '2' ) {
            $message = __( 'Service case updated successfully.', 'direktt-service-status' );
        } else {
            $message = __( 'Error updating service case. Please try again.', 'direktt-service-status' );
            $class .= ' notice-error';
        }

        echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';
    }
	?>
	<script>
		jQuery(function($) {
			$('#add_new_case').off('click').on('click', function(event) {
				event.preventDefault();
				$('.direktt-service-status-wrapper').hide();
				$('.direktt-service-status-case-form').show();
				$('.direktt-service-status-case-form h2').text('<?php echo esc_js( 'Add New Service Case', 'direktt-service-status' ); ?>');
				$('#save-case-form').data('action', 'add');
                $('.notice').remove();
			});

			$('#save-case-form').off('click').on('click', function(event) {
				event.preventDefault();
				var action = $(this).data('action');
				var caseNumber = $('#case-form-number').val().trim();
				var caseDescription = $('#case-form-description').val().trim();
				var caseStatus = $('#case-form-status').val();
				if (caseStatus === '0') {
                    $('#direktt-service-status-alert').addClass('direktt-popup-on');
                    $('#direktt-service-status-alert .direktt-popup-text').text('<?php echo esc_js( __( 'Please select a valid case status.', 'direktt-service-status' ) ); ?>');
					return;
				}

				if (action === 'add') {
					$('<input>').attr({
						type: 'hidden',
						name: 'add_service_case',
						value: '1'
					}).appendTo('form');
					$('<input>').attr({
						type: 'hidden',
						name: 'case_number',
						value: caseNumber
					}).appendTo('form');
					$('<input>').attr({
						type: 'hidden',
						name: 'case_description',
						value: caseDescription
					}).appendTo('form');
					$('<input>').attr({
						type: 'hidden',
						name: 'case_status',
						value: caseStatus
					}).appendTo('form');
					setTimeout(function() {
						$('form').submit();
					}, 500);
				} else if (action === 'edit') {
					$('<input>').attr({
						type: 'hidden',
						name: 'edit_service_case',
						value: '1'
					}).appendTo('form');
					$('<input>').attr({
						type: 'hidden',
						name: 'case_id',
						value: $('#case-form-id').val()
					}).appendTo('form');
					$('<input>').attr({
						type: 'hidden',
						name: 'case_number',
						value: caseNumber
					}).appendTo('form');
					$('<input>').attr({
						type: 'hidden',
						name: 'case_description',
						value: caseDescription
					}).appendTo('form');
					$('<input>').attr({
						type: 'hidden',
						name: 'case_status',
						value: caseStatus
					}).appendTo('form');
					setTimeout(function() {
						$('form').submit();
					}, 500);
				}
			});

			$('#direktt-service-status-alert .direktt-popup-ok').off('click').on('click', function(event) {
				event.preventDefault();
                $('#direktt-service-status-alert').removeClass('direktt-popup-on');
			});

			$('#cancel-case-form').off('click').on('click', function(event) {
				event.preventDefault();
				$('.direktt-service-status-case-form').hide();
				$('.direktt-service-status-wrapper').show();
				$('#case-form-number').val('');
				$('#case-form-number').prop('disabled', false);
				$('#case-form-description').val('');
				$('#search_query').val('');
				$('.form-log-list').empty();
				$('#case-form-status').val(<?php echo esc_js( $opening_status ); ?>);
			});

			$('#search_cases').off('click').on('click', function(e) {
				e.preventDefault();
				var searchQuery = $('input[name="search_query"]').val().trim();

				if (searchQuery === '') {
                    $('#direktt-service-status-alert').addClass('direktt-popup-on');
                    $('#direktt-service-status-alert .direktt-popup-text').text('<?php echo esc_js( __( 'Please enter a service case number to search.', 'direktt-service-status' ) ); ?>');
                    return;
				}

				$.ajax({
					url: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
					method: 'POST',
					data: {
						action: 'direktt_search_service_cases',
						search_query: searchQuery,
						nonce: $('input[name="direktt_service_status_nonce"]').val(),
						subscription_id: "<?php echo esc_js( $subscription_id ); ?>"
					},
					success: function(response) {
						if (response.success) {
							var caseData = response.data;
							caseData = caseData[0];
							$('.direktt-service-status-wrapper').hide();
							$('.direktt-service-status-case-form').show();
							$('.direktt-service-status-case-form h2').text('<?php echo esc_js( 'Edit Service Case', 'direktt-service-status' ); ?>');
							$('#case-form-id').val(caseData.id);
							$('#case-form-number').val(caseData.title);
							$('#case-form-number').prop('disabled', true);
							$('#case-form-description').val(caseData.description);
							$('#case-form-status').val(caseData.status);
							$('.form-log-list').empty();
							var logEntry = '<table class="direktt-service-status-log">';
								logEntry += '<thead>';
									logEntry += '<th>';
										logEntry += 'User';
									logEntry += '</th>';
									logEntry += '<th>';
										logEntry += 'Time';
									logEntry += '</th>';
									logEntry += '<th>';
										logEntry += 'From';
									logEntry += '</th>';
									logEntry += '<th>';
										logEntry += 'To';
									logEntry += '</th>';
								logEntry += '</thead>';
								logEntry += '<tbody>';
								caseData.log.forEach(function(entry) {
									// var logEntry = '';
									if (entry.type === 'changed') {
										var oldTerm = entry.old_term ? entry.old_term : 'None';
										var newTerm = entry.new_term ? entry.new_term : 'None';
										logEntry += '<tr>';
											logEntry += '<td>';
												logEntry += entry.user_name;
											logEntry += '</td>';
											logEntry += '<td>';
												logEntry += entry.date;
											logEntry += '</td>';
											logEntry += '<td>';
												logEntry += oldTerm;
											logEntry += '</td>';
											logEntry += '<td>';
												logEntry += newTerm;
											logEntry += '</td>';
										logEntry += '</tr>';
									} else if (entry.type === 'created') {
										var status = entry.status ? entry.status : 'None';
										logEntry += '<tr>';
											logEntry += '<td>';
												logEntry += '<strong>' + entry.user_name + '</strong>';
												logEntry += '</br><i>(' + entry.user_id + ')</i>';
											logEntry += '</td>';
											logEntry += '<td>';
												logEntry += entry.date;
											logEntry += '</td>';
											logEntry += '<td>';
												logEntry += '/';
											logEntry += '</td>';
											logEntry += '<td>';
												logEntry += status;
											logEntry += '</td>';
										logEntry += '</tr>';
									}
								});
								logEntry += '</tbody>';
							logEntry += '</table>';
							$('.form-log-list').append(logEntry);
							$('#save-case-form').data('action', 'edit');
                            $('.notice').remove();
						} else {
                            $('#direktt-service-status-alert').addClass('direktt-popup-on');
                            $('#direktt-service-status-alert .direktt-popup-text').text(response.data);
						}
					},
					error: function() {
                        $('#direktt-service-status-alert').addClass('direktt-popup-on');
                        $('#direktt-service-status-alert .direktt-popup-text').text('<?php echo esc_js( __( 'Error searching for service cases. Please try again.', 'direktt-service-status' ) ); ?>');
					}
				});
			});

			$('.edit_case').off('click').on('click', function(event) {
				event.preventDefault();
				var case_id = $(this).data('case-id');
				$.ajax({
					url: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
					method: 'POST',
					data: {
						action: 'direktt_search_service_cases_id',
						case_id: case_id,
						nonce: $('input[name="direktt_service_status_nonce"]').val()
					},
					success: function(response) {
						if (response.success) {
							var caseData = response.data[0];
							$('.direktt-service-status-wrapper').hide();
							$('.direktt-service-status-case-form').show();
							$('.direktt-service-status-case-form h2').text('<?php echo esc_js( 'Edit Service Case', 'direktt-service-status' ); ?>');
							$('#case-form-id').val(caseData.id);
							$('#case-form-number').val(caseData.title).prop('disabled', true);
							$('#case-form-description').val(caseData.description);
							$('#case-form-status').val(caseData.status);
							$('.form-log-list').empty();
							var logEntry = '<table class="direktt-service-status-log">';
								logEntry += '<thead>';
									logEntry += '<th>';
										logEntry += 'User';
									logEntry += '</th>';
									logEntry += '<th>';
										logEntry += 'Time';
									logEntry += '</th>';
									logEntry += '<th>';
										logEntry += 'From';
									logEntry += '</th>';
									logEntry += '<th>';
										logEntry += 'To';
									logEntry += '</th>';
								logEntry += '</thead>';
								logEntry += '<tbody>';
								caseData.log.forEach(function(entry) {
									// var logEntry = '';
									if (entry.type === 'changed') {
										var oldTerm = entry.old_term ? entry.old_term : 'None';
										var newTerm = entry.new_term ? entry.new_term : 'None';
										logEntry += '<tr>';
											logEntry += '<td>';
												logEntry += '<strong>' + entry.user_name + '</strong>';
												logEntry += '</br><i>(' + entry.user_id + ')</i>';
											logEntry += '</td>';
											logEntry += '<td>';
												logEntry += entry.date;
											logEntry += '</td>';
											logEntry += '<td>';
												logEntry += oldTerm;
											logEntry += '</td>';
											logEntry += '<td>';
												logEntry += newTerm;
											logEntry += '</td>';
										logEntry += '</tr>';
									} else if (entry.type === 'created') {
										var status = entry.status ? entry.status : 'None';
										logEntry += '<tr>';
											logEntry += '<td>';
												logEntry += '<strong>' + entry.user_name + '</strong>';
												logEntry += '</br><i>(' + entry.user_id + ')</i>';
											logEntry += '</td>';
											logEntry += '<td>';
												logEntry += entry.date;
											logEntry += '</td>';
											logEntry += '<td>';
												logEntry += '/';
											logEntry += '</td>';
											logEntry += '<td>';
												logEntry += status;
											logEntry += '</td>';
										logEntry += '</tr>';
									}
								});
								logEntry += '</tbody>';
							logEntry += '</table>';
							$('.form-log-list').append(logEntry);
							$('#save-case-form').data('action', 'edit');
                            $('.notice').remove();
						} else {
                            $('#direktt-service-status-alert').addClass('direktt-popup-on');
                            $('#direktt-service-status-alert .direktt-popup-text').text(response.data);
						}
					},
					error: function() {
                        $('#direktt-service-status-alert').addClass('direktt-popup-on');
                        $('#direktt-service-status-alert .direktt-popup-text').text('<?php echo esc_js( __( 'Error retrieving service case details. Please try again.', 'direktt-service-status' ) ); ?>');
					}
				});
			});

			var case_list = 
			<?php
			echo wp_json_encode(
				array_values(
					array_map(
						'strval',
						array_map(
							function ( $case_id ) {
											return get_the_title( $case_id );
							},
							$case_list
						)
					)
				)
			);
			?>
							;

			$(document).on('focus', '#search_query', function() {
				var $el = $(this);
				if (!$el.data('ui-autocomplete')) {
					$el.autocomplete({
						source: case_list
					});
				}
			});
		});
	</script>
	<div class="direktt-service-status">
		<div class="direktt-service-status-wrapper">
			<h2><?php echo esc_html__( 'Service Status Management', 'direktt-service-status' ); ?></h2>
			<div class="direktt-service-status-add-new">
				<button id="add_new_case" class="button-large button-primary"><?php echo esc_html__( 'Add New Service Case', 'direktt-service-status' ); ?></button>
			</div>
			<div class="direktt-service-status-search">
				<input type="text" name="search_query" id="search_query" placeholder="<?php echo esc_attr__( 'Service Cases Number', 'direktt-service-status' ); ?>" />
				<button id="search_cases" class="burron-primary button-invert"><?php echo esc_html__( 'Search', 'direktt-service-status' ); ?></button>
			</div>
			<div class="direktt-service-status-cases-list">
				<?php
				if ( ! empty( $case_list ) ) {
					foreach ( $case_list as $case_id ) {
						$case              = get_post( $case_id );
						$case_status_terms = get_the_terms( $case->ID, 'case_status' );
						$case_status       = ( $case_status_terms && ! is_wp_error( $case_status_terms ) ) ? $case_status_terms[0]->name : 'No Status';
						?>
												<div class="case-item">
							<h3><?php echo esc_html( $case->post_title ); ?></h3>
							<div class="direktt-service-status-description"><strong><?php echo esc_html__( 'Description:', 'direktt-service-status' ); ?> </strong><?php echo esc_html( wp_trim_words( $case->post_content, 10, '...' ) ?: '/' ); ?></div>
							<div class="direktt-service-status-status"><strong><?php echo esc_html__( 'Status:', 'direktt-service-status' ); ?> </strong><?php echo esc_html( $case_status ); ?></div>
							<?php
							$log = get_post_meta( $case_id, 'direktt_service_status_change_log', true ) ?: array();
							$log = array_slice( array_reverse( $log ), 0, 1 );
							if ( ! empty( $log ) && is_array( $log ) ) {
								echo '<table class="direktt-service-status-log">';
								echo '<thead>';
									echo '<tr>';
										echo '<th>';
											echo esc_html__( 'User', 'direktt-service-status' );
										echo '</th>';
										echo '<th>';
											echo esc_html__( 'Time', 'direktt-service-status' );
										echo '</th>';
										echo '<th>';
											echo esc_html__( 'From', 'direktt-service-status' );
										echo '</th>';
										echo '<th>';
											echo esc_html__( 'To', 'direktt-service-status' );
										echo '</th>';
									echo '</tr>';
								echo '</thead>';
									echo '<tbody>';
								foreach ( $log as $entry ) {
									$user_id      = $entry['user_id'];
									$direktt_user = Direktt_User::get_user_by_subscription_id( $user_id );
									if ( $direktt_user ) {
										$user_name = $direktt_user['direktt_display_name'];
									} else {
										$user_info = get_userdata( $user_id );
										$user_name = $user_info ? $user_info->user_login : 'Unknown User';
									}
									if ( $entry['type'] === 'changed' ) {
										$old_term = $entry['old_term'] ? get_term( $entry['old_term'] )->name : 'None';
										$new_term = $entry['new_term'] ? get_term( $entry['new_term'] )->name : 'None';
										echo '<tr>';
											echo '<td>';
												echo wp_kses_post( '<strong>' . $user_name . '</strong> <br/><i>' . $user_id . '</i>' );
											echo '</td>';
											echo '<td>';
												echo esc_html( human_time_diff( strtotime( $entry['date'] ) ) . ' ago' );
											echo '</td>';
											echo '<td>';
												echo esc_html( $old_term );
											echo '</td>';
											echo '<td>';
												echo esc_html( $new_term );
											echo '</td>';
										echo '</tr>';
									} else {
										$status = $entry['status'] ? get_term( $entry['status'] )->name : 'None';
										echo '<tr>';
											echo '<td>';
												echo wp_kses_post( '<strong>' . $user_name . '</strong> <br/><i>' . $user_id . '</i>' );
											echo '</td>';
											echo '<td>';
												echo esc_html( human_time_diff( strtotime( $entry['date'] ) ) . ' ago' );
											echo '</td>';
											echo '<td>';
												echo esc_html( '/' );
											echo '</td>';
											echo '<td>';
												echo esc_html( $status );
											echo '</td>';
										echo '</tr>';
									}
								}
									echo '</tbody>';
								echo '</table>';
							}
							?>
							<button class="edit_case" data-case-id="<?php echo esc_attr( $case_id ); ?>"><?php echo esc_html__( 'Edit Case', 'direktt-service-status' ); ?></button>		
						</div>
						<?php
					}
				} else {
					?>
					<h3><?php echo esc_html__( 'There are no open cases for this user.', 'direktt-service-status' ); ?></h3>
					<?php
				}
				?>
			</div>
		</div>
		<div class="direktt-service-status-case-form" style="display: none;">
			<div class="direktt-service-status-case-form-wrapper">
				<form method="post">
					<?php wp_nonce_field( 'direktt_service_status_action', 'direktt_service_status_nonce' ); ?>
					<h2></h2>
					<div>
						<label for="case-form-number"><?php echo esc_html__( 'Service Case Number', 'direktt-service-status' ); ?></label>
						<input type="text" id="case-form-number" placeholder="<?php echo esc_attr__( 'Service Case Number', 'direktt-service-status' ); ?>" />
					</div>
					<div>
						<label for="case-form-description"><?php echo esc_html__( 'Service Case Description', 'direktt-service-status' ); ?></label>
						<textarea id="case-form-description" placeholder="<?php echo esc_attr__( 'Service Case Description', 'direktt-service-status' ); ?>" rows="6"></textarea>
					</div>
					<div>
						<label for="case-form-status"><?php echo esc_html__( 'Service Case Status', 'direktt-service-status' ); ?></label>
						<select id="case-form-status">
							<option value="0" <?php selected( $opening_status, 0 ); ?>><?php echo esc_html__( 'Select Status', 'direktt-service-status' ); ?></option>
							<?php foreach ( $status_options as $option ) : ?>
								<option value="<?php echo esc_attr( $option['value'] ); ?>" <?php selected( $opening_status, $option['value'] ); ?>>
									<?php echo esc_html( $option['name'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="form-buttons">
						<button id="save-case-form" class="button button-primary button-large"><?php echo esc_html__( 'Save Service Case', 'direktt-service-status' ); ?></button>
						<button id="cancel-case-form" class="button-invert button-dark-gray"><?php echo esc_html__( 'Cancel', 'direktt-service-status' ); ?></button>
					</div>
					<h3>Activity log</h3>
					<div class="form-log-list"></div>
					<input type="hidden" id="case-form-id" value="" />
				</form>
			</div>
		</div>
		<?php
        echo Direktt_Public::direktt_render_alert_popup( 'direktt-service-status-alert', '' );
        ?>
	</div>
	<?php
}

function direktt_service_status_get_status_list() {
	$status_terms   = get_terms(
		array(
			'taxonomy'   => 'case_status',
			'hide_empty' => false,
		)
	);
	$status_options = array();
	foreach ( $status_terms as $term ) {
		$status_options[] = array(
			'value' => $term->term_id,
			'name'  => $term->name,
		);
	}
	return $status_options;
}

add_action( 'wp_ajax_direktt_search_service_cases', 'handle_direktt_search_service_cases' );

function handle_direktt_search_service_cases() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'direktt_service_status_action' ) ) {
		wp_send_json_error( esc_html__( 'Invalid nonce.', 'direktt-service-status' ) );
		wp_die();
	}

	$search_query = isset( $_POST['search_query'] ) ? sanitize_text_field( wp_unslash( $_POST['search_query'] ) ) : '';

	if ( empty( $search_query ) ) {
		wp_send_json_error( esc_html__( 'Search query is empty.', 'direktt-service-status' ) );
		wp_die();
	}

	$args = array(
		'post_type'      => 'direktt_service_case',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'title'          => $search_query,
	);

	$cases = get_posts( $args );
	$cases = array_reverse( $cases );

	if ( empty( $cases ) ) {
		wp_send_json_error( esc_html__( 'No service case found.', 'direktt-service-status' ) );
		wp_die();
	}

	$results         = array();
	$case            = $cases[0];
	$subscription_id = get_post_meta( $case->ID, '_dss_direktt_subscription_id', true );
	if ( isset( $_POST['subscription_id'] ) ) {
		$profile_subscription_id = sanitize_text_field( $_POST['subscription_id'] );
		if ( $subscription_id !== $profile_subscription_id ) {
			wp_send_json_error( esc_html__( 'No service case found.', 'direktt-service-status' ) );
			wp_die();
		}
	}
	$case_status_terms = get_the_terms( $case->ID, 'case_status' );
	$case_status_id    = ( $case_status_terms && ! is_wp_error( $case_status_terms ) ) ? $case_status_terms[0]->term_id : 0;
	$log               = get_post_meta( $case->ID, 'direktt_service_status_change_log', true ) ?: array();
	$log               = array_reverse( $log );
	$log_entries       = array();
	foreach ( $log as $entry ) {
		$user_id      = $entry['user_id'];
		$direktt_user = Direktt_User::get_user_by_subscription_id( $user_id );
		if ( $direktt_user ) {
			$user_name = $direktt_user['direktt_display_name'];
		} else {
			$user_info = get_userdata( $user_id );
			$user_name = $user_info ? $user_info->user_login : 'Unknown User';
		}
		$entry['user_name'] = $user_name;
		$entry['user_id']   = $user_id;
		$entry['date']      = human_time_diff( strtotime( $entry['date'] ) ) . ' ago';
		if ( $entry['type'] !== 'created' ) {
			$old_term          = get_term( $entry['old_term'], 'case_status' );
			$new_term          = get_term( $entry['new_term'], 'case_status' );
			$entry['old_term'] = $old_term->name;
			$entry['new_term'] = $new_term->name;
		} else {
			$status          = get_term( $entry['status'], 'case_status' );
			$entry['status'] = $status->name;
		}
		$log_entries[] = $entry;
	}
	$log = $log_entries;

	$results[] = array(
		'id'          => $case->ID,
		'title'       => $case->post_title,
		'userId'      => $subscription_id,
		'description' => $case->post_content,
		'status'      => $case_status_id,
		'log'         => $log,
	);

	wp_send_json_success( $results );
	wp_die();
}

add_action( 'wp_ajax_direktt_search_service_cases_id', 'handle_direktt_search_service_cases_id' );

function handle_direktt_search_service_cases_id() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'direktt_service_status_action' ) ) {
		wp_send_json_error( esc_html__( 'Invalid nonce.', 'direktt-service-status' ) );
		wp_die();
	}

	$case_id = isset( $_POST['case_id'] ) ? sanitize_text_field( wp_unslash( $_POST['case_id'] ) ) : '';

	if ( empty( $case_id ) ) {
		wp_send_json_error( esc_html__( 'Search query is empty.', 'direktt-service-status' ) );
		wp_die();
	}

	$case = get_post( $case_id );

	if ( empty( $case ) ) {
		wp_send_json_error( esc_html__( 'No service case found.', 'direktt-service-status' ) );
		wp_die();
	}

	$results           = array();
	$subscription_id   = get_post_meta( $case->ID, '_dss_direktt_subscription_id', true );
	$case_status_terms = get_the_terms( $case->ID, 'case_status' );
	$case_status_id    = ( $case_status_terms && ! is_wp_error( $case_status_terms ) ) ? $case_status_terms[0]->term_id : 0;
	$log               = get_post_meta( $case->ID, 'direktt_service_status_change_log', true ) ?: array();
	$log               = array_reverse( $log );
	$log_entries       = array();
	foreach ( $log as $entry ) {
		$user_id      = $entry['user_id'];
		$direktt_user = Direktt_User::get_user_by_subscription_id( $user_id );
		if ( $direktt_user ) {
			$user_name = $direktt_user['direktt_display_name'];
		} else {
			$user_info = get_userdata( $user_id );
			$user_name = $user_info ? $user_info->user_login : 'Unknown User';
		}
		$entry['user_name'] = $user_name;
		$entry['user_id']   = $user_id;
		$entry['date']      = human_time_diff( strtotime( $entry['date'] ) ) . ' ago';
		if ( $entry['type'] !== 'created' ) {
			$old_term          = get_term( $entry['old_term'], 'case_status' );
			$new_term          = get_term( $entry['new_term'], 'case_status' );
			$entry['old_term'] = $old_term->name;
			$entry['new_term'] = $new_term->name;
		} else {
			$status          = get_term( $entry['status'], 'case_status' );
			$entry['status'] = $status->name;
		}
		$log_entries[] = $entry;
	}
	$log = $log_entries;

	$results[] = array(
		'id'          => $case->ID,
		'title'       => $case->post_title,
		'userId'      => $subscription_id,
		'description' => $case->post_content,
		'status'      => $case_status_id,
		'log'         => $log,
	);

	wp_send_json_success( $results );
	wp_die();
}

add_shortcode( 'direktt_service_case', 'direktt_add_service_case_shortcode' );

function direktt_add_service_case_shortcode() {
	global $direktt_user;
	if ( ! $direktt_user ) {
		return;
	}
	$subscription_id     = $direktt_user['direktt_user_id'];
	$profile_user        = Direktt_User::get_user_by_subscription_id( $subscription_id );
	$post_id             = $profile_user['ID'];
	$assigned_categories = wp_get_post_terms( $post_id, 'direkttusercategories', array( 'fields' => 'ids' ) );
	$assigned_tags       = wp_get_post_terms( $post_id, 'direkttusertags', array( 'fields' => 'ids' ) );
	$categories          = intval( get_option( 'direktt_service_status_categories', 0 ) );
	$tags                = intval( get_option( 'direktt_service_status_tags', 0 ) );
	$eligible            = Direktt_User::is_direktt_admin() || in_array( $categories, $assigned_categories ) || in_array( $tags, $assigned_tags );
	$status_options      = direktt_service_status_get_status_list();
	$opening_status      = intval( get_option( 'direktt_service_status_opening_status', 0 ) );
	$closing_status      = intval( get_option( 'direktt_service_status_closing_status', 0 ) );
	global $enqueue_direktt_case_script;
	$enqueue_direktt_case_script = true;
	ob_start();
	echo '<div id="direktt-profile-wrapper">';
	echo '<div id="direktt-profile">';
	echo '<div id="direktt-profile-data" class="direktt-profile-data-service-status-tool direktt-service">';
	echo '<h2>' . esc_html__( 'Service Status Management', 'direktt-service-status' ) . '</h2>';
	if ( $eligible ) {
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['direktt_service_status_nonce'] ) && wp_verify_nonce( $_POST['direktt_service_status_nonce'], 'direktt_service_status_action' ) ) {
			if ( isset( $_POST['add_service_case'] ) && intval( $_POST['add_service_case'] ) === 1 ) {
				$case_number      = sanitize_text_field( $_POST['case_number'] );
				$case_user_id     = sanitize_text_field( $_POST['case_user_id'] );
				$case_description = sanitize_textarea_field( $_POST['case_description'] );
				$case_status      = intval( $_POST['case_status'] );

				$new_case = array(
					'post_title'   => $case_number,
					'post_content' => $case_description,
					'post_status'  => 'publish',
					'post_type'    => 'direktt_service_case',
				);
				if ( strlen( $case_user_id ) > 6 ) {
					$user_subscription_id = $case_user_id;
				} else {
					$profile_user         = Direktt_User::get_user_by_membership_id( $case_user_id );
					$user_subscription_id = $profile_user['direktt_user_id'];
				}
				set_transient( 'dss_temp_id_transient', $user_subscription_id, 30 );
				$case_id = wp_insert_post( $new_case );

				if ( ! is_wp_error( $case_id ) ) {
					wp_set_object_terms( $case_id, array( $case_status ), 'case_status', false );

					$post = get_post( $case_id );

					$case_opened_flag = get_post_meta( $case_id, '_dss_case_opened_flag', true );
					if ( empty( $case_opened_flag ) ) {
						$new_case_template = intval( get_option( 'direktt_service_status_new_case_template', 0 ) );
						Direktt_Message::send_message_template(
							array( $user_subscription_id ),
							$new_case_template,
							array(
								'case-no'   => $post->post_title,
								'date-time' => current_time( 'mysql' ),
							)
						);
						update_post_meta( $case_id, '_dss_case_opened_flag', '1' );
					}

					$log = get_post_meta( $case_id, 'direktt_service_status_change_log', true ) ?: array();
					$log = array_reverse( $log );
					if ( empty( $log ) ) {
						$log[] = array(
							'type'    => 'created',
							'user_id' => $subscription_id,
							'date'    => current_time( 'mysql' ),
							'status'  => $case_status,
						);
					}
					update_post_meta( $case_id, 'direktt_service_status_change_log', $log );

                    $redirect_url = add_query_arg( 'success_flag', '1', $_SERVER['REQUEST_URI'] );
					wp_safe_redirect( $redirect_url );
					exit;
				} else {
                    $redirect_url = add_query_arg( 'success_flag', '0', $_SERVER['REQUEST_URI'] );
					wp_safe_redirect( $redirect_url );
					exit;
				}
			}

			if ( isset( $_POST['edit_service_case'] ) && intval( $_POST['edit_service_case'] ) === 1 ) {
				$case_id          = intval( $_POST['case_id'] );
				$case_description = sanitize_textarea_field( $_POST['case_description'] );
				$case_status      = intval( $_POST['case_status'] );

				$case_post = get_post( $case_id );
				if ( $case_post ) {
					wp_update_post(
						array(
							'ID'           => $case_id,
							'post_content' => $case_description,
						)
					);

					wp_set_object_terms( $case_id, array( $case_status ), 'case_status', false );

                    $redirect_url = add_query_arg( 'success_flag', '2', $_SERVER['REQUEST_URI'] );
					wp_safe_redirect( $redirect_url );
					exit;
				} else {
                    $redirect_url = add_query_arg( 'success_flag', '3', $_SERVER['REQUEST_URI'] );
					wp_safe_redirect( $redirect_url );
					exit;
				}
			}
		}
		$case_list = array();

		$case_list_posts = get_posts(
			array(
				'post_type'      => 'direktt_service_case',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			)
		);

		if ( ! empty( $case_list_posts ) ) {
			foreach ( $case_list_posts as $case_post ) {
				$case_status_terms = get_the_terms( $case_post->ID, 'case_status' );
				$case_status_id    = $case_status_terms[0]->term_id;
				if ( $closing_status !== $case_status_id ) {
					$case_list[] = $case_post->ID;
				}
			}
		}

		$all_ids = array();
		$users   = get_posts(
			array(
				'post_type'      => 'direkttusers',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		foreach ( $users as $user_id ) {
			$all_ids[] = get_post_meta( $user_id, 'direktt_user_id', true );
			$all_ids[] = get_post_meta( $user_id, 'direktt_membership_id', true );
		}
		?>
		<script>
			jQuery(function($) {
				var ids = <?php echo wp_json_encode( array_values( array_map( 'strval', $all_ids ) ) ); ?>;

				$('#my_cases').off('click').on('click', function(event) {
					event.preventDefault();
					$('.my-cases').show();
					$('.direktt-service-status').hide();
					$('#go-back').show();
                    $('.notice').remove();
				});

				$('#add_new_case').off('click').on('click', function(event) {
					event.preventDefault();
					$('.direktt-service-status-wrapper').hide();
					$('.direktt-service-status-case-form').show();
					$('.direktt-service-status-case-form h2').text('<?php echo esc_js( 'Add New Service Case', 'direktt-service-status' ); ?>');
					$('#save-case-form').data('action', 'add');
                    $('.notice').remove();
				});

				$('#save-case-form').off('click').on('click', function(event) {
					event.preventDefault();
					var action = $(this).data('action');
					var caseUserId = $('#case-form-user-id').val();
					var caseNumber = $('#case-form-number').val().trim();
					var caseDescription = $('#case-form-description').val().trim();
					var caseStatus = $('#case-form-status').val();
					if (caseStatus === '0') {
                        $('#direktt-service-status-alert').addClass('direktt-popup-on');
                        $('#direktt-service-status-alert .direktt-popup-text').text('<?php echo esc_js( __( 'Please select valid case status.', 'direktt-service-status' ) ); ?>');
						return;
					}
					if (!ids.includes(caseUserId)) {
						$('#direktt-service-status-alert').addClass('direktt-popup-on');
                        $('#direktt-service-status-alert .direktt-popup-text').text('<?php echo esc_js( 'Please enter valid Subscription/Membership ID.', 'direktt-service-status' ); ?>');
						return;
					}

					if (action === 'add') {
						$('<input>').attr({
							type: 'hidden',
							name: 'add_service_case',
							value: '1'
						}).appendTo('form');
						$('<input>').attr({
							type: 'hidden',
							name: 'case_number',
							value: caseNumber
						}).appendTo('form');
						$('<input>').attr({
							type: 'hidden',
							name: 'case_user_id',
							value: caseUserId
						}).appendTo('form');
						$('<input>').attr({
							type: 'hidden',
							name: 'case_description',
							value: caseDescription
						}).appendTo('form');
						$('<input>').attr({
							type: 'hidden',
							name: 'case_status',
							value: caseStatus
						}).appendTo('form');
						setTimeout(function() {
							$('form').submit();
						}, 500);
					} else if (action === 'edit') {
						$('<input>').attr({
							type: 'hidden',
							name: 'edit_service_case',
							value: '1'
						}).appendTo('form');
						$('<input>').attr({
							type: 'hidden',
							name: 'case_id',
							value: $('#case-form-id').val()
						}).appendTo('form');
						$('<input>').attr({
							type: 'hidden',
							name: 'case_number',
							value: caseNumber
						}).appendTo('form');
						$('<input>').attr({
							type: 'hidden',
							name: 'case_user_id',
							value: caseUserId
						}).appendTo('form');
						$('<input>').attr({
							type: 'hidden',
							name: 'case_description',
							value: caseDescription
						}).appendTo('form');
						$('<input>').attr({
							type: 'hidden',
							name: 'case_status',
							value: caseStatus
						}).appendTo('form');
						setTimeout(function() {
							$('form').submit();
						}, 500);
					}
				});

				$('#direktt-service-status-alert .direktt-popup-ok').off('click').on('click', function(event) {
					event.preventDefault();
					$('#direktt-service-status-alert').removeClass('direktt-popup-on');
				});

				$('#cancel-case-form').off('click').on('click', function(event) {
					event.preventDefault();
					$('.direktt-service-status-case-form').hide();
					$('.direktt-service-status-wrapper').show();
					$('#case-form-number').val('');
					$('#case-form-number').prop('disabled', false);
					$('#case-form-user-id').val('');
					$('#case-form-user-id').prop('disabled', false);
					$('#case-form-description').val('');
					$('#search_query').val('');
					$('.form-log-list').empty();
					$('#case-form-status').val(<?php echo esc_js( $opening_status ); ?>);
				});

				$('#search_cases').off('click').on('click', function(e) {
					e.preventDefault();
					var searchQuery = $('input[name="search_query"]').val().trim();

					if (searchQuery === '') {
                        $('#direktt-service-status-alert').addClass('direktt-popup-on');
                        $('#direktt-service-status-alert .direktt-popup-text').text('<?php echo esc_js( __( 'Please enter a service case number to search.', 'direktt-service-status' ) ); ?>');
                        return;
					}

					$.ajax({
						url: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
						method: 'POST',
						data: {
							action: 'direktt_search_service_cases',
							search_query: searchQuery,
							nonce: $('input[name="direktt_service_status_nonce"]').val()
						},
						success: function(response) {
							if (response.success) {
								var caseData = response.data;
								caseData = caseData[0];
								$('.direktt-service-status-wrapper').hide();
								$('.direktt-service-status-case-form').show();
								$('.direktt-service-status-case-form h2').text('<?php echo esc_js( 'Edit Service Case', 'direktt-service-status' ); ?>');
								$('#case-form-id').val(caseData.id);
								$('#case-form-number').val(caseData.title);
								$('#case-form-number').prop('disabled', true);
								$('#case-form-user-id').val(caseData.userId);
								$('#case-form-user-id').prop('disabled', true);
								$('#case-form-description').val(caseData.description);
								$('#case-form-status').val(caseData.status);
								$('.form-log-list').empty();
								var logEntry = '<table class="direktt-service-status-log">';
									logEntry += '<tbody>';
									caseData.log.forEach(function(entry) {
										// var logEntry = '';
										if (entry.type === 'changed') {
											var oldTerm = entry.old_term ? entry.old_term : 'None';
											var newTerm = entry.new_term ? entry.new_term : 'None';
											logEntry += '<tr>';
												logEntry += '<td>';
													logEntry += '<strong>' + entry.user_name + '</strong>';
													logEntry += '</br><i>(' + entry.user_id + ')</i>';
												logEntry += '</td>';
												logEntry += '<td>';
													logEntry += entry.date;
												logEntry += '</td>';
												logEntry += '<td>';
													logEntry += oldTerm;
												logEntry += '</td>';
												logEntry += '<td>';
													logEntry += newTerm;
												logEntry += '</td>';
											logEntry += '</tr>';
										} else if (entry.type === 'created') {
											var status = entry.status ? entry.status : 'None';
											logEntry += '<tr>';
												logEntry += '<td>';
													logEntry += '<strong>' + entry.user_name + '</strong>';
													logEntry += '</br><i>(' + entry.user_id + ')</i>';
												logEntry += '</td>';
												logEntry += '<td>';
													logEntry += entry.date;
												logEntry += '</td>';
												logEntry += '<td>';
													logEntry += '/';
												logEntry += '</td>';
												logEntry += '<td>';
													logEntry += status;
												logEntry += '</td>';
											logEntry += '</tr>';
										}
									});
									logEntry += '</tbody>';
								logEntry += '</table>';
								$('.form-log-list').append(logEntry);
								$('#save-case-form').data('action', 'edit');
                                $('.notice').remove();
							} else {
                                $('#direktt-service-status-alert').addClass('direktt-popup-on');
                                $('#direktt-service-status-alert .direktt-popup-text').text(response.data);
							}
						},
						error: function() {
                            $('#direktt-service-status-alert').addClass('direktt-popup-on');
                            $('#direktt-service-status-alert .direktt-popup-text').text('<?php echo esc_js( __( 'Error searching for service cases. Please try again.', 'direktt-service-status' ) ); ?>');
						}
					});
				});

				$('.edit_case').off('click').on('click', function(event) {
					event.preventDefault();
					var case_id = $(this).data('case-id');
					$.ajax({
						url: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
						method: 'POST',
						data: {
							action: 'direktt_search_service_cases_id',
							case_id: case_id,
							nonce: $('input[name="direktt_service_status_nonce"]').val()
						},
						success: function(response) {
							if (response.success) {
								var caseData = response.data[0];
								$('.direktt-service-status-wrapper').hide();
								$('.direktt-service-status-case-form').show();
								$('.direktt-service-status-case-form h2').text('<?php echo esc_js( 'Edit Service Case', 'direktt-service-status' ); ?>');
								$('#case-form-id').val(caseData.id);
								$('#case-form-number').val(caseData.title).prop('disabled', true);
								$('#case-form-user-id').val(caseData.userId);
								$('#case-form-user-id').prop('disabled', true);
								$('#case-form-description').val(caseData.description);
								$('#case-form-status').val(caseData.status);
								$('.form-log-list').empty();
								var logEntry = '<table class="direktt-service-status-log">';
								logEntry += '<thead>';
									logEntry += '<th>';
										logEntry += 'User';
									logEntry += '</th>';
									logEntry += '<th>';
										logEntry += 'Time';
									logEntry += '</th>';
									logEntry += '<th>';
										logEntry += 'From';
									logEntry += '</th>';
									logEntry += '<th>';
										logEntry += 'To';
									logEntry += '</th>';
								logEntry += '</thead>';
								logEntry += '<tbody>';
								caseData.log.forEach(function(entry) {
									// var logEntry = '';
									if (entry.type === 'changed') {
										var oldTerm = entry.old_term ? entry.old_term : 'None';
										var newTerm = entry.new_term ? entry.new_term : 'None';
										logEntry += '<tr>';
											logEntry += '<td>';
												logEntry += '<strong>' + entry.user_name + '</strong>';
												logEntry += '</br><i>(' + entry.user_id + ')</i>';
											logEntry += '</td>';
											logEntry += '<td>';
												logEntry += entry.date;
											logEntry += '</td>';
											logEntry += '<td>';
												logEntry += oldTerm;
											logEntry += '</td>';
											logEntry += '<td>';
												logEntry += newTerm;
											logEntry += '</td>';
										logEntry += '</tr>';
									} else if (entry.type === 'created') {
										var status = entry.status ? entry.status : 'None';
										logEntry += '<tr>';
											logEntry += '<td>';
												logEntry += '<strong>' + entry.user_name + '</strong>';
												logEntry += '</br><i>(' + entry.user_id + ')</i>';
											logEntry += '</td>';
											logEntry += '<td>';
												logEntry += entry.date;
											logEntry += '</td>';
											logEntry += '<td>';
												logEntry += '/';
											logEntry += '</td>';
											logEntry += '<td>';
												logEntry += status;
											logEntry += '</td>';
										logEntry += '</tr>';
									}
								});
								logEntry += '</tbody>';
							logEntry += '</table>';
								$('.form-log-list').append(logEntry);
								$('#save-case-form').data('action', 'edit');
                                $('.notice').remove();
							} else {
                                $('#direktt-service-status-alert').addClass('direktt-popup-on');
                                $('#direktt-service-status-alert .direktt-popup-text').text(response.data);
							}
						},
						error: function() {
                            $('#direktt-service-status-alert').addClass('direktt-popup-on');
                            $('#direktt-service-status-alert .direktt-popup-text').text('<?php echo esc_js( __( 'Error retrieving service case details. Please try again.', 'direktt-service-status' ) ); ?>');
						}
					});
				});

				var case_list = 
				<?php
				echo wp_json_encode(
					array_values(
						array_map(
							'strval',
							array_map(
								function ( $case_id ) {
                                    return get_the_title( $case_id );
								},
								$case_list
							)
						)
					)
				);
				?>;

				$(document).on('focus', '#search_query', function() {
					var $el = $(this);
					if (!$el.data('ui-autocomplete')) {
						$el.autocomplete({
							source: case_list
						});
					}
				});

				$(document).on('focus', '#case-form-user-id', function() {
					var $el = $(this);
					if (!$el.data('ui-autocomplete')) {
						$el.autocomplete({
							source: ids
						});
					}
				});
			});
		</script>
		<div class="direktt-service-status">
			<div class="direktt-service-status-wrapper">
                <?php
                if ( isset( $_GET['success_flag'] ) ) {
                    $success_flag = sanitize_text_field( wp_unslash( $_GET['success_flag'] ) );
                    $class = 'notice';
                    if ( $success_flag === '1' ) {
                        $message = __( 'Service case added successfully.', 'direktt-service-status' );
                    } elseif ( $success_flag === '0' ) {
                        $message = __( 'Error adding service case. Please try again.', 'direktt-service-status' );
                        $class .= ' notice-error';
                    } elseif ( $success_flag === '2' ) {
                        $message = __( 'Service case updated successfully.', 'direktt-service-status' );
                    } elseif ( $success_flag === '3' ) {
                        $message = __( 'Error updating service case. Please try again.', 'direktt-service-status' );
                        $class .= ' notice-error';
                    }

                    echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';
                }
                ?>
				<div class="direktt-service-status-add-new">
					<button id="add_new_case" class="button-large button-primary"><?php echo esc_html__( 'Add New Service Case', 'direktt-service-status' ); ?></button>
				</div>
				<div class="direktt-service-status-case-my-cases">
					<button id="my_cases"><?php echo esc_html__( 'My Cases', 'direktt-service-status' ); ?></button>
				</div>
				<div class="direktt-service-status-search">
					<input type="text" name="search_query" id="search_query" placeholder="<?php echo esc_attr__( 'Service Cases Number', 'direktt-service-status' ); ?>" />
					<button id="search_cases"><?php echo esc_html__( 'Search', 'direktt-service-status' ); ?></button>
				</div>
				<div class="direktt-service-status-cases-list">
					<?php
					if ( ! empty( $case_list ) ) {
						foreach ( $case_list as $case_id ) {
							$case              = get_post( $case_id );
							$case_status_terms = get_the_terms( $case_id, 'case_status' );
							$case_status_id    = $case_status_terms[0]->term_id;
							$case_status       = ( $case_status_terms && ! is_wp_error( $case_status_terms ) ) ? $case_status_terms[0]->name : 'No Status';
							$case_user_id      = get_post_meta( $case_id, '_dss_direktt_subscription_id', true );
							$profile_user      = Direktt_User::get_user_by_subscription_id( $case_user_id );
							$display_name      = $profile_user['direktt_display_name'];
							?>
							<div class="case-item">
								<div div class="direktt-service-status-user"><strong><?php echo esc_html__( 'User:', 'direktt-service-status' ); ?> </strong><?php echo esc_html( $display_name ) . ' (' . esc_html( $case_user_id ) . ')'; ?></div>
								<h3><?php echo esc_html( $case->post_title ); ?></h3>
								<div div class="direktt-service-status-description"><strong><?php echo esc_html__( 'Description:', 'direktt-service-status' ); ?> </strong><?php echo esc_html( wp_trim_words( $case->post_content, 10, '...' ) ?: '/' ); ?></div>
								<div div class="direktt-service-status-status"><strong><?php echo esc_html__( 'Status:', 'direktt-service-status' ); ?> </strong><?php echo esc_html( $case_status ); ?></div>
								<?php
								$log = get_post_meta( $case_id, 'direktt_service_status_change_log', true ) ?: array();
								// $log = array_reverse( $log );
								if ( ! empty( $log ) && is_array( $log ) ) {
									$entry = $log[ count( $log ) - 1 ];
									echo '<table class="direktt-service-status-log">';
									echo '<thead>';
										echo '<tr>';
											echo '<th>';
												echo esc_html__( 'User', 'direktt-service-status' );
											echo '</th>';
											echo '<th>';
												echo esc_html__( 'Time', 'direktt-service-status' );
											echo '</th>';
											echo '<th>';
												echo esc_html__( 'From', 'direktt-service-status' );
											echo '</th>';
											echo '<th>';
												echo esc_html__( 'To', 'direktt-service-status' );
											echo '</th>';
										echo '</tr>';
									echo '</thead>';
									$user_id      = $entry['user_id'];
									$direktt_user = Direktt_User::get_user_by_subscription_id( $user_id );
									if ( $direktt_user ) {
										$user_name = '<strong>' . $direktt_user['direktt_display_name'] . "</strong> <br/><i>($user_id)</i>";
									} else {
										$user_info = get_userdata( $user_id );
										$user_name = $user_info ? $user_info->user_login : '<strong>Unknown User</strong> <br/><i>(Unknown Id)</i>';
									}
									if ( $entry['type'] === 'changed' ) {
										$old_term = $entry['old_term'] ? get_term( $entry['old_term'] )->name : 'None';
										$new_term = $entry['new_term'] ? get_term( $entry['new_term'] )->name : 'None';
										echo '<tr>';
											echo '<td>';
												echo wp_kses_post( $user_name );
											echo '</td>';
											echo '<td>';
												echo esc_html( human_time_diff( strtotime( $entry['date'] ) ) . ' ago' );
											echo '</td>';
											echo '<td>';
												echo esc_html( $old_term );
											echo '</td>';
											echo '<td>';
												echo esc_html( $new_term );
											echo '</td>';
										echo '</tr>';
									} else {
										$status = $entry['status'] ? get_term( $entry['status'] )->name : 'None';
										echo '<tr>';
											echo '<td>';
												echo wp_kses_post( $user_name );
											echo '</td>';
											echo '<td>';
												echo esc_html( human_time_diff( strtotime( $entry['date'] ) ) . ' ago' );
											echo '</td>';
											echo '<td>';
												echo esc_html( '/' );
											echo '</td>';
											echo '<td>';
												echo esc_html( $status );
											echo '</td>';
										echo '</tr>';
									}
									echo '</table>';
								}
								?>
								<button class="edit_case" data-case-id="<?php echo esc_attr( $case_id ); ?>"><?php echo esc_html__( 'Edit Case', 'direktt-service-status' ); ?></button>
							</div>
							<?php
						}
					} else {
						?>
						<h3><?php echo esc_html__( 'There are no open cases.', 'direktt-service-status' ); ?></h3>
						<?php
					}
					?>
				</div>
			</div>
			<div class="direktt-service-status-case-form" style="display: none;">
				<div class="direktt-service-status-case-form-wrapper">
					<form method="post">
						<?php wp_nonce_field( 'direktt_service_status_action', 'direktt_service_status_nonce' ); ?>
						<h2></h2>
						<div>
							<label for="case-form-number"><?php echo esc_html__( 'Service Case Number', 'direktt-service-status' ); ?></label>
							<input type="text" id="case-form-number" placeholder="<?php echo esc_attr__( 'Service Case Number', 'direktt-service-status' ); ?>" />
						</div>
						<div>
							<label for="case-form-user-id"><?php echo esc_html__( 'Subscription/Membership ID', 'direktt-service-status' ); ?></label>
							<input type="text" id="case-form-user-id" placeholder="<?php echo esc_attr__( 'Subscription/Membership ID', 'direktt-service-status' ); ?>" />
						</div>
						<div>
							<label for="case-form-description"><?php echo esc_html__( 'Service Case Description', 'direktt-service-status' ); ?></label>
							<textarea id="case-form-description" placeholder="<?php echo esc_attr__( 'Service Case Description', 'direktt-service-status' ); ?>"></textarea>
						</div>
						<div>
							<label for="case-form-status"><?php echo esc_html__( 'Service Case Status', 'direktt-service-status' ); ?></label>
							<select id="case-form-status">
								<option value="0" <?php selected( $opening_status, 0 ); ?>><?php echo esc_html__( 'Select Status', 'direktt-service-status' ); ?></option>
								<?php foreach ( $status_options as $option ) : ?>
									<option value="<?php echo esc_attr( $option['value'] ); ?>" <?php selected( $opening_status, $option['value'] ); ?>>
										<?php echo esc_html( $option['name'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="form-log-list"></div>
						<div class="form-buttons">
							<button id="save-case-form" class="button button-large"><?php echo esc_html__( 'Save Service Case', 'direktt-service-status' ); ?></button>
							<button id="cancel-case-form" class="button button-invert"><?php echo esc_html__( 'Cancel', 'direktt-service-status' ); ?></button>
						</div>
						<input type="hidden" id="case-form-id" value="" />
					</form>
				</div>
			</div>
            <?php
            echo Direktt_Public::direktt_render_alert_popup( 'direktt-service-status-alert', '' );
            ?>
		</div>
		<?php
	}
	if ( $eligible ) {
		?>
		<script>
			jQuery(function($) {
				$('#go-back').off('click').on('click', function(event) {
					event.preventDefault();
					$('.my-cases').hide();
					$('.direktt-service-status').show();
					$('#go-back').hide();
				});
			});
		</script>
		<?php
	}
	$my_case_list = array();

	$my_case_list_posts = get_posts(
		array(
			'post_type'      => 'direktt_service_case',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'   => '_dss_direktt_subscription_id',
					'value' => $subscription_id,
				),
			),
		)
	);

	if ( ! empty( $my_case_list_posts ) ) {
		foreach ( $my_case_list_posts as $my_case_post ) {
			$my_case_status_terms = get_the_terms( $my_case_post->ID, 'case_status' );
			$my_case_status_id    = $my_case_status_terms[0]->term_id;
			if ( $closing_status !== $my_case_status_id ) {
				$my_case_list[] = $my_case_post->ID;
			}
		}
	}
	?>
		<?php
		if ( ! empty( $my_case_list ) ) {
			?>
			<div class="direktt-service-status-cases-list my-cases" style="<?php echo $eligible ? esc_attr( 'display: none;' ) : ''; ?>">
			<?php
			foreach ( $my_case_list as $my_case_id ) {
				$my_case              = get_post( $my_case_id );
				$my_case_status_terms = get_the_terms( $my_case_id, 'case_status' );
				$my_case_status       = ( $my_case_status_terms && ! is_wp_error( $my_case_status_terms ) ) ? $my_case_status_terms[0]->name : 'No Status';
				?>
				<div class="case-item my-case-item">
					<h3><?php echo esc_html( $my_case->post_title ); ?></h3>
					<div class="direktt-service-status-description"><strong><?php echo esc_html__( 'Description:', 'direktt-service-status' ); ?> </strong><?php echo esc_html( wp_trim_words( $my_case->post_content, 10, '...' ) ?: '/' ); ?></div>
					<div class="direktt-service-status-status"><strong><?php echo esc_html__( 'Status:', 'direktt-service-status' ); ?> </strong><?php echo esc_html( $my_case_status ); ?></div>
					<?php
					$log = get_post_meta( $my_case_id, 'direktt_service_status_change_log', true ) ?: array();
					$log = array_reverse( $log );
					if ( ! empty( $log ) && is_array( $log ) ) {
							echo '<table class="direktt-service-status-log">';
								echo '<thead>';
									echo '<tr>';
										echo '<th>';
											echo esc_html__( 'Time', 'direktt-service-status' );
										echo '</th>';
										echo '<th>';
											echo esc_html__( 'From', 'direktt-service-status' );
										echo '</th>';
										echo '<th>';
											echo esc_html__( 'To', 'direktt-service-status' );
										echo '</th>';
									echo '</tr>';
								echo '</thead>';
						foreach ( $log as $entry ) {
							if ( $entry['type'] === 'changed' ) {
								$old_term = $entry['old_term'] ? get_term( $entry['old_term'] )->name : 'None';
								$new_term = $entry['new_term'] ? get_term( $entry['new_term'] )->name : 'None';
								echo '<tr>';
									echo '<td>';
										echo esc_html( human_time_diff( strtotime( $entry['date'] ) ) . ' ago' );
									echo '</td>';
									echo '<td>';
										echo esc_html( $old_term );
									echo '</td>';
									echo '<td>';
										echo esc_html( $new_term );
									echo '</td>';
								echo '</tr>';
							} else {
								$status = $entry['status'] ? get_term( $entry['status'] )->name : 'None';
								echo '<tr>';
									echo '<td>';
										echo esc_html( human_time_diff( strtotime( $entry['date'] ) ) . ' ago' );
									echo '</td>';
									echo '<td>';
										echo esc_html( '/' );
									echo '</td>';
									echo '<td>';
										echo esc_html( $status );
									echo '</td>';
								echo '</tr>';
							}
						}
								echo '</table>';
					}
					?>
						</div>
					<?php
			}
				echo '</div>';
		} else {
			?>
				<h3 class="notice notice-warning"><?php echo esc_html__( 'You have no open cases.', 'direktt-service-status' ); ?></h3>
				<?php
		}
		if ( $eligible ) {
			?>
				<button id="go-back" style="display: none;"><?php echo esc_html__( 'Go back', 'direktt-service-status' ); ?></button>
			<?php
		}
			echo '</div>';
		echo '</div>';
		echo '</div>';
		return ob_get_clean();
}

add_action( 'parent_file', 'direktt_service_status_highlight_submenu' );

function direktt_service_status_highlight_submenu( $parent_file ) {
	global $submenu_file, $current_screen, $pagenow;

	if ( $pagenow === 'post.php' || $pagenow === 'post-new.php' ) {
		if ( $current_screen->post_type === 'direktt_service_case' ) {
			$submenu_file = 'edit.php?post_type=direktt_service_case';
			$parent_file  = 'direktt-dashboard';
		}
	} elseif ( $pagenow === 'term.php' && $current_screen->taxonomy === 'case_status' ) {
		$submenu_file = 'edit-tags.php?taxonomy=case_status';
		$parent_file  = 'direktt-dashboard';
	}

	return $parent_file;
}
