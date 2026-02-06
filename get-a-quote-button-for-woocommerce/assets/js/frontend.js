+(function ($) {
    "use strict";

    /**
     * Executes all inline <script> tags inside a container
     * and reinitializes WPForms
     */
    function reinitWPForms(containerSelector = ".swal2-popup") {
        const $container = $(containerSelector);

        $container.find("script").each(function () {
            try {
                eval(this.textContent);
            } catch (e) {
                console.warn("WPB Inline Script Execution Error:", e);
            }
        });

        // Reinitialize WPForms
        if (window.wpforms && typeof window.wpforms.init === "function") {
            window.wpforms.init();
        }
    }


    /**
     * WPCF7 & WPForms Cloudflare Turnstile Support
     */
    function gqbTurnstileSupport(popupEl) {
        // Wait a short moment for the DOM to render
        setTimeout(function () {
            const formPlugin = $(popupEl).find("form.wpforms-form").length ? 'wpforms' : 
                   $(popupEl).find("form.wpcf7-form").length ? 'cf7' : 'unknown';

            if(formPlugin === 'wpforms') {
                // Init CAPTCHA for WPForms
                if ( 'undefined' !== typeof wpformsRecaptchaLoad ) {
                    wpformsRecaptchaLoad();
                }
            }else {
                // Check if Cloudflare Turnstile is loaded
                if (typeof turnstile !== "undefined") {
                    // === Contact Form 7 Support ===
                    $(popupEl)
                        .find(".wpcf7-turnstile, .cf-turnstile")
                        .each(function () {
                            var el = this;
                            // Skip if already has an iframe (already rendered)
                            if ($(el).find("iframe").length) return;
                            
                            var siteKey = $(el).data("sitekey");
                            
                            // Render the Turnstile widget
                            turnstile.render(el, {
                                sitekey: siteKey,
                                theme: "auto",
                            });
                        });
                }
            }
        }, 200); // slight delay to ensure form HTML is ready
    }

    /**
     * Fire The Popup
     */
    $(document).on("click", ".wpb-get-a-quote-button-form-fire", function (e) {
        e.preventDefault();

        var button = $(this),
            id = button.attr("data-id"),
            post_id = button.attr("data-post_id"),
            form_style = button.attr("data-form_style") ? !0 : !1,
            allow_outside_click = button.attr("data-allow_outside_click")
                ? !0
                : !1,
            width = button.attr("data-width");

        wp.ajax.send({
            ajax_option: "fire_wpb_gqb_contact_form",
            data: {
                action: "fire_contact_form",
                contact_form_id: id,
                wpb_post_id: post_id,
            },
            beforeSend: function (xhr) {
                button.addClass("wpb-gqf-btn-loading");
            },
            success: function (res) {
                button.removeClass("wpb-gqf-btn-loading");
                Swal.fire({
                    html: res,
                    showConfirmButton: false,
                    customClass: {
                        container:
                            "wpb-gqf-popup wpb-gqf-form-style-" + form_style,
                    },
                    padding: "30px",
                    width: width,
                    showCloseButton: true,
                    backdrop: true,
                    allowOutsideClick: allow_outside_click,
                    didOpen: function (popupEl) {
                        gqbTurnstileSupport(popupEl);
                    }
                });

                // reInit WPForms
                setTimeout(function () {
                    reinitWPForms();
                }, 50);

                // For CF7 5.3.1 and before
                if (
                    typeof wpcf7 !== "undefined" &&
                    typeof wpcf7.initForm === "function"
                ) {
                    wpcf7.initForm($(".wpcf7-form"));
                }

                // For CF7 5.4 and after
                if (
                    typeof wpcf7 !== "undefined" &&
                    typeof wpcf7.init === "function"
                ) {
                    document
                        .querySelectorAll(".wpcf7 > form")
                        .forEach(function (e) {
                            return wpcf7.init(e);
                        });
                }

                // Reinitialize reCAPTCHA v3
                if (
                    typeof grecaptcha !== "undefined" &&
                    typeof wpcf7_recaptcha !== "undefined"
                ) {
                    grecaptcha.ready(function () {
                        grecaptcha
                            .execute(wpcf7_recaptcha.sitekey, {
                                action: wpcf7_recaptcha.actions.contactform,
                            })
                            .then(function (token) {
                                const event = new CustomEvent(
                                    "wpcf7grecaptchaexecuted",
                                    {
                                        detail: {
                                            action: wpcf7_recaptcha.actions
                                                .contactform,
                                            token: token,
                                        },
                                    }
                                );
                                document.dispatchEvent(event);

                                // Update the hidden input in the dynamically loaded form
                                $(".wpcf7-form")
                                    .find(
                                        'input[name="_wpcf7_recaptcha_response"]'
                                    )
                                    .val(token);
                            });
                    });
                }

                // Add support for - Simple Cloudflare Turnstile – CAPTCHA Alternative
                if (typeof turnstile !== "undefined") {
                    var cf_turnstile_id = $($.parseHTML(res))
                        .find(".cf-turnstile")
                        .attr("id");
                    if (document.getElementById(cf_turnstile_id)) {
                        setTimeout(function () {
                            turnstile.render("#" + cf_turnstile_id);
                        }, 10);
                    }
                }

                // Add support for - Drag and Drop Multiple File Upload – Contact Form 7
                if (typeof initDragDrop === "function") {
                    window.initDragDrop();
                }

                // ReCaptcha v2 for Contact Form 7 - By IQComputing
                if (typeof recaptchaCallback === "function") {
                    recaptchaCallback();
                }

                // Add support for - Conditional Fields for Contact Form 7
                if (typeof wpcf7cf !== "undefined") {
                    wpcf7cf.initForm($(".wpcf7-form"));
                }

                // Add post ID to the popup form
                $("[name='_wpcf7_container_post']").val(post_id);

                // WP Armour – Honeypot Anti Spam By Dnesscarkey.
                if (typeof wpa_add_honeypot_field == "function") {
                    wpa_add_honeypot_field();
                }

                // WP Armour PRO – Honeypot Anti Spam By Dnesscarkey.
                if (typeof wpae_add_honeypot_field == "function") {
                    // IF EXTENDED version exists.
                    wpae_add_honeypot_field();
                }

                // Adding any custom JS code on form init
                if (typeof wpb_gqf_on_cf7_form_init === "function") {
                    wpb_gqf_on_cf7_form_init();
                }
            },
            error: function (error) {
                alert(error);
            },
        });
    });

    /**
     * Hide if variation has no stock
     */

    $(document).on(
        "found_variation",
        "form.variations_form",
        function (event, variation) {
            if (!variation.is_in_stock) {
                $(".wpb-gqb-product-type-variable").addClass(
                    "wpb-gqb-product-type-variable-show"
                );
            } else {
                $(".wpb-gqb-product-type-variable").removeClass(
                    "wpb-gqb-product-type-variable-show"
                );
            }
        }
    );

    $(document).on("click", ".reset_variations", function (event) {
        $(".wpb-gqb-product-type-variable").removeClass(
            "wpb-gqb-product-type-variable-show"
        );
    });
})(jQuery);
