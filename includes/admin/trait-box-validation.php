<?php
trait PCPI_WF_Box_Validation {

    public function validation_box($post){

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

        if ( empty($config['routes']['questionnaire']) ) {
            $warnings[] = 'Questionnaire page is not selected.';
        }

        if ( empty($errors) && empty($warnings) ) {
            echo '<div class="pcpi-notice success"><strong>✓ Workflow looks good</strong></div>';
            return;
        }

        if ($errors) {
            echo '<div class="pcpi-notice error"><strong>Errors</strong><ul>';
            foreach ($errors as $e) echo "<li>{$e}</li>";
            echo '</ul></div>';
        }

        if ($warnings) {
            echo '<div class="pcpi-notice warning"><strong>Warnings</strong><ul>';
            foreach ($warnings as $w) echo "<li>{$w}</li>";
            echo '</ul></div>';
        }
    }
}