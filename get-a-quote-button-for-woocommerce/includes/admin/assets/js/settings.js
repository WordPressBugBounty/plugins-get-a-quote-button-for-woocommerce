// Select button group
// https://www.jqueryscript.net/form/Bootstrap-Plugin-To-Convert-Select-Boxes-Into-Button-Groups-select-togglebutton-js.html

(function($) {
    // Define the togglebutton plugin.
    $.fn.togglebutton = function(opts) {
        // Apply the users options if exists.
        var settings = $.extend( {}, $.fn.togglebutton.defaults, opts);

        // For each select element.
        this.each(function() {
            var self = $(this);
            var multiple = this.multiple;

            // Retrieve all options.
            var options = self.children('option');
            // Create an array of buttons with the value of select options.
            var buttons = options.map(function(index, opt) {
                var button = $("<button type='button' class='btn btn-default'></button>")
                .prop('value', opt.value)
                .text(opt.text);

                // Add an `active` class if the option has been selected.
                if (opt.selected)
                    button.addClass("active");

                // Return the button.
                return button[0];
            });

            // For each button, implement the click button removing and adding
            // `active` class to simulate the toggle effect. And also change the
            // select selected option.
            buttons.each(function(index, btn) {
                $(btn).click(function() {
                    if (!multiple) {
                        // For single-select: just activate the clicked button
                        buttons.removeClass('active');
                        $(btn).addClass('active');
                        // Set the select value directly and reliably
                        self.val(btn.value).trigger('change');
                    } else {
                        // For multi-select: toggle the clicked button
                        $(btn).toggleClass('active');
                        var total = [];
                        buttons.each(function(i, b) {
                            if ($(b).hasClass('active')) {
                                total.push(b.value);
                            }
                        });
                        self.val(total).trigger('change');
                    }
                });
            });

            // Group all the buttons in a `div` element.
            var btnGroup = $("<div class='ui-segment'>").append(buttons);
            // Include the buttons group after the select element.
            self.after(btnGroup);
            // Hide the display element.
            self.hide();
        });
    };

    // Set the defaults options of the plugin.
    $.fn.togglebutton.defaults = {
    };

    $('form').on('submit', function() {
        $(this).find('select').each(function() {
            console.log($(this).attr('name') + ' = ' + $(this).val());
        });
    });

}(jQuery));


(function( $ ) {

    //Initiate Color Picker
    $('.wp-color-picker-field').wpColorPicker();

    // Switches option sections
    $('.group').hide();
    var activetab = '';
    if (typeof(localStorage) != 'undefined' ) {
        activetab = localStorage.getItem("activetab");
    }
    //if url has section id as hash then set it as active or override the current local storage value
    if(window.location.hash){
        activetab = window.location.hash;
        if (typeof(localStorage) != 'undefined' ) {
            localStorage.setItem("activetab", activetab);
        }
    }
    if (activetab != '' && $(activetab).length ) {
        $(activetab).fadeIn();
    } else {
        $('.group:first').fadeIn();
    }
    $('.group .collapsed').each(function(){
        $(this).find('input:checked').parent().parent().parent().nextAll().each(
        function(){
            if ($(this).hasClass('last')) {
                $(this).removeClass('hidden');
                return false;
            }
            $(this).filter('.hidden').removeClass('hidden');
        });
    });
    if (activetab != '' && $(activetab + '-tab').length ) {
        $(activetab + '-tab').addClass('nav-tab-active');
    }
    else {
        $('.nav-tab-wrapper a:first').addClass('nav-tab-active');
    }
    $('.nav-tab-wrapper a').click(function(evt) {
        $('.nav-tab-wrapper a').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active').blur();
        var clicked_group = $(this).attr('href');


        if (typeof(localStorage) != 'undefined' ) {
            localStorage.setItem("activetab", $(this).attr('href'));
        }
        $('.group').hide();
        $(clicked_group).fadeIn();
        evt.preventDefault();
    });

    $('.wpsa-browse').on('click', function (event) {
        event.preventDefault();

        var self = $(this);

        // Create the media frame.
        var file_frame = wp.media.frames.file_frame = wp.media({
            title: self.data('uploader_title'),
            button: {
                text: self.data('uploader_button_text'),
            },
            multiple: false
        });

        file_frame.on('select', function () {
            attachment = file_frame.state().get('selection').first().toJSON();
            self.prev('.wpsa-url').val(attachment.url).change();
        });

        // Finally, open the modal
        file_frame.open();
    });

    $('.wpb-select-buttons').togglebutton();

    // Condition
    $(document).ready(function($) {
        function toggleFormFields() {
            var selected = $('#form_settings\\[wpb_gqb_form_plugin\\]').val();

            if (selected === 'wpcf7') {
                $('.wpb_gqb_cf7_form_id, .wpb_gqb_cf7_form_name_attr, .wpb_gqb_force_cf7_scripts').show();
                $('.wpb_gqb_wpforms_form_id').hide();
            } else if (selected === 'wpforms') {
                $('.wpb_gqb_cf7_form_id, .wpb_gqb_cf7_form_name_attr, .wpb_gqb_force_cf7_scripts').hide();
                $('.wpb_gqb_wpforms_form_id').show();
            } else {
                $('.wpb_gqb_cf7_form_id, .wpb_gqb_wpforms_form_id').hide();
            }
        }

        // Initial toggle
        toggleFormFields();

        // Bind change event
        $('#form_settings\\[wpb_gqb_form_plugin\\]').on('change', toggleFormFields);
    });

})( jQuery );