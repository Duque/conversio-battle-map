<?php
require_once __DIR__ . '/helpers-map.php';
class CBM_Rest_Endpoints {
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route( 'battle-map/v1', '/user', [
            'methods'  => 'GET',
            'callback' => [ $this, 'get_user_map' ],
            'permission_callback' => '__return_true',
            'args'    => [
                'token' => [
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ] );

        register_rest_route(
            'battle-map/v1',
            '/user/(?P<userId>[a-zA-Z0-9_-]+)/section/(?P<slug>[a-zA-Z0-9_-]+)/complete',
            [
                'methods'  => 'POST',
                'callback' => [ $this, 'complete_section' ],
                'permission_callback' => '__return_true',
            ]
        );

        register_rest_route(
            'battle-map/v1',
            '/user/(?P<userId>[a-zA-Z0-9_-]+)/summary',
            [
                'methods'  => 'GET',
                'callback' => [ $this, 'get_user_summary' ],
                'permission_callback' => '__return_true',
            ]
        );

        register_rest_route(
            'battle-map/v1',
            '/catalogs',
            [
                'methods'  => 'GET',
                'callback' => [ $this, 'get_catalogs' ],
                'permission_callback' => '__return_true',
            ]
        );

        register_rest_route(
            'battle-map/v1',
            '/user/(?P<userId>[a-zA-Z0-9_-]+)/export/pdf',
            [
                'methods'  => 'POST',
                'callback' => [ $this, 'export_pdf' ],
                'permission_callback' => '__return_true',
            ]
        );
    }

    public function get_user_map( WP_REST_Request $request ) {
        $token = $request->get_param( 'token' );
        $map   = CBM_User_Map::generate_user_map( $token );
        if ( null === $map ) {
            return new WP_REST_Response(
                [ 'message' => 'Mapa no encontrado o token inválido.' ],
                404
            );
        }

        return rest_ensure_response( $map );
    }

    public function complete_section( WP_REST_Request $request ) {
        $user_id = sanitize_text_field( $request['userId'] );
        $slug    = sanitize_text_field( $request['slug'] );

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
            return new WP_REST_Response( [ 'message' => 'Mapa no encontrado.' ], 404 );
        }

        $post_id  = $posts[0];
        $map_json = get_post_meta( $post_id, 'user_map', true );
        if ( empty( $map_json ) ) {
            return new WP_REST_Response( [ 'message' => 'Mapa no encontrado.' ], 404 );
        }

        $map = json_decode( $map_json, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_REST_Response( [ 'message' => 'Datos inválidos.' ], 500 );
        }

        $progress_json = get_post_meta( $post_id, 'progress_record', true );
        $progress      = [];
        if ( ! empty( $progress_json ) ) {
            $progress = json_decode( $progress_json, true );
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                $progress = [];
            }
        }

        $achievements_json = get_post_meta( $post_id, 'achievements', true );
        $achievements      = [];
        if ( ! empty( $achievements_json ) ) {
            $achievements = json_decode( $achievements_json, true );
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                $achievements = [];
            }
        }

        $section_found = false;
        $territory_idx = null;
        $section_idx   = null;

        foreach ( $map['territories'] as $t_index => $territory ) {
            foreach ( $territory['sections'] as $s_index => $section ) {
                if ( $section['slug'] === $slug ) {
                    if ( empty( $territory['unlocked'] ) ) {
                        return new WP_REST_Response( [ 'message' => 'Territorio bloqueado.' ], 403 );
                    }
                    if ( empty( $section['unlocked'] ) ) {
                        return new WP_REST_Response( [ 'message' => 'Sección no desbloqueada.' ], 400 );
                    }
                    $territory_idx = $t_index;
                    $section_idx   = $s_index;
                    $section_found = true;
                    break 2;
                }
            }
        }

        if ( ! $section_found ) {
            return new WP_REST_Response( [ 'message' => 'Sección no encontrada.' ], 400 );
        }

        $map['territories'][ $territory_idx ]['sections'][ $section_idx ]['completed'] = true;

        // Marcar territorio como completado si todas las secciones lo están
        $territory_sections = $map['territories'][ $territory_idx ]['sections'];
        $all_completed      = true;
        foreach ( $territory_sections as $sec ) {
            if ( empty( $sec['completed'] ) ) {
                $all_completed = false;
                break;
            }
        }
        if ( $all_completed ) {
            $map['territories'][ $territory_idx ]['completed'] = true;
        }

        $total_points = calculatePoints( $map );

        $progress['totalPoints'] = $total_points;
        $progress['lastUpdated'] = gmdate( 'Y-m-d\TH:i:s\Z' );

        $new_achievements = unlockAchievement( $map, $progress, $achievements );

        if ( ! empty( $new_achievements ) ) {
            update_post_meta( $post_id, 'achievements', wp_json_encode( $achievements ) );
        }

        update_post_meta( $post_id, 'user_map', wp_json_encode( $map ) );
        update_post_meta( $post_id, 'progress_record', wp_json_encode( $progress ) );

        $narratives = handleNarrativeTriggers( 'section.complete', $slug );

        $response = [
            'userMap'       => $map,
            'progressRecord' => [
                'totalPoints' => $total_points,
                'lastUpdated' => $progress['lastUpdated'],
            ],
            'newAchievements' => [],
            'narrativeMessages' => []
        ];

        if ( ! empty( $new_achievements ) ) {
            foreach ( $achievements as $ach ) {
                if ( in_array( $ach['id'], $new_achievements, true ) ) {
                    $response['newAchievements'][] = $ach;
                }
            }
        }

        if ( ! empty( $narratives ) ) {
            foreach ( $narratives as $msg ) {
                $response['narrativeMessages'][] = $msg;
            }
        }

        return rest_ensure_response( $response );
    }

    public function get_user_summary( WP_REST_Request $request ) {
        $user_id = sanitize_text_field( $request['userId'] );

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
            return new WP_REST_Response( [ 'message' => 'Mapa no encontrado.' ], 404 );
        }

        $post_id           = $posts[0];
        $user_map_json     = get_post_meta( $post_id, 'user_map', true );
        $progress_json     = get_post_meta( $post_id, 'progress_record', true );
        $achievements_json = get_post_meta( $post_id, 'achievements', true );

        $user_map = $user_map_json ? json_decode( $user_map_json, true ) : null;
        $progress = $progress_json ? json_decode( $progress_json, true ) : null;
        $achievements = $achievements_json ? json_decode( $achievements_json, true ) : null;

        if ( ! is_array( $user_map ) || ! is_array( $progress ) ) {
            return new WP_REST_Response( [ 'message' => 'Datos inválidos.' ], 500 );
        }

        $completed_sections = 0;
        $total_sections     = 0;

        if ( ! empty( $user_map['territories'] ) ) {
            foreach ( $user_map['territories'] as $territory ) {
                if ( empty( $territory['sections'] ) ) {
                    continue;
                }
                foreach ( $territory['sections'] as $section ) {
                    $total_sections++;
                    if ( ! empty( $section['completed'] ) ) {
                        $completed_sections++;
                    }
                }
            }
        }

        $summary = [
            'totalPoints'          => isset( $progress['totalPoints'] ) ? (int) $progress['totalPoints'] : 0,
            'completedSections'    => $completed_sections,
            'totalSections'        => $total_sections,
            'completedTerritories' => isset( $progress['completedTerritories'] ) && is_array( $progress['completedTerritories'] ) ? count( $progress['completedTerritories'] ) : 0,
            'unlockedTerritories'  => isset( $progress['unlockedTerritories'] ) && is_array( $progress['unlockedTerritories'] ) ? count( $progress['unlockedTerritories'] ) : 0,
            'unlockedAchievements' => isset( $progress['unlockedAchievements'] ) && is_array( $progress['unlockedAchievements'] ) ? count( $progress['unlockedAchievements'] ) : 0,
            'lastUpdate'           => isset( $progress['lastUpdated'] ) ? $progress['lastUpdated'] : '',
        ];

        return rest_ensure_response( $summary );
    }

    public function get_catalogs( WP_REST_Request $request ) {
        $file_path = plugin_dir_path( __FILE__ ) . '../data/catalogs.json';

        if ( ! file_exists( $file_path ) ) {
            return new WP_REST_Response(
                [ 'message' => 'Error al cargar los cat\xC3\xA1logos.' ],
                500
            );
        }

        $json = file_get_contents( $file_path );
        if ( false === $json ) {
            return new WP_REST_Response(
                [ 'message' => 'Error al cargar los cat\xC3\xA1logos.' ],
                500
            );
        }

        $data = json_decode( $json, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_REST_Response(
                [ 'message' => 'Error al cargar los cat\xC3\xA1logos.' ],
                500
            );
        }

        return rest_ensure_response( $data );
    }

    public function export_pdf( WP_REST_Request $request ) {
        $user_id = sanitize_text_field( $request['userId'] );

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
            return new WP_REST_Response( [ 'message' => 'Mapa no encontrado.' ], 404 );
        }

        $post_id = $posts[0];

        $user_map_json = get_post_meta( $post_id, 'user_map', true );
        $progress_json = get_post_meta( $post_id, 'progress_record', true );
        $achievements_json = get_post_meta( $post_id, 'achievements', true );

        $user_map = $user_map_json ? json_decode( $user_map_json, true ) : null;
        $progress = $progress_json ? json_decode( $progress_json, true ) : null;
        $achievements = $achievements_json ? json_decode( $achievements_json, true ) : null;

        if ( ! is_array( $user_map ) || ! is_array( $progress ) || ! is_array( $achievements ) ) {
            return new WP_REST_Response( [ 'message' => 'Datos inválidos.' ], 500 );
        }

        $options = $request->get_json_params();
        $include_details = isset( $options['includeDetails'] ) ? (bool) $options['includeDetails'] : true;
        $lang           = isset( $options['lang'] ) ? sanitize_text_field( $options['lang'] ) : 'es';
        $style          = isset( $options['style'] ) ? sanitize_text_field( $options['style'] ) : 'formal';
        $include_cta    = isset( $options['includeCTA'] ) ? (bool) $options['includeCTA'] : true;

        // Simulated PDF generation - simple string with user information
        $text = 'Mapa generado';
        if ( isset( $user_map['userId'] ) ) {
            $text = 'Mapa de ' . $user_map['userId'];
        }

        $pdf_content = $this->create_simple_pdf( $text );

        if ( empty( $pdf_content ) ) {
            return new WP_REST_Response( [ 'message' => 'No se pudo generar el PDF' ], 500 );
        }

        $response = new WP_REST_Response( $pdf_content );
        $response->header( 'Content-Type', 'application/pdf' );
        $response->header( 'Content-Disposition', 'attachment; filename="mapa-conversio.pdf"' );
        return $response;
    }

    private function create_simple_pdf( $text ) {
        $text = str_replace( [ '\\', '(', ')' ], [ '\\\\', '\\(', '\\)' ], $text );
        $pdf = '%PDF-1.4' . "\n";
        $offsets = [];

        $offsets[1] = strlen( $pdf );
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $offsets[2] = strlen( $pdf );
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $offsets[3] = strlen( $pdf );
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";

        $stream = 'BT /F1 24 Tf 50 750 Td (' . $text . ') Tj ET';
        $offsets[4] = strlen( $pdf );
        $pdf .= '4 0 obj\n<< /Length ' . strlen( $stream ) . " >>\nstream\n" . $stream . "\nendstream\nendobj\n";

        $offsets[5] = strlen( $pdf );
        $pdf .= "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

        $xref_pos = strlen( $pdf );
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        for ( $i = 1; $i <= 5; $i++ ) {
            $pdf .= sprintf( "%010d 00000 n \n", $offsets[ $i ] );
        }
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n" . $xref_pos . "\n%%EOF";

        return $pdf;
    }

    // The calculate_points method was replaced by the global calculatePoints helper.
}
