jQuery(function($){

    function loadFields(formId, select){
        if(!formId) return;

        $.post(pcpiAjax.url,{
            action:'pcpi_get_form_fields',
            form_id:formId
        },function(res){

            select.empty().append('<option value="">--</option>');

            res.forEach(f=>{
                select.append(`<option value="${f.id}">${f.label} (${f.id})</option>`);
            });

        });
    }

    $('.pcpi-form-select').on('change',function(){

        let val = $(this).val();

        $('.pcpi-field.applicant').each(function(){ loadFields(val,$(this)); });
        $('.pcpi-field.questionnaire').each(function(){ loadFields(val,$(this)); });
        $('.pcpi-field.review').each(function(){ loadFields(val,$(this)); });

    });

});