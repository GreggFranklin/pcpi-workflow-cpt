<?php
if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/trait-helpers.php';
require_once __DIR__ . '/trait-box-validation.php';
require_once __DIR__ . '/trait-box-forms.php';
require_once __DIR__ . '/trait-box-behavior.php';
require_once __DIR__ . '/trait-box-pages.php';
require_once __DIR__ . '/trait-box-connections.php';
require_once __DIR__ . '/trait-box-features.php';
require_once __DIR__ . '/trait-box-dashboard-actions.php';
require_once __DIR__ . '/trait-box-fieldmap.php';

class PCPI_WF_Meta_Boxes {

    use PCPI_WF_Box_Helpers;
    use PCPI_WF_Box_Validation;
    use PCPI_WF_Box_Forms;
    use PCPI_WF_Box_Behavior;
    use PCPI_WF_Box_Pages;
    use PCPI_WF_Box_Connections;
    use PCPI_WF_Box_Features;
    use PCPI_WF_Box_Dashboard_Actions;
    use PCPI_WF_Box_Fieldmap;

    const META_KEY = '_pcpi_workflow_config';

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_boxes' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
        add_action('post_submitbox_misc_actions', [ $this, 'add_workflow_key_to_publish_box' ]);
        add_filter( 'default_hidden_meta_boxes', [ $this, 'hide_onboarding_box' ], 10, 2 );
    }

    public function add_boxes() {

        global $post;

    	// Only show validation AFTER first save
    	if ( $post && $post->post_status !== 'auto-draft' ) {
        	add_meta_box('pcpi_validation','Workflow Status',[ $this,'validation_box'],'pcpi_workflow','normal','high');
   	 }
   	 
   	add_meta_box('pcpi_onboarding','Getting Started',[ $this, 'onboarding_box' ],'pcpi_workflow','normal','high');

        add_meta_box('pcpi_forms','Step 1: Select Forms',[ $this,'forms_box'],'pcpi_workflow','normal','high');
        add_meta_box('pcpi_behavior','Step 2: Configure Workflow',[ $this,'behavior_box'],'pcpi_workflow','normal');
        add_meta_box('pcpi_pages','Step 3: Select Pages',[ $this,'pages_box'],'pcpi_workflow','normal');
        add_meta_box('pcpi_connections','Step 4: Link Forms',[ $this,'connections_box'],'pcpi_workflow','normal');
        add_meta_box('pcpi_features','Step 5: Features',[ $this,'features_box'],'pcpi_workflow','normal');
        add_meta_box('pcpi_dashboard_actions','Step 6: Dashboard Actions',[ $this, 'dashboard_actions_box' ],'pcpi_workflow','normal');
        
        add_meta_box('pcpi_fieldmap','Advanced: Field Mapping',[ $this,'fieldmap_box'],'pcpi_workflow','normal');
    }

    protected function get_config($post_id){
        $config = get_post_meta($post_id,self::META_KEY,true);
        return is_array($config) ? $config : [
            'forms'=>[],
            'features'=>[],
            'field_map'=>[]
        ];
    }

    public function enqueue(){

        $screen = get_current_screen();
        if(!$screen || $screen->post_type !== 'pcpi_workflow') return;

        wp_enqueue_style(
            'pcpi-admin-css',
            PCPI_WF_URL.'assets/css/admin.css',
            [],
            filemtime(PCPI_WF_PATH.'assets/css/admin.css')
        );

        wp_enqueue_script('pcpi-admin',PCPI_WF_URL.'assets/js/admin.js',['jquery'],null,true);
        wp_enqueue_script('pcpi-fieldmap',PCPI_WF_URL.'assets/js/field-map.js',['jquery','pcpi-admin'],null,true);
    }
    
    public function add_workflow_key_to_publish_box(){

    global $post;

    if ( ! $post || $post->post_type !== 'pcpi_workflow' ) {
        return;
    }

    $config = get_post_meta($post->ID, self::META_KEY, true);

    if ( empty($config['key']) ) {
        return;
    }

    echo '<div class="misc-pub-section pcpi-workflow-key">
    <span><strong>Workflow Key:</strong> <code>' . esc_html($config['key']) . '</code></span>
	</div>';
}
    
   
    public function hide_onboarding_box( $hidden, $screen ) {

    if ( $screen->post_type === 'pcpi_workflow' ) {
        $hidden[] = 'pcpi_onboarding';
    }

    return $hidden;
}

    public function onboarding_box($post){

    echo '<div class="pcpi-onboarding">

        <strong>How Workflows Work</strong>

        <ol class="pcpi-onboarding-list">
            <li>
                <strong>Applicant Form (optional)</strong><br>
                Completed by staff to create the applicant and send them a link to the questionnaire.<br>
                This step is optional and not used in kiosk-style workflows.
            </li>

            <li>
                <strong>Questionnaire Form</strong><br>
                The main form completed by the applicant.
            </li>

            <li>
                <strong>Review Form (optional)</strong><br>
                A staff-facing form used to review and finalize results.
            </li>
        </ol>

        <strong>Before creating a workflow, make sure you have:</strong>

        <ul class="pcpi-onboarding-list">
            <li>Forms created in Gravity Forms</li>
            <li>Pages created to host each form</li>
            <li>Forms embedded on those pages</li>
        </ul>

        <p class="pcpi-help">
        Tip: If using kiosk mode, you can skip the Applicant Form and go directly to the Questionnaire.
        </p>

    </div>';
}
}