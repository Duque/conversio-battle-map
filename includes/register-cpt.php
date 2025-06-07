<?php
function register_battle_map_cpt() {
    $args = [
        'labels'          => [
            'name'          => 'Battle Maps',
            'singular_name' => 'Battle Map',
        ],
        'public'          => false,
        'show_ui'         => true,
        'show_in_rest'    => true,
        'supports'        => [ 'title', 'custom-fields' ],
        'capability_type' => 'post',
        'menu_position'   => 25,
        'menu_icon'       => 'dashicons-location-alt',
    ];

    register_post_type( 'battle_map', $args );
}
add_action( 'init', 'register_battle_map_cpt' );

/**
 * Initialize default meta fields when a Battle Map post is created.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an existing post being updated.
 */
function cbm_initialize_battle_map_fields( $post_id, $post, $update ) {
    // Only on creation, avoid autosaves.
    if ( $update || defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    $token = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( '', true );
    $user_id = $post->post_author ? $post->post_author : get_current_user_id();

    // Basic template with Clarity Call territory unlocked.
    $user_map = [
        'currentTerritorySlug' => 'clarity-call',
        'territories'          => [
            [
                'slug'      => 'clarity-call',
                'title'     => 'Clarity Call\u2122',
                'unlocked'  => true,
                'completed' => false,
                'order'     => 1,
                'sections'  => [
                    [ 'slug' => 'home', 'completed' => false, 'unlocked' => true ],
                    [ 'slug' => 'product', 'completed' => false, 'unlocked' => false ],
                    [ 'slug' => 'cart', 'completed' => false, 'unlocked' => false ],
                    [ 'slug' => 'checkout', 'completed' => false, 'unlocked' => false ],
                ],
            ],
        ],
    ];

    update_post_meta( $post_id, 'access_token', $token );
    update_post_meta( $post_id, 'user_id', $user_id );
    update_post_meta( $post_id, 'user_map', wp_json_encode( $user_map ) );
    update_post_meta( $post_id, 'progress_record', wp_json_encode( [] ) );
    update_post_meta( $post_id, 'achievements', wp_json_encode( [] ) );
}
add_action( 'save_post_battle_map', 'cbm_initialize_battle_map_fields', 10, 3 );
