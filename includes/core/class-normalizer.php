<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class PCPI_WF_Normalizer {

    public static function normalize($workflows){

        $n = [];

        foreach($workflows as $wf){

            $c = $wf['config'];

            $n[$wf['key']] = [
                'applicant_form_id'=>$c['forms']['applicant']??0,
                'questionnaire_form_id'=>$c['forms']['questionnaire']??0,
                'review_form_id'=>$c['forms']['review']??0,
                'features'=>$c['features']??[],
                'field_map'=>$c['field_map']??[],
            ];
        }

        return $n;
    }
}