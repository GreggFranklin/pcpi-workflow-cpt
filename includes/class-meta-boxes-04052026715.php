<?php
/**
 * Handles all admin UI for Workflow CPT.
 *
 * RESPONSIBILITY:
 * - Render meta boxes (Forms, Features, Field Mapping)
 * - Enqueue admin scripts
 *
 * IMPORTANT:
 * - This class ONLY handles UI
 * - No data processing or saving logic should exist here
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class PCPI_WF_Meta_Boxes {

    /**
     * Meta key where workflow config is stored
     */
    const META_KEY = '_pcpi_workflow_config';

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_boxes' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
    }

    /**
     * Register all meta boxes for workflow CPT
     */
    public function add_boxes() {

        add_meta_box('pcpi_forms','Forms',[ $this,'forms_box'],'pcpi_workflow','normal','high');
        add_meta_box('pcpi_features','Features',[ $this,'features_box'],'pcpi_workflow','normal');
        add_meta_box('pcpi_fieldmap','Field Mapping',[ $this,'fieldmap_box'],'pcpi_workflow','normal');
        add_meta_box('pcpi_connections','Connections',[ $this,'connections_box'],'pcpi_workflow','normal');
	add_meta_box('pcpi_behavior','Workflow Behavior',[ $this,'behavior_box'],'pcpi_workflow','side');
	add_meta_box('pcpi_pages','Pages',[ $this,'pages_box'],'pcpi_workflow','side');
    }

    /**
     * Retrieve workflow config from DB
     *
     * Ensures a consistent structure is always returned
     *
     * @param int $post_id
     * @return array
     */
    private function get_config($post_id){

        $config = get_post_meta($post_id,self::META_KEY,true);

        return is_array($config) ? $config : [
            'forms'     => [],
            'features'  => [],
            'field_map' => []
        ];
    }

    /* ---------------------------------------------------------------------
     * FORMS UI
     * --------------------------------------------------------------------- */

    /**
     * Render form selection dropdowns
     *
     * Pulls forms dynamically from Gravity Forms
     */
    public function forms_box($post){

        // Security nonce (validated in save handler)
        wp_nonce_field('pcpi_save','pcpi_nonce');

	echo '<p class="pcpi-help">
	Select the forms used in this workflow.
	<br><strong>Applicant</strong>: Initial intake form (optional).
	<br><strong>Questionnaire</strong>: Main form completed by the applicant.
	<br><strong>Review</strong>: Staff review form used to finalize and generate results.
	</p>';

        $config = $this->get_config($post->ID);

        // Get all Gravity Forms
        $forms = class_exists('GFAPI') ? \GFAPI::get_forms() : [];

        $this->dropdown('Applicant','pcpi_forms[applicant]',$config['forms']['applicant'] ?? '',$forms);
        $this->dropdown('Questionnaire','pcpi_forms[questionnaire]',$config['forms']['questionnaire'] ?? '',$forms);
        $this->dropdown('Review','pcpi_forms[review]',$config['forms']['review'] ?? '',$forms);
    }

    /**
     * Render a dropdown for form selection
     */
    private function dropdown($label,$name,$selected,$forms){

        echo "<p><strong>{$label}</strong></p>";
        echo "<select name='{$name}' class='pcpi-form-select' style='width:100%'>";
        echo "<option value=''>-- Select --</option>";

foreach ( $forms as $form ) {

    $form_id = $form['id'];
    $is_nested = false;

    // ✅ FAST CHECK (meta)
    if ( function_exists('gform_get_meta') ) {
        $parent = gform_get_meta( $form_id, '_gpnf_parent_form_id' );
        if ( ! empty( $parent ) ) {
            $is_nested = true;
        }
    }

    // 🔍 FALLBACK CHECK (field scan)
    if ( ! $is_nested ) {

        foreach ( $forms as $parent_form ) {

            if ( empty( $parent_form['fields'] ) ) continue;

            foreach ( $parent_form['fields'] as $field ) {

                if (
                    isset( $field->type ) &&
                    $field->type === 'form' &&
                    isset( $field->gpnfForm ) &&
                    intval( $field->gpnfForm ) === $form_id
                ) {
                    $is_nested = true;
                    break 2;
                }
            }
        }
    }

    // 🚫 SKIP nested forms
    if ( $is_nested ) {
        continue;
    }

    $sel = selected( $selected, $form_id, false );

    echo "<option value='{$form_id}' {$sel}>
        {$form['title']} (ID {$form_id})
    </option>";
}

        echo "</select>";
    }

    /* ---------------------------------------------------------------------
     * FEATURES UI
     * --------------------------------------------------------------------- */

    /**
     * Render feature toggle checkboxes
     *
     * These control workflow behavior dynamically
     */
    public function features_box($post){

        $config = $this->get_config($post->ID);
        
        echo '<p class="pcpi-help">
	These options enhance how the questionnaire behaves for users. 
	They do not change the workflow itself, only the user experience.
	</p>';

        $features = $config['features'] ?? [];

        $this->checkbox('auto_scroll_radios','Auto scroll to the next question',$features);
        $this->checkbox('mark_all_as_no','Mark all questions as no',$features);
    }

    /**
     * Render a single checkbox toggle
     */
    private function checkbox($key,$label,$features){

        $checked = !empty($features[$key]) ? 'checked' : '';

        echo "<p>
            <label>
                <input type='checkbox' name='pcpi_features[{$key}]' value='1' {$checked}>
                {$label}
            </label>
        </p>";
    }

    /* ---------------------------------------------------------------------
     * FIELD MAPPING UI
     * --------------------------------------------------------------------- */

    /**
     * Render field mapping table
     *
     * Each row represents a logical field mapped across:
     * - Applicant
     * - Questionnaire
     * - Review
     * - PDF
     */
    public function fieldmap_box($post){
    
    	$enabled = !empty($config['field_map_enabled']);

		echo '<p class="pcpi-help">
		By default, fields are automatically matched using Admin Labels.
		Enable this option only if you need to override the automatic mapping.
		</p>';
		
		echo '<p style="margin-bottom:10px;">
    		<label>
        		<input type="checkbox" id="pcpi-fieldmap-toggle" name="pcpi_field_map_enabled" value="1" ' 
        		. checked($enabled, true, false) . '>
        		<strong>Customize Field Mapping (Advanced)</strong>
    		</label>
		</p>';

        $config = $this->get_config($post->ID);
        $map = $config['field_map'] ?? [];

	echo '<div id="pcpi-fieldmap-container" style="' . ( $enabled ? '' : 'display:none;' ) . '">';
	
	echo '<button type="button" class="button button-primary" id="pcpi-add-row">+ Add Row</button>';
	
        echo '<table class="widefat" id="pcpi-fieldmap">
            <thead>
                <tr>
                    <th>Key</th>
                    <th>Applicant</th>
                    <th>Questionnaire</th>
                    <th>Review</th>
                    <th>PDF</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>';

        foreach($map as $key => $row){

            echo "<tr>
                <td><input type='text' name='pcpi_map[key][]' value='".esc_attr($key)."'></td>
                <td><select class='pcpi-field applicant' name='pcpi_map[applicant][]'></select></td>
                <td><select class='pcpi-field questionnaire' name='pcpi_map[questionnaire][]'></select></td>
                <td><select class='pcpi-field review' name='pcpi_map[review][]'></select></td>
                <td><input type='text' name='pcpi_map[pdf][]' value='".esc_attr($row['pdf'] ?? '')."'></td>
                <td><button type='button' class='button pcpi-remove'>X</button></td>
            </tr>";
        }

        echo '</tbody></table>';
        echo '</div>';
    }
    
    public function connections_box($post){

    $config = $this->get_config($post->ID);
    
    echo '<p class="pcpi-help">
	Define how entries are linked between forms.
	<br><strong>Questionnaire → Applicant</strong>: Stores which applicant this questionnaire belongs to.
	<br><strong>Review → Questionnaire</strong>: Links the review to the questionnaire entry.
	<br><strong>Review → Applicant</strong>: Links the review directly to the applicant.
	</p>';

    $forms = class_exists('GFAPI') ? \GFAPI::get_forms() : [];

    $q_form = $config['forms']['questionnaire'] ?? 0;
    $r_form = $config['forms']['review'] ?? 0;

    echo '<p class="pcpi-sub-label">Questionnaire → Applicant Field</strong></p>';
    $this->field_dropdown($q_form, 'pcpi_rel[q_to_app]', $config['relationships']['questionnaire_parent_applicant_field_id'] ?? '');

    echo '<p class="pcpi-sub-label">Review → Questionnaire Field</strong></p>';
    $this->field_dropdown($r_form, 'pcpi_rel[r_to_q]', $config['relationships']['review_parent_questionnaire_field_id'] ?? '');

    echo '<p class="pcpi-sub-label">Review → Applicant Field</strong></p>';
    $this->field_dropdown($r_form, 'pcpi_rel[r_to_app]', $config['relationships']['review_parent_applicant_field_id'] ?? '');
}

public function behavior_box($post){

    $config = $this->get_config($post->ID);
    
    echo '<p class="pcpi-help">
	These settings control how the workflow operates.
	<br><strong>Workflow Selector Field</strong>: Determines which workflow is used based on the applicant form.
	<br><strong>Has Review</strong>: Enables a staff review step after the questionnaire is completed.
	<br><strong>Entry Mode</strong>: Controls how the form is presented to the user (e.g. kiosk mode for guided entry).
	</p>';

    $app_form = $config['forms']['applicant'] ?? 0;

    echo '<p><strong>Workflow Selector Field</strong></p>';
    $this->field_dropdown($app_form, 'pcpi_behavior[resolver]', $config['resolver']['field_id'] ?? '');

    echo '<p><label><input type="checkbox" name="pcpi_behavior[has_review]" value="1" '
        . checked(!empty($config['forms']['review']), true, false)
        . '> Has Review</label></p>';

    echo '<p><strong>Entry Mode</strong></p>';
    echo '<select name="pcpi_behavior[entry_mode]" style="width:100%">
        <option value="">Standard</option>
        <option value="kiosk" '.selected($config['entry_mode'] ?? '', 'kiosk', false).'>Kiosk</option>
    </select>';
}

public function pages_box($post){

    $config = $this->get_config($post->ID);
    
    echo '<p class="pcpi-help">
	Select the pages used for this workflow.
	<br>These pages are used to route users through the process.
	<br><strong>Note:</strong> The system uses the page URL (path) behind the scenes (e.g. <code>/form-questionnaire/</code>).
	</p>';

    $pages = get_pages();

    echo '<p class="pcpi-sub-label">Questionnaire Page</strong></p>';
    $this->page_dropdown($pages, 'pcpi_pages[questionnaire]', $config['routes']['questionnaire'] ?? '');

    echo '<p class="pcpi-sub-label">Review Page</strong></p>';
    $this->page_dropdown($pages, 'pcpi_pages[review]', $config['routes']['review'] ?? '');
}

private function field_dropdown($form_id, $name, $selected){

    echo "<select name='{$name}' style='width:100%'>";
    echo "<option value=''>-- Select Field --</option>";

    if ( class_exists('GFAPI') && $form_id ) {

        $form = \GFAPI::get_form($form_id);

        if (!empty($form['fields'])) {
            foreach ($form['fields'] as $field) {
                echo "<option value='{$field->id}' "
                    . selected($selected, $field->id, false)
                    . ">{$field->label} ({$field->id})</option>";
            }
        }
    }

    echo "</select>";
}

private function page_dropdown($pages, $name, $selected){

    echo "<select name='{$name}' style='width:100%'>";
    echo "<option value=''>-- Select Page --</option>";

    foreach($pages as $p){
        echo "<option value='".esc_url(get_permalink($p->ID))."' "
            . selected($selected, get_permalink($p->ID), false)
            . ">{$p->post_title}</option>";
    }

    echo "</select>";
}

    /* ---------------------------------------------------------------------
     * ASSETS
     * --------------------------------------------------------------------- */

    /**
     * Enqueue admin scripts for workflow editor
     *
     * Loads only on workflow CPT screen
     */
    public function enqueue(){

        $screen = get_current_screen();

        if(!$screen || $screen->post_type !== 'pcpi_workflow') return;

        $base = PCPI_WF_URL . 'assets/js/';
        $path = PCPI_WF_PATH . 'assets/js/';
        
        wp_enqueue_style(
    	'pcpi-admin-css',
    	PCPI_WF_URL . 'assets/css/admin.css',
    	[],
    	filemtime( PCPI_WF_PATH . 'assets/css/admin.css' )
	);

        wp_enqueue_script('pcpi-admin',$base.'admin.js',['jquery'],filemtime($path.'admin.js'),true);
        wp_enqueue_script('pcpi-forms',$base.'forms.js',['jquery','pcpi-admin'],filemtime($path.'forms.js'),true);
        wp_enqueue_script('pcpi-fieldmap',$base.'field-map.js',['jquery','pcpi-admin'],filemtime($path.'field-map.js'),true);

        wp_localize_script('pcpi-admin','pcpiAjax',[
            'url'=>admin_url('admin-ajax.php')
        ]);
    }
}

add_action('admin_head', function(){

    $screen = get_current_screen();

    if ($screen->post_type !== 'pcpi_workflow') return;

    echo '<style>
        #slugdiv,
        #authordiv,
        #commentstatusdiv,
        #commentsdiv,
        #trackbacksdiv {
            display:none !important;
        }
    </style>';
});