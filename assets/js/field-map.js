jQuery(function($){

    $('#pcpi-add-row').on('click',function(){

        $('#pcpi-fieldmap tbody').append(`
        <tr>
        <td><input type="text" name="pcpi_map[key][]"></td>
        <td><select class="pcpi-field applicant" name="pcpi_map[applicant][]"></select></td>
        <td><select class="pcpi-field questionnaire" name="pcpi_map[questionnaire][]"></select></td>
        <td><select class="pcpi-field review" name="pcpi_map[review][]"></select></td>
        <td><input type="text" name="pcpi_map[pdf][]"></td>
        <td><button type="button" class="button pcpi-remove">X</button></td>
        </tr>
        `);

    });

    $(document).on('click','.pcpi-remove',function(){
        $(this).closest('tr').remove();
    });
    
    $('#pcpi-fieldmap-toggle').on('change', function(){
        if ($(this).is(':checked')) {
            $('#pcpi-fieldmap-container').slideDown(150);
        } else {
            $('#pcpi-fieldmap-container').slideUp(150);
        }
    });

});