<?php
if ( ! defined( 'ABSPATH' ) ) exit;

trait PCPI_WF_Box_Helpers {

    protected function field_dropdown($form_id, $name, $selected){

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

    protected function page_dropdown($pages,$name,$selected){

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
}