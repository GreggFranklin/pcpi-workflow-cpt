<?php
/**
 * Plugin Name: _PCPI Workflow CPT Registry
 * Description: A workflow builder that uses a custom post type to define how applicant, questionnaire, and review forms connect. Works with the PCPI Workflow Engine to control routing, relationships, and automation.
 * Version: 1.4.0
 * Author: Gregg Franklin, Marc Benzakein
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define('PCPI_WF_PATH', plugin_dir_path(__FILE__));
define('PCPI_WF_URL', plugin_dir_url(__FILE__));

/*
|--------------------------------------------------------------------------
| DEPENDENCY CHECK
|--------------------------------------------------------------------------
*/

add_action('admin_init', function(){

    if ( ! class_exists('PCPI_Workflow_Engine') ) {

        add_action('admin_notices', function(){
            echo '<div class="notice notice-error">
                <p><strong>Missing Required Plugin: PCPI Workflow Engine</strong></p>
                <p>
                    The <strong>PCPI Workflow CPT Registry</strong> plugin requires the 
                    <strong>PCPI Workflow Engine</strong> to function.
                </p>
                <p>
                    Please install and activate the Workflow Engine plugin.
                </p>
            </div>';
        });

    }

});

/*
|--------------------------------------------------------------------------
| LOAD FILES
|--------------------------------------------------------------------------
*/

// Post Type
require_once PCPI_WF_PATH . 'includes/post-type/class-post-type.php';

// Admin
require_once PCPI_WF_PATH . 'includes/admin/class-meta-boxes.php';

// Actions
require_once PCPI_WF_PATH . 'includes/actions/class-save-handler.php';
require_once PCPI_WF_PATH . 'includes/actions/class-duplicate.php';

// API
require_once PCPI_WF_PATH . 'includes/api/class-ajax.php';

// Core
require_once PCPI_WF_PATH . 'includes/core/class-repository.php';
require_once PCPI_WF_PATH . 'includes/core/class-normalizer.php';

// Registry
require_once PCPI_WF_PATH . 'includes/registry/functions-registry.php';

/*
|--------------------------------------------------------------------------
| BOOT
|--------------------------------------------------------------------------
*/

add_action('plugins_loaded', function(){

    if ( ! class_exists('PCPI_Workflow_Engine') ) {
        return; // stop execution safely
    }

    // Always needed
    new PCPI_WF_Post_Type();
    new PCPI_WF_Save_Handler();
    new PCPI_WF_Ajax();
    new PCPI_WF_Duplicate();

    // Admin only
    if ( is_admin() ) {
        new PCPI_WF_Meta_Boxes();
    }

});