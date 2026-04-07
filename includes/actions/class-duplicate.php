<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PCPI_WF_Duplicate {

    public function __construct() {
        add_filter('post_row_actions', [$this,'add_duplicate_link'], 10, 2);
        add_action('admin_action_pcpi_duplicate_workflow', [$this,'duplicate']);
    }

    public function add_duplicate_link($actions, $post){

        if ($post->post_type !== 'pcpi_workflow') return $actions;

        $url = wp_nonce_url(
            admin_url('admin.php?action=pcpi_duplicate_workflow&post='.$post->ID),
            'pcpi_duplicate_'.$post->ID
        );

        $actions['duplicate'] = "<a href='{$url}'>Duplicate</a>";

        return $actions;
    }

    public function duplicate(){

        $post_id = absint($_GET['post']);

        if (!wp_verify_nonce($_GET['_wpnonce'], 'pcpi_duplicate_'.$post_id)) {
            wp_die('Security check failed');
        }

        $post = get_post($post_id);

        $new_id = wp_insert_post([
            'post_title' => $post->post_title . ' (Copy)',
            'post_type'  => 'pcpi_workflow',
            'post_status'=> 'draft'
        ]);

        // Copy config
        $config = get_post_meta($post_id, '_pcpi_workflow_config', true);
        update_post_meta($new_id, '_pcpi_workflow_config', $config);

        wp_redirect(admin_url('post.php?post='.$new_id.'&action=edit'));
        exit;
    }
}