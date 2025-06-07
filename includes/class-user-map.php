<?php
class CBM_User_Map {
    public function __construct() {
        // Placeholder for future initialization
    }

    public static function generate_user_map( $access_token ) {
        $token = sanitize_text_field( $access_token );

        $args = [
            'post_type'      => 'battle_map',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'   => 'access_token',
                    'value' => $token,
                ],
            ],
        ];

        $posts = get_posts( $args );
        if ( empty( $posts ) ) {
            return null;
        }

        $post_id = $posts[0];

        $user_map_json       = get_post_meta( $post_id, 'user_map', true );
        $progress_json       = get_post_meta( $post_id, 'progress_record', true );
        $achievements_json   = get_post_meta( $post_id, 'achievements', true );
        $stored_access_token = get_post_meta( $post_id, 'access_token', true );
        $user_id             = get_post_meta( $post_id, 'user_id', true );

        if ( ! $user_map_json || ! $progress_json || ! $achievements_json ) {
            return null;
        }

        $user_map = json_decode( $user_map_json, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return null;
        }

        $progress = json_decode( $progress_json, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return null;
        }

        $achievements = json_decode( $achievements_json, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return null;
        }

        return [
            'id'             => $post_id,
            'user_id'        => $user_id,
            'access_token'   => $stored_access_token,
            'userMap'        => $user_map,
            'progressRecord' => $progress,
            'achievements'   => $achievements,
        ];
    }

    public static function get_user_map_by_token( $token ) {
        $token = sanitize_text_field( $token );

        $args = [
            'post_type'      => 'battle_map',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'   => 'access_token',
                    'value' => $token,
                ],
            ],
        ];

        $posts = get_posts( $args );
        if ( empty( $posts ) ) {
            return null;
        }

        $post = get_post( $posts[0] );
        if ( ! $post ) {
            return null;
        }

        return self::build_user_map_data( $post );
    }

    public static function get_user_map_by_user_id( $user_id ) {
        $user_id = sanitize_text_field( $user_id );

        $args = [
            'post_type'      => 'battle_map',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'   => 'user_id',
                    'value' => $user_id,
                ],
            ],
        ];

        $posts = get_posts( $args );
        if ( empty( $posts ) ) {
            return null;
        }

        $post = get_post( $posts[0] );
        if ( ! $post ) {
            return null;
        }

        return self::build_user_map_data( $post );
    }

    private static function build_user_map_data( $post ) {
        $user_map_json     = get_post_meta( $post->ID, 'user_map', true );
        $progress_json     = get_post_meta( $post->ID, 'progress_record', true );
        $achievements_json = get_post_meta( $post->ID, 'achievements', true );

        if ( ! $user_map_json || ! $progress_json || ! $achievements_json ) {
            return null;
        }

        $user_map = json_decode( $user_map_json, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return null;
        }

        $progress = json_decode( $progress_json, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return null;
        }

        $achievements = json_decode( $achievements_json, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return null;
        }

        return [
            'userMap'        => $user_map,
            'progressRecord' => $progress,
            'achievements'   => $achievements,
        ];
    }

    public static function update_user_map_data( $post_id, $user_map, $progress_record = [], $achievements = [] ) {
        update_post_meta( $post_id, 'user_map', wp_json_encode( $user_map ) );
        update_post_meta( $post_id, 'progress_record', wp_json_encode( $progress_record ) );
        update_post_meta( $post_id, 'achievements', wp_json_encode( $achievements ) );
    }
}
