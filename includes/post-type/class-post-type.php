<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PCPI_WF_Post_Type {

    public function __construct() {
        add_action('init', [$this,'register']);
    }

public function register() {

    register_post_type('pcpi_workflow', [

        'labels' => [

            'name'               => 'Workflows',
            'singular_name'      => 'Workflow',
            'add_new'            => 'Add Workflow',
            'add_new_item'       => 'Add New Workflow',
            'edit_item'          => 'Edit Workflow',
            'new_item'           => 'New Workflow',
            'view_item'          => 'View Workflow',
            'search_items'       => 'Search Workflows',
            'not_found'          => 'No workflows found',
            'not_found_in_trash' => 'No workflows found in Trash',
            'all_items'          => 'All Workflows',
            'menu_name'          => 'Workflows',
            'name_admin_bar'     => 'Workflow',

        ],

        'public'       => false,
        'show_ui'      => true,
        'menu_icon'    => 'dashicons-randomize',
        'supports' => ['title'],

    ]);
}
}

add_filter('enter_title_here', function($title, $post){

    if ($post->post_type === 'pcpi_workflow') {
        return 'Enter Workflow Name';
    }

    return $title;

}, 10, 2);