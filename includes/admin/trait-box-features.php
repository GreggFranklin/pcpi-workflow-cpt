<?php
trait PCPI_WF_Box_Features {

    public function features_box($post){

        $config = $this->get_config($post->ID);

        echo '<p class="pcpi-help">
        These options enhance how users interact with the form.
        </p>';

        $features = $config['features'] ?? [];

        $this->checkbox('auto_scroll_radios','Auto scroll to the next question',$features);
        $this->checkbox('mark_all_as_no','Mark all questions as no',$features);
    }

    private function checkbox($key,$label,$features){

        $checked = !empty($features[$key]) ? 'checked' : '';

        echo "<p><label>
            <input type='checkbox' name='pcpi_features[{$key}]' value='1' {$checked}>
            {$label}
        </label></p>";
    }
}