<?php
if ( ! defined( 'ABSPATH' ) ) exit;

trait PCPI_WF_Box_Dashboard_Actions {

    public function dashboard_actions_box($post){

        $config = $this->get_config($post->ID);
        $actions = $config['dashboard']['actions'] ?? [];

        echo '<p class="pcpi-help">
        Define which actions are available to staff for this workflow.
        These actions appear in the Staff Dashboard and control what staff can do at each stage of the process.
        </p>';

        $this->action_checkbox('review', 'Review (Questionnaire in read-only mode)', $actions);
        $this->action_checkbox('generate_pdf', 'Summary (Generate PDF)', $actions);
        $this->action_checkbox('send', 'Send PDF (Send PDF to agency)', $actions);
        $this->action_checkbox('resend', 'Resend (Resend email with link to questionnaire)', $actions);
        $this->action_checkbox('delete', 'Delete (Remove Applicant)', $actions);
    }

    private function action_checkbox($key, $label, $actions){

	$default_checked = in_array($key, ['send', 'resend', 'delete'], true);

	$checked = isset($actions[$key])
    ? ! empty($actions[$key])
    : $default_checked;

        echo '<p>
            <label>
                <input type="checkbox" name="pcpi_dashboard_actions[' . esc_attr($key) . ']" value="1" ' . checked($checked, true, false) . '>
                ' . esc_html($label) . '
            </label>
        </p>';
    }
}