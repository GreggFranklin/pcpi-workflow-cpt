<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class PCPI_WF_Repository {

    public static function get_all(){

        $posts = get_posts([
            'post_type'=>'pcpi_workflow',
            'numberposts'=>-1,
            'post_status'=>'publish'
        ]);

        $out = [];

        foreach($posts as $p){
            $config = get_post_meta($p->ID,'_pcpi_workflow_config',true);
            if(!$config) continue;

            $out[] = [
                'key'=>sanitize_key($p->post_title),
                'config'=>$config
            ];
        }

        return $out;
    }
}