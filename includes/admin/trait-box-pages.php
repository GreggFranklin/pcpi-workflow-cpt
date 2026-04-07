<?php
trait PCPI_WF_Box_Pages {

public function pages_box($post){

    $config = $this->get_config($post->ID);
    $q_form = $config['forms']['questionnaire'] ?? 0;

    echo '<p class="pcpi-help">
    Select pages used for routing. System uses the URL path.
    </p>';

    if ( empty($q_form) ) {
        echo '<div class="pcpi-help">
        Select and save a <strong>Questionnaire form</strong> first.
        </div>';
        return;
    }

    $pages = get_pages();

    echo '<p class="pcpi-sub-label">Questionnaire Page</p>';
    $this->page_dropdown($pages,'pcpi_pages[questionnaire]',$config['routes']['questionnaire'] ?? '');

    echo '<p class="pcpi-sub-label">Review Page</p>';
    $this->page_dropdown($pages,'pcpi_pages[review]',$config['routes']['review'] ?? '');
}
}