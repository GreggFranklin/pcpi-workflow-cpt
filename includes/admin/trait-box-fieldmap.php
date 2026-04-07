<?php
trait PCPI_WF_Box_Fieldmap {

    public function fieldmap_box($post){

        $config = $this->get_config($post->ID);
        $enabled = !empty($config['field_map_enabled']);

        echo '<p class="pcpi-help">
        Fields auto-map using Admin Labels unless overridden.
        </p>';

        echo '<p><label>
            <input type="checkbox" id="pcpi-fieldmap-toggle" name="pcpi_field_map_enabled" value="1" '
            . checked($enabled,true,false).'>
            Customize Field Mapping
        </label></p>';
    }
}