<?php
function register_battle_map_cpt() {
    $args = [
        'label'           => 'Battle Maps',
        'public'          => false,
        'show_ui'         => true,
        'show_in_rest'    => false,
        'supports'        => ['title'],
        'capability_type' => 'post',
        'menu_position'   => 25,
        'menu_icon'       => 'dashicons-location-alt',
    ];

    register_post_type( 'battle_map', $args );
}
add_action( 'init', 'register_battle_map_cpt' );
