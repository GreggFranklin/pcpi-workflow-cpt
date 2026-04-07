<?php
trait PCPI_WF_Box_Forms {

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
        $is_nested = false;

        // 1. Direct GP Nested Forms meta check
        if ( function_exists('gform_get_meta') ) {
            $parent = gform_get_meta( $form_id, '_gpnf_parent_form_id' );
            if ( ! empty( $parent ) ) {
                $is_nested = true;
            }
        }

        // 2. Check if used as a nested form in ANY other form
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

        // Skip nested forms
        if ( $is_nested ) continue;

        echo "<option value='{$form_id}' ".selected($selected,$form_id,false).">
            {$form['title']} (ID {$form_id})
        </option>";
    }

    echo "</select>";
}
}