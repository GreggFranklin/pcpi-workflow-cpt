<?php
/**
 * Handles saving and sanitization of workflow configuration.
 *
 * RESPONSIBILITY:
 * - Validate request (nonce, permissions)
 * - Sanitize all incoming data
 * - Build normalized config array
 * - Persist to post meta
 *
 * IMPORTANT:
 * - No UI logic here
 * - No business logic here
 * - Only data handling
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class PCPI_WF_Save_Handler {

    const META_KEY = '_pcpi_workflow_config';

    public function __construct() {
        add_action( 'save_post', [ $this, 'save' ] );
    }
    
    private function workflow_key_exists($key, $exclude_post_id = 0){

    $posts = get_posts([
        'post_type'   => 'pcpi_workflow',
        'post_status' => 'any',
        'numberposts' => -1,
        'exclude'     => [$exclude_post_id],
    ]);

    foreach ($posts as $p) {
        $cfg = get_post_meta($p->ID, '_pcpi_workflow_config', true);
        if ( isset($cfg['key']) && $cfg['key'] === $key ) {
            return true;
        }
    }

    return false;
}

    /**
     * Get existing config or return default structure
     */
    private function get_config($post_id){

        $config = get_post_meta($post_id,self::META_KEY,true);

        return is_array($config) ? $config : [
            'forms'     => [],
            'features'  => [],
            'field_map' => []
        ];
    }

    /**
     * Main save handler
     *
     * Runs on every post save, but exits unless:
     * - Correct post type
     * - Valid nonce
     * - Proper permissions
     */
    public function save($post_id){

        /* ---------------- SECURITY ---------------- */

        if ( ! isset($_POST['pcpi_nonce']) ||
             ! wp_verify_nonce($_POST['pcpi_nonce'],'pcpi_save') ) return;

        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;

        if ( ! current_user_can('edit_post',$post_id) ) return;

        if ( get_post_type($post_id) !== 'pcpi_workflow' ) return;

        $config = $this->get_config($post_id);

        /* ---------------- FORMS ---------------- */

        if ( isset($_POST['pcpi_forms']) ) {
            $config['forms'] = array_map('absint', $_POST['pcpi_forms']);
        }

        /* ---------------- FEATURES ---------------- */

        $config['features'] = [];

        if ( isset($_POST['pcpi_features']) ) {
            foreach ($_POST['pcpi_features'] as $key => $value) {
                $config['features'][ sanitize_key($key) ] = true;
            }
        }
        
        /* ----------------- Actions ------------------ */
        
	$default_actions = [
    	'review' => false,
    	'generate_pdf' => false,
    	'send' => true,
   	 'resend' => true,
    	'delete' => true,
	];

	$actions = [];

	if ( ! empty($_POST['pcpi_dashboard_actions']) && is_array($_POST['pcpi_dashboard_actions']) ) {

    	foreach ( $_POST['pcpi_dashboard_actions'] as $key => $value ) {
     	   $actions[ sanitize_key($key) ] = (bool) $value;
    	}
	}

	// Merge with defaults (ensures all keys exist)
	$config['dashboard']['actions'] = array_merge($default_actions, $actions);

        /* ---------------- FIELD MAP ---------------- */

        /**
         * Expected structure:
         *
         * pcpi_map[
         *   key[]            => 'first_name'
         *   applicant[]      => 12
         *   questionnaire[]  => 203
         *   review[]         => 45
         *   pdf[]            => 'f9'
         * ]
         */

        if ( !empty($config['field_map_enabled']) && isset($_POST['pcpi_map']) ) {
        
        $config['field_map_enabled'] = isset($_POST['pcpi_field_map_enabled']);

            $map = [];
            $keys = $_POST['pcpi_map']['key'];

            foreach ($keys as $i => $key) {

                if ( empty($key) ) continue;

                $map[ sanitize_key($key) ] = [
                    'applicant'     => absint($_POST['pcpi_map']['applicant'][$i]),
                    'questionnaire' => absint($_POST['pcpi_map']['questionnaire'][$i]),
                    'review'        => absint($_POST['pcpi_map']['review'][$i]),
                    'pdf'           => sanitize_text_field($_POST['pcpi_map']['pdf'][$i]),
                ];
            }

            $config['field_map'] = $map;
        }
        
        if ( isset($_POST['pcpi_rel']) ) {

    $config['relationships'] = [
        'questionnaire_parent_applicant_field_id' => absint($_POST['pcpi_rel']['q_to_app'] ?? 0),
        'review_parent_questionnaire_field_id'    => absint($_POST['pcpi_rel']['r_to_q'] ?? 0),
        'review_parent_applicant_field_id'        => absint($_POST['pcpi_rel']['r_to_app'] ?? 0),
    ];
}

if ( isset($_POST['pcpi_behavior']) ) {

    $config['resolver']['field_id'] = absint($_POST['pcpi_behavior']['resolver'] ?? 0);

    $config['entry_mode'] = sanitize_text_field($_POST['pcpi_behavior']['entry_mode'] ?? '');

}

if ( isset($_POST['pcpi_pages']) ) {

    $config['routes'] = [
        'questionnaire' => esc_url_raw($_POST['pcpi_pages']['questionnaire'] ?? ''),
        'review'        => esc_url_raw($_POST['pcpi_pages']['review'] ?? ''),
    ];
}

// Ensure stable workflow key
if ( empty($config['key']) ) {

    // Generate from title (once)
    $base = sanitize_key( get_the_title($post_id) );

    if ( empty($base) ) {
        $base = 'workflow_' . $post_id;
    }

    $key = $base;
    $i = 1;

    // Ensure uniqueness
    while ( $this->workflow_key_exists($key, $post_id) ) {
        $key = $base . '_' . $i;
        $i++;
    }

    $config['key'] = $key;
}

        /* ---------------- SAVE ---------------- */

        update_post_meta($post_id, self::META_KEY, $config);
    }
}