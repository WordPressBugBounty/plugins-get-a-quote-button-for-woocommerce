+(function($) {

    "use strict";
    
    var WPB_GQB_Quote_Button_Widget_Handler = function($scope, $){

        var $_wpb_quote_button = $scope.find(".wpb-get-a-quote-button-form-fire");


        $_wpb_quote_button.on("click", function(e){
            e.preventDefault();

            var button  = $(this),
            id          = button.attr('data-id'),
            post_id     = button.attr('data-post_id'),
            form_style  = button.attr('data-form_style') ? !0 : !1,
            width       = button.attr('data-width'),
            variations      = button.attr('data-variations'),
            variation_sku   = button.attr('data-variation-sku');

            wp.ajax.send( {
                ajax_option : 'fire_wpb_gqb_contact_form',
                data: {
                    action: 'fire_contact_form',
                    contact_form_id: id,
                    wpb_post_id: post_id,
                    wpb_gqb_variations: variations,
                    wpb_gqb_variation_sku: variation_sku,
                    _wpnonce: WPB_GQB_Vars.nonce
                },
                beforeSend : function ( xhr ) {
					button.addClass('wpb-gqf-btn-loading');
				},
                success: function( res ) {
                    button.removeClass('wpb-gqf-btn-loading');
                    Swal.fire({
                        html: res,
                        showConfirmButton: false,
                        customClass: {
                            container: 'wpb-gqf-form-style-' + form_style,
                        },
                        padding: '30px',
                        width: width,
                        showCloseButton: true,
                    });
                    
                    // For CF7 5.3.1 and before
                    if( typeof wpcf7.initForm === "function" ){
                        wpcf7.initForm( $('.wpcf7-form') );
                    }

                    // For CF7 5.4 and after
                    if( typeof wpcf7.init === "function" ){
                        document.querySelectorAll(".wpcf7 > form").forEach(function (e) {
                            return wpcf7.init(e);
                        });
                    }

                    // Add support for - Drag and Drop Multiple File Upload – Contact Form 7
                    if( typeof initDragDrop === "function" ){
                        window.initDragDrop();
                    }

                    // Add post ID to the popup form
                    $("[name='_wpcf7_container_post']").val( post_id );
                },
                error: function(error) {
                    alert( error );
                }
            });
        });

        /**
         * Hide if variation has no stock 
         */
        
        $(document).on( 'found_variation', 'form.variations_form', function( event, variation ) { 
            if( !variation.is_in_stock ){
                $('.wpb-gqb-product-type-variable').addClass('wpb-gqb-product-type-variable-show');
            }else{
                $('.wpb-gqb-product-type-variable').removeClass('wpb-gqb-product-type-variable-show');
            }
        });

        $(document).on( 'click', '.reset_variations', function( event ) {
            $('.wpb-gqb-product-type-variable').removeClass('wpb-gqb-product-type-variable-show');
        });


        /**
         * Add Product variations selected data to the quote button
         */
        
        $(document).on( 'found_variation', 'form.variations_form', function( event, variation ) {
            $('.wpb-gqb-product-type-variable').attr( 'data-variations', JSON.stringify(variation.attributes) );
            $('.wpb-gqb-product-type-variable').attr( 'data-variation-sku', JSON.stringify(variation.sku) );
        });

        $(document).on( 'click', '.reset_variations', function( event ) {
            $('.wpb-gqb-product-type-variable').removeAttr('data-variations');
            $('.wpb-gqb-product-type-variable').removeAttr('data-variation-sku');
        });


        /**
         * Gray out quote button for variable products
         */

        $(document).on( 'hide_variation', 'form.variations_form', function( event ) {
            event.preventDefault();
            $('.wpb-gqb-product-type-variable.wpb-get-a-quote-button-variable-gray-out-on').addClass( 'wpb-gqb-btn-disabled' );
            $('.wpb-gqb-product-type-variable.wpb-get-a-quote-button-variable-gray-out-on').attr( 'disabled', 'disabled' );
        });

        $(document).on( 'show_variation', 'form.variations_form', function( event ) {
            event.preventDefault();
            $('.wpb-gqb-product-type-variable.wpb-get-a-quote-button-variable-gray-out-on').removeClass( 'wpb-gqb-btn-disabled' );
            $('.wpb-gqb-product-type-variable.wpb-get-a-quote-button-variable-gray-out-on').removeAttr( 'disabled' );
        });
    
    }

    //Elementor JS Hooks
    $(window).on('elementor/frontend/init', function () {
      elementorFrontend.hooks.addAction('frontend/element_ready/wpb_gqb_button.default', WPB_GQB_Quote_Button_Widget_Handler);
    });

})(jQuery);