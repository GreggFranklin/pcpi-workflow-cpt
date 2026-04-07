<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function pcpi_get_registry(){

    $db = PCPI_WF_Repository::get_all();

    if(empty($db) && function_exists('pcpi_get_default_registry')){
        return pcpi_get_default_registry();
    }

    return PCPI_WF_Normalizer::normalize($db);
}

/**
 * PCPI Workflow Engine Integration
 * ------------------------------------------------------------------------
 *
 * This filter injects workflows stored in the CPT (pcpi_workflow)
 * into the Workflow Engine.
 *
 * The engine calls:
 *   apply_filters('pcpi_workflow_engine_workflows', $defaults)
 *
 * We hook into that here and merge CPT-defined workflows with the
 * legacy registry (trait-registry.php).
 *
 * ------------------------------------------------------------------------
 * DATA FLOW
 * ------------------------------------------------------------------------
 *
 * CPT (UI Builder)
 *   → post_type: pcpi_workflow
 *   → meta: _pcpi_workflow_config
 *
 * is normalized into:
 *
 * Engine Workflow Structure:
 *
 * [
 *   'key' => [
 *     'label' => string,
 *
 *     // Forms
 *     'applicant_form_id' => int,
 *     'applicant_workflow_field_id' => int,
 *     'source_form_id' => int,
 *     'review_form_id' => int,
 *
 *     'has_review' => bool,
 *
 *     // Relationships
 *     'questionnaire_parent_applicant_field_id' => int,
 *     'review_parent_questionnaire_field_id' => int,
 *     'review_parent_applicant_field_id' => int,
 *
 *     // Pages
 *     'questionnaire_page_path' => string,
 *     'review_page_path' => string,
 *
 *     // Features
 *     'features' => array,
 *
 *     // Behavior
 *     'entry_mode' => string (optional: kiosk, etc.)
 *   ]
 * ]
 *
 * ------------------------------------------------------------------------
 * KEY NOTES
 * ------------------------------------------------------------------------
 *
 * - Workflow keys are currently generated from post_title using sanitize_key()
 *   → Changing the title WILL change the workflow key.
 *
 * - CPT workflows are merged with legacy workflows for backward compatibility.
 *
 * - To fully migrate away from trait-registry.php:
 *     replace array_merge($defaults, $cpt_workflows)
 *     with:
 *     return $cpt_workflows;
 *
 * ------------------------------------------------------------------------
 * FUTURE IMPROVEMENTS
 * ------------------------------------------------------------------------
 *
 * - Store a stable workflow key in post meta instead of using post_title
 * - Add validation to ensure required fields exist before exposing workflow
 * - Add caching layer for performance
 *
 */
add_filter('pcpi_workflow_engine_workflows', function($defaults){

    // Helper logger (safe)
    $log = function($msg){
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log('[PCPI][CPT] ' . $msg);
        }
    };

    $posts = get_posts([
        'post_type'   => 'pcpi_workflow',
        'post_status' => 'publish',
        'numberposts' => -1,
    ]);

    if ( empty($posts) ) {
        $log('No CPT workflows found. Using legacy registry.');
        return $defaults;
    }

    $log('CPT workflows found: ' . count($posts));

    $cpt_workflows = [];

    foreach ( $posts as $post ) {

        $config = get_post_meta($post->ID, '_pcpi_workflow_config', true);

        if ( empty($config) || ! is_array($config) ) {
            $log("Skipping post ID {$post->ID} (no config)");
            continue;
        }

        $key = $config['key'] ?? sanitize_key($post->post_title);

        $log("Processing workflow: {$post->post_title} (key: {$key})");

        $cpt_workflows[$key] = [

            'label' => $post->post_title,

            'applicant_form_id' => $config['forms']['applicant'] ?? 0,
            'applicant_workflow_field_id' => $config['resolver']['field_id'] ?? 0,
            'source_form_id' => $config['forms']['questionnaire'] ?? 0,
            'review_form_id' => $config['forms']['review'] ?? 0,

            'has_review' => ! empty($config['forms']['review']),

            'questionnaire_parent_applicant_field_id' =>
                $config['relationships']['questionnaire_parent_applicant_field_id'] ?? 0,

            'review_parent_questionnaire_field_id' =>
                $config['relationships']['review_parent_questionnaire_field_id'] ?? 0,

            'review_parent_applicant_field_id' =>
                $config['relationships']['review_parent_applicant_field_id'] ?? 0,

            'questionnaire_page_path' => $config['routes']['questionnaire'] ?? '',
            'review_page_path'        => $config['routes']['review'] ?? '',

            'features' => $config['features'] ?? [],
            'entry_mode' => $config['entry_mode'] ?? '',

        ];
    }

    if ( empty($cpt_workflows) ) {
        $log('No valid CPT workflows after processing. Using legacy.');
        return $defaults;
    }

    $log('Using CPT workflows: ' . implode(', ', array_keys($cpt_workflows)));

    return array_merge($defaults, $cpt_workflows);

});