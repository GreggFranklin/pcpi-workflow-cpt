<?php
trait PCPI_WF_Box_Connections {

public function connections_box($post){

    $config = $this->get_config($post->ID);

    $q_form = $config['forms']['questionnaire'] ?? 0;
    $r_form = $config['forms']['review'] ?? 0;

    echo '<p class="pcpi-help">
    Define how entries are linked between forms.
    </p>';

    // 🔥 GUIDED UX
    if ( empty($q_form) ) {
        echo '<div class="pcpi-help">
        Select and save a <strong>Questionnaire form</strong> first.
        </div>';
        return;
    }

    echo '<p class="pcpi-sub-label">Questionnaire → Applicant Field</p>';
    $this->field_dropdown($q_form,'pcpi_rel[q_to_app]',$config['relationships']['questionnaire_parent_applicant_field_id'] ?? '');

    if ( empty($r_form) ) {
        echo '<div class="pcpi-help">
        Select a <strong>Review form</strong> to configure additional connections.
        </div>';
        return;
    }

    echo '<p class="pcpi-sub-label">Review → Questionnaire Field</p>';
    $this->field_dropdown($r_form,'pcpi_rel[r_to_q]',$config['relationships']['review_parent_questionnaire_field_id'] ?? '');

    echo '<p class="pcpi-sub-label">Review → Applicant Field</p>';
    $this->field_dropdown($r_form,'pcpi_rel[r_to_app]',$config['relationships']['review_parent_applicant_field_id'] ?? '');
}
}