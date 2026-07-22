(function( $ ) {

    'use strict';

    var defaultDataType     = $('select[name="product_data_type"]').find(":selected").val(),
        inputName           = $('form.tag-generator-panel[data-id="gqb_product_data"]').find('input[data-tag-part="name"]'),
        inputTag            = $('form.tag-generator-panel[data-id="gqb_product_data"]').find('input[data-tag-part="tag"]'),
        inputMailTag        = $('form.tag-generator-panel[data-id="gqb_product_data"]').find('strong[data-tag-part="mail-tag"]'),
        fieldsetFieldType   = $('form.tag-generator-panel[data-id="gqb_product_data"]').find('legend#tag-generator-panel-gqbproductdata-type-legend').closest('fieldset'),
        fieldsetFieldname   = $('form.tag-generator-panel[data-id="gqb_product_data"]').find('legend#tag-generator-panel-gqbproductdata-name-legend').closest('fieldset');

    $(fieldsetFieldType).hide();
    $(fieldsetFieldname).hide();

    if(defaultDataType){
        $( 'button[data-taggen="open-dialog"]' ).on( "click", function() {
          setTimeout(function() {
            inputName.val(defaultDataType);
            inputTag.val("[gqb_product_data "+defaultDataType+"]");
            inputMailTag.html("["+defaultDataType+"]");
          }, 30);
        });
    }

    $('select[name="product_data_type"]').on('change', function() {
        var input_name = $(this).closest('.control-box').find('input[data-tag-part="name"]');
        input_name.val(this.value);
    });
})( jQuery );