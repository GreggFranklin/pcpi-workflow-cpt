<?php
trait PCPI_WF_Box_Behavior {

public function behavior_box($post){

    $config = $this->get_config($post->ID);
    $app_form = $config['forms']['applicant'] ?? 0;

    echo '<p class="pcpi-help">These settings control how the workflow operates.</p>';

    // 🔥 GUIDED UX
    if ( empty($app_form) ) {
        echo '<div class="pcpi-help">
        Select and save an <strong>Applicant form</strong> in the Forms section first.
        </div>';
        return;
    }

    echo '<p class="pcpi-sub-label">Workflow Selector (Applicant Field)</p>';
    $this->field_dropdown($app_form,'pcpi_behavior[resolver]',$config['resolver']['field_id'] ?? '');

    echo '<p><label>
        <input type="checkbox" name="pcpi_behavior[has_review]" value="1" '
        . checked(!empty($config['forms']['review']), true, false)
        . '> Has Review
    </label></p>';

    echo '<p class="pcpi-sub-label">Entry Mode</p>';
    echo '<select name="pcpi_behavior[entry_mode]" style="width:100%">
        <option value="">Standard</option>
        <option value="kiosk" '.selected($config['entry_mode'] ?? '', 'kiosk', false).'>Kiosk</option>
    </select>';
}
}