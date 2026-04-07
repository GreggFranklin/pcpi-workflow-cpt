<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PCPI_WF_Meta_Boxes {

    const META_KEY = '_pcpi_workflow_config';

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_boxes' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
    }

    public function add_boxes() {

        add_meta_box('pcpi_validation','Workflow Status',[ $this,'validation_box'],'pcpi_workflow','normal','high');

        add_meta_box('pcpi_forms','Forms',[ $this,'forms_box'],'pcpi_workflow','normal','high');
        add_meta_box('pcpi_behavior','Workflow Behavior',[ $this,'behavior_box'],'pcpi_workflow','normal');
        add_meta_box('pcpi_pages','Pages',[ $this,'pages_box'],'pcpi_workflow','normal');

        add_meta_box('pcpi_connections','Connections',[ $this,'connections_box'],'pcpi_workflow','normal');
        add_meta_box('pcpi_features','Features',[ $this,'features_box'],'pcpi_workflow','normal');
        add_meta_box('pcpi_fieldmap','Field Mapping',[ $this,'fieldmap_box'],'pcpi_workflow','normal');
    }

    private function get_config($post_id){
        $config = get_post_meta($post_id,self::META_KEY,true);
        return is_array($config) ? $config : [
            'forms'=>[],
            'features'=>[],
            'field_map'=>[]
        ];
    }

    /* ================= VALIDATION ================= */

    public function validation_box($post){
    
    	// Do not validate brand new (unsaved) workflows
	if ( empty($post->ID) || $post->post_status === 'auto-draft' ) {
    		echo '<div class="pcpi-help">Save the workflow to see validation status.</div>';
    	return;
	}

        $config = $this->get_config($post->ID);

        $errors = [];
        $warnings = [];

        if ( empty($config['forms']['questionnaire']) ) {
            $errors[] = 'Questionnaire form is not selected.';
        }

        if ( empty($config['resolver']['field_id']) ) {
            $warnings[] = 'Workflow selector field is not set.';
        }

        if ( !empty($config['forms']['review']) && empty($config['relationships']['review_parent_questionnaire_field_id']) ) {
            $warnings[] = 'Review → Questionnaire connection is not set.';
        }

        if ( !empty($config['entry_mode']) && empty($config['forms']['review']) ) {
            $warnings[] = 'Review is enabled but no review form is selected.';
        }

        if ( empty($config['routes']['questionnaire']) ) {
            $warnings[] = 'Questionnaire page is not selected.';
        }

        if ( empty($errors) && empty($warnings) ) {
            echo '<div class="pcpi-notice success"><strong>✓ Workflow looks good</strong></div>';
            return;
        }

        if ( !empty($errors) ) {
            echo '<div class="pcpi-notice error"><strong>Errors</strong><ul>';
            foreach ($errors as $e) echo '<li>'.$e.'</li>';
            echo '</ul></div>';
        }

        if ( !empty($warnings) ) {
            echo '<div class="pcpi-notice warning"><strong>Warnings</strong><ul>';
            foreach ($warnings as $w) echo '<li>'.$w.'</li>';
            echo '</ul></div>';
        }
    }

    /* ================= FORMS ================= */

    public function forms_box($post){

        wp_nonce_field('pcpi_save','pcpi_nonce');

        echo '<p class="pcpi-help">Select the forms used in this workflow.</p>';

        $config = $this->get_config($post->ID);
        $forms = class_exists('GFAPI') ? \GFAPI::get_forms() : [];

        $this->dropdown('Applicant','pcpi_forms[applicant]',$config['forms']['applicant'] ?? '',$forms);
        $this->dropdown('Questionnaire','pcpi_forms[questionnaire]',$config['forms']['questionnaire'] ?? '',$forms);
        $this->dropdown('Review','pcpi_forms[review]',$config['forms']['review'] ?? '',$forms);
    }

    private function dropdown($label,$name,$selected,$forms){

        echo "<p class='pcpi-sub-label'>{$label}</p>";
        echo "<select name='{$name}' style='width:100%'>";
        echo "<option value=''>-- Select --</option>";

        foreach ( $forms as $form ) {

            $form_id = $form['id'];

            if ( function_exists('gform_get_meta') && gform_get_meta( $form_id, '_gpnf_parent_form_id' ) ) {
                continue;
            }

            echo "<option value='{$form_id}' ".selected($selected,$form_id,false).">
                {$form['title']} (ID {$form_id})
            </option>";
        }

        echo "</select>";
    }

    /* ================= FEATURES ================= */

    public function features_box($post){

        $config = $this->get_config($post->ID);

        echo '<p class="pcpi-help">
        These options enhance how the questionnaire behaves for users.
        They do not change the workflow logic, only how users interact with the form.
        </p>';

        $features = $config['features'] ?? [];

        $this->checkbox('auto_scroll_radios','Auto scroll to the next question',$features);
        $this->checkbox('mark_all_as_no','Mark all questions as no',$features);
    }

    private function checkbox($key,$label,$features){

        $checked = !empty($features[$key]) ? 'checked' : '';

        echo "<p><label>
            <input type='checkbox' name='pcpi_features[{$key}]' value='1' {$checked}>
            {$label}
        </label></p>";
    }

    /* ================= FIELD MAP ================= */

    public function fieldmap_box($post){

        $config = $this->get_config($post->ID);
        $enabled = !empty($config['field_map_enabled']);

        echo '<p class="pcpi-help">
        Fields are automatically matched using Admin Labels.
        Enable customization only if you need to override this behavior.
        </p>';

        echo '<p><label>
            <input type="checkbox" id="pcpi-fieldmap-toggle" name="pcpi_field_map_enabled" value="1" '
            . checked($enabled,true,false).'>
            <strong>Customize Field Mapping (Advanced)</strong>
        </label></p>';

        $map = $config['field_map'] ?? [];

        echo '<div id="pcpi-fieldmap-container" style="'.($enabled?'':'display:none;').'">';

        echo '<button type="button" class="button button-primary" id="pcpi-add-row">+ Add Row</button>';

        echo '<table class="widefat" id="pcpi-fieldmap"><tbody>';

        foreach($map as $key=>$row){
            echo "<tr>
                <td><input type='text' name='pcpi_map[key][]' value='".esc_attr($key)."'></td>
                <td><input type='text' name='pcpi_map[pdf][]' value='".esc_attr($row['pdf'] ?? '')."'></td>
                <td><button type='button' class='button pcpi-remove'>X</button></td>
            </tr>";
        }

        echo '</tbody></table></div>';
    }

    /* ================= CONNECTIONS ================= */

    public function connections_box($post){

        $config = $this->get_config($post->ID);

        echo '<p class="pcpi-help">Define how entries are linked between forms.</p>';

        $q_form = $config['forms']['questionnaire'] ?? 0;
        $r_form = $config['forms']['review'] ?? 0;

        echo '<p class="pcpi-sub-label">Questionnaire → Applicant Field</p>';
        $this->field_dropdown($q_form,'pcpi_rel[q_to_app]',$config['relationships']['questionnaire_parent_applicant_field_id'] ?? '');

        echo '<p class="pcpi-sub-label">Review → Questionnaire Field</p>';
        $this->field_dropdown($r_form,'pcpi_rel[r_to_q]',$config['relationships']['review_parent_questionnaire_field_id'] ?? '');

        echo '<p class="pcpi-sub-label">Review → Applicant Field</p>';
        $this->field_dropdown($r_form,'pcpi_rel[r_to_app]',$config['relationships']['review_parent_applicant_field_id'] ?? '');
    }

    /* ================= BEHAVIOR ================= */

    public function behavior_box($post){

        $config = $this->get_config($post->ID);

        echo '<p class="pcpi-help">These settings control how the workflow operates.</p>';

        $app_form = $config['forms']['applicant'] ?? 0;

        echo '<p class="pcpi-sub-label">Workflow Selector (Applicant Field)</p>';
        $this->field_dropdown($app_form,'pcpi_behavior[resolver]',$config['resolver']['field_id'] ?? '');

        echo '<p style="margin:10px 0;">
            <label>
                <input type="checkbox" name="pcpi_behavior[has_review]" value="1" '
                . checked(!empty($config['forms']['review']), true, false)
                . '> Has Review
            </label>
        </p>';

        echo '<p class="pcpi-sub-label">Entry Mode</p>';
        echo '<select name="pcpi_behavior[entry_mode]" style="width:100%">
            <option value="">Standard</option>
            <option value="kiosk" '.selected($config['entry_mode'] ?? '', 'kiosk', false).'>Kiosk</option>
        </select>';
    }

    /* ================= PAGES ================= */

    public function pages_box($post){

        $config = $this->get_config($post->ID);

        echo '<p class="pcpi-help">
        Select the pages used in this workflow. The system uses the page URL path behind the scenes.
        </p>';

        $pages = get_pages();

        echo '<p class="pcpi-sub-label">Questionnaire Page</p>';
        $this->page_dropdown($pages,'pcpi_pages[questionnaire]',$config['routes']['questionnaire'] ?? '');

        if (!empty($config['routes']['questionnaire'])) {
            echo '<div class="pcpi-help"><code>'.esc_html(parse_url($config['routes']['questionnaire'],PHP_URL_PATH)).'</code></div>';
        }

        echo '<p class="pcpi-sub-label">Review Page</p>';
        $this->page_dropdown($pages,'pcpi_pages[review]',$config['routes']['review'] ?? '');

        if (!empty($config['routes']['review'])) {
            echo '<div class="pcpi-help"><code>'.esc_html(parse_url($config['routes']['review'],PHP_URL_PATH)).'</code></div>';
        }
    }

    private function field_dropdown($form_id,$name,$selected){

        echo "<select name='{$name}' style='width:100%'>";
        echo "<option value=''>-- Select Field --</option>";

        if ( class_exists('GFAPI') && $form_id ) {

            $form = \GFAPI::get_form($form_id);

            if (!empty($form['fields'])) {
                foreach ($form['fields'] as $field) {
                    echo "<option value='{$field->id}' "
                        . selected($selected,$field->id,false)
                        . ">{$field->label} ({$field->id})</option>";
                }
            }
        }

        echo "</select>";
    }

    private function page_dropdown($pages,$name,$selected){

        echo "<select name='{$name}' style='width:100%'>";
        echo "<option value=''>-- Select Page --</option>";

        foreach($pages as $p){
            $url = get_permalink($p->ID);
            echo "<option value='".esc_url($url)."' "
                . selected($selected,$url,false)
                . ">{$p->post_title}</option>";
        }

        echo "</select>";
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
}