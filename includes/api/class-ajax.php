<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class PCPI_WF_Ajax {

    public function __construct() {
        add_action('wp_ajax_pcpi_get_form_fields', [$this,'get_fields']);
    }

    public function get_fields() {

        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
        $fields = [];

        if(class_exists('GFAPI') && $form_id){
            $form = GFAPI::get_form($form_id);
            foreach($form['fields'] as $f){
                $fields[] = ['id'=>$f->id,'label'=>$f->label];
            }
        }

        wp_send_json($fields);
    }
}