<?php
/**
 * Helper functions for Battle Map calculations.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Calculate total points for a user map based on completed sections.
 *
 * @param array $userMap User map structure as associative array.
 * @return int Total points, max 1000.
 */
function calculatePoints( $userMap ) {
    $totalImpact     = 0;
    $impactBySection = [];

    if ( empty( $userMap['territories'] ) ) {
        return 0;
    }

    foreach ( $userMap['territories'] as $territory ) {
        if ( empty( $territory['unlocked'] ) ) {
            continue;
        }

        if ( empty( $territory['sections'] ) ) {
            continue;
        }

        foreach ( $territory['sections'] as $section ) {
            if ( ! empty( $section['completed'] ) ) {
                $impactBySection[] = isset( $section['impact'] ) ? (int) $section['impact'] : 0;
                $totalImpact      += isset( $section['impact'] ) ? (int) $section['impact'] : 0;
            }
        }
    }

    if ( 0 === $totalImpact ) {
        return 0;
    }

    $totalPoints = 0;
    foreach ( $impactBySection as $impact ) {
        $weight = $impact / $totalImpact;
        $points = round( $weight * 1000 );
        $totalPoints += $points;
    }

    return min( $totalPoints, 1000 );
}

/**
 * Unlock achievements based on current user map and progress.
 *
 * @param array $userMap        User map array, passed by reference.
 * @param array $progressRecord Progress record array, passed by reference.
 * @param array $achievements   Achievements array, passed by reference.
 *
 * @return array List of achievement IDs unlocked during this call.
 */
function unlockAchievement( &$userMap, &$progressRecord, &$achievements ) {
    $unlocked_now = [];

    if ( ! is_array( $achievements ) ) {
        $achievements = [];
    }

    if ( ! isset( $progressRecord['unlockedAchievements'] ) || ! is_array( $progressRecord['unlockedAchievements'] ) ) {
        $progressRecord['unlockedAchievements'] = [];
    }

    $already_unlocked = $progressRecord['unlockedAchievements'];
    foreach ( $achievements as $ach ) {
        if ( ! empty( $ach['id'] ) && ! empty( $ach['unlocked'] ) ) {
            $already_unlocked[] = $ach['id'];
        }
    }

    $conditions = [
        'first-step'      => function () use ( $userMap ) {
            if ( empty( $userMap['territories'] ) ) {
                return false;
            }
            foreach ( $userMap['territories'] as $territory ) {
                if ( empty( $territory['sections'] ) ) {
                    continue;
                }
                foreach ( $territory['sections'] as $section ) {
                    if ( ! empty( $section['completed'] ) ) {
                        return true;
                    }
                }
            }
            return false;
        },
        'clarity-complete' => function () use ( $userMap ) {
            if ( empty( $userMap['territories'] ) ) {
                return false;
            }
            foreach ( $userMap['territories'] as $territory ) {
                if ( 'clarity-call' === $territory['slug'] && ! empty( $territory['completed'] ) ) {
                    return true;
                }
            }
            return false;
        },
        'half-map'       => function () use ( $progressRecord ) {
            return isset( $progressRecord['totalPoints'] ) && $progressRecord['totalPoints'] >= 500;
        },
        'full-map'       => function () use ( $progressRecord ) {
            return isset( $progressRecord['totalPoints'] ) && $progressRecord['totalPoints'] >= 1000;
        },
        'all-completed'  => function () use ( $userMap ) {
            if ( empty( $userMap['territories'] ) ) {
                return false;
            }
            foreach ( $userMap['territories'] as $territory ) {
                if ( empty( $territory['completed'] ) ) {
                    return false;
                }
            }
            return true;
        },
    ];

    foreach ( $conditions as $id => $callback ) {
        if ( in_array( $id, $already_unlocked, true ) ) {
            continue;
        }

        if ( call_user_func( $callback ) ) {
            $achievements[] = [
                'id'         => $id,
                'unlocked'   => true,
                'unlockedAt' => date( 'c' ),
            ];
            $progressRecord['unlockedAchievements'][] = $id;
            $unlocked_now[] = $id;
        }
    }

    return $unlocked_now;
}

/**
 * Handle narrative triggers and return matching messages from catalogs.
 *
 * @param string      $trigger    Event trigger identifier.
 * @param string|null $targetSlug Optional target slug for filtering.
 *
 * @return array Matching narrative messages.
 */
function handleNarrativeTriggers( $trigger, $targetSlug = null ) {
    $file_path = plugin_dir_path( __FILE__ ) . '/../data/catalogs.json';

    if ( ! file_exists( $file_path ) ) {
        return [];
    }

    $json = file_get_contents( $file_path );
    if ( false === $json ) {
        return [];
    }

    $data = json_decode( $json, true );
    if ( json_last_error() !== JSON_ERROR_NONE ) {
        return [];
    }

    if ( empty( $data['narrativeMessages'] ) || ! is_array( $data['narrativeMessages'] ) ) {
        return [];
    }

    $messages = [];
    foreach ( $data['narrativeMessages'] as $msg ) {
        if ( ! isset( $msg['trigger'] ) || $msg['trigger'] !== $trigger ) {
            continue;
        }
        if ( null !== $targetSlug ) {
            if ( ! isset( $msg['targetSlug'] ) || $msg['targetSlug'] !== $targetSlug ) {
                continue;
            }
        }
        $messages[] = $msg;
    }

    return $messages;
}
