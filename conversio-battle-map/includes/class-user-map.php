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
}
