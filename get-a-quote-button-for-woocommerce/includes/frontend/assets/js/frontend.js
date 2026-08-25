+(function ($) {
    "use strict";

    /**
     * Tracks whether the currently-open popup was fired from the multi-product
     * Quote List page's "Send Quote" button, so we know whether to clear the
     * quote session once the form is actually submitted.
     */
    var wpbGqbQuoteListPopupActive = false;

    /**
     * Multi-Product Quote System: refresh every "quote count" badge on the page.
     *
     * `.wpb-gqb-quote-count` is documented as something users attach to arbitrary
     * elements (e.g. a nav menu item's <li>, via the WordPress menu editor's CSS
     * Classes field), so we can't just overwrite the element's text/HTML - that
     * would wipe out the menu link. Instead we inject/update a dedicated child
     * badge element and leave the rest of the element's content untouched.
     *
     * When the class is on a menu <li>, the badge is appended inside that item's
     * <a> (rather than as a sibling of it) so it stays inline with the link text -
     * most themes render menu <a> tags as flex/block elements, which would
     * otherwise push a sibling badge onto its own line.
     */
    function wpb_gqb_update_quote_count(count) {
        if (
            typeof WPB_GQB_Vars === "undefined" ||
            WPB_GQB_Vars.quote_show_count_badge === "off"
        ) {
            return;
        }

        count = parseInt(count, 10) || 0;

        $(".wpb-gqb-quote-count").each(function () {
            var $el = $(this),
                $target = $el.is("a") ? $el : $el.children("a").first(),
                $badge;

            if (!$target.length) {
                $target = $el;
            }

            $badge = $target.children(".wpb-gqb-quote-count-badge");

            if (!$badge.length) {
                $badge = $(
                    '<span class="wpb-gqb-quote-count-badge"></span>',
                ).appendTo($target);
            }

            $badge.text(count);
            $el.attr("data-count", count);
        });
    }

    /**
     * Multi-Product Quote System: replace every instance of the
     * `[wpb-quote-widget]` hover dropdown on the page with freshly rendered
     * markup from the server (mirrors the `.wpb-gqb-quote-list` refresh
     * pattern used by wpb_gqb_maybe_clear_quote() below). Delegated event
     * handlers mean nothing needs re-binding after the swap.
     */
    function wpb_gqb_refresh_quote_widget(html) {
        if (!html) {
            return;
        }

        // Keep the panel open across the swap if the user is actively
        // interacting with it (e.g. removing items one after another) -
        // otherwise a full markup replace would snap it shut mid-interaction.
        var wasOpen = $(".wpb-gqb-quote-widget").hasClass(
            "wpb-gqb-quote-widget-open",
        );

        $(".wpb-gqb-quote-widget").replaceWith(html);

        if (wasOpen) {
            $(".wpb-gqb-quote-widget")
                .addClass("wpb-gqb-quote-widget-open")
                .each(function () {
                    wpb_gqb_position_quote_widget_panel($(this));
                });
        }

        wpb_gqb_refresh_quote_count_hover_panels(html);
    }

    /**
     * Multi-Product Quote System: "Show Quote List on Badge Hover" - pulls
     * the `.wpb-gqb-quote-widget-panel` node out of a freshly rendered
     * `[wpb-quote-widget]` markup string, detached from its toggle
     * button/icon (those belong to the real widget only).
     *
     * @param {string} html
     * @return {jQuery|null}
     */
    function wpb_gqb_extract_quote_widget_panel(html) {
        if (!html) {
            return null;
        }

        var $panel = $("<div>")
            .html(html)
            .find(".wpb-gqb-quote-widget-panel")
            .first();

        return $panel.length ? $panel : null;
    }

    /**
     * Multi-Product Quote System: "Show Quote List on Badge Hover" - lazily
     * append a clone of the quote widget's dropdown panel into a generic
     * `.wpb-gqb-quote-count` element (e.g. a nav menu item) the first time
     * it's hovered. `.wpb-gqb-quote-count` already has `position: relative`
     * (see the CSS), so the panel's existing `position: absolute; top:
     * 100%; right: 0;` rules position it correctly without any extra markup.
     *
     * @param {jQuery} $el
     */
    function wpb_gqb_ensure_quote_count_hover_panel($el) {
        if ($el.find(".wpb-gqb-quote-count-hover-panel").length) {
            return;
        }

        var $panel = wpb_gqb_extract_quote_widget_panel(
            WPB_GQB_Vars.quote_count_hover_panel_html,
        );

        if (!$panel) {
            return;
        }

        $el.addClass("wpb-gqb-quote-count-hover-enabled").append(
            $panel.addClass("wpb-gqb-quote-count-hover-panel"),
        );
    }

    /**
     * Multi-Product Quote System: "Show Quote List on Badge Hover" - keep
     * every already-injected hover panel (see above) in sync whenever the
     * quote list changes elsewhere on the page (add/remove/clear), the same
     * way wpb_gqb_refresh_quote_widget() keeps the real widget in sync.
     *
     * @param {string} html Freshly rendered `[wpb-quote-widget]` markup.
     */
    function wpb_gqb_refresh_quote_count_hover_panels(html) {
        var $fresh = wpb_gqb_extract_quote_widget_panel(html);

        if (!$fresh) {
            return;
        }

        $(".wpb-gqb-quote-count-hover-panel").each(function () {
            $(this).replaceWith(
                $fresh.clone().addClass("wpb-gqb-quote-count-hover-panel"),
            );
        });
    }

    /**
     * Multi-Product Quote System: keep the widget's dropdown panel inside
     * the viewport. It's right-aligned to the icon by default (CSS
     * `right: 0`), but depending on where the shortcode/widget is placed
     * (a narrow sidebar/column, near the left edge, etc.) that can push the
     * panel off-screen to the left. Flips it to left-aligned instead
     * whenever that would happen.
     *
     * @param {jQuery} $widget A single `.wpb-gqb-quote-widget` element.
     */
    function wpb_gqb_position_quote_widget_panel($widget) {
        var $panel = $widget.find(".wpb-gqb-quote-widget-panel").first();

        if (!$panel.length) {
            return;
        }

        $panel.removeClass("wpb-gqb-quote-widget-panel-align-left");

        if ($panel[0].getBoundingClientRect().left < 0) {
            $panel.addClass("wpb-gqb-quote-widget-panel-align-left");
        }
    }

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

        // Reinitialize WPForms' "modern markup" enhancements (page-break
        // button enabling, GB-block accent-color theming for icon/image
        // choices, rating colors, timepicker dropdown theming). This is a
        // separate module from window.wpforms above and isn't covered by it -
        // without this, a freshly AJAX-injected multi-step form keeps its
        // page-break buttons in the server-rendered "disabled" state and
        // never picks up the color/theme CSS vars applied via JS.
        if (
            window.WPForms &&
            window.WPForms.FrontendModern &&
            typeof window.WPForms.FrontendModern.init === "function"
        ) {
            window.WPForms.FrontendModern.init();
        }
    }

    /**
     * WPCF7 & WPForms Cloudflare Turnstile Support
     */
    function gqbTurnstileSupport(popupEl) {
        // Wait a short moment for the DOM to render
        setTimeout(function () {
            const formPlugin = $(popupEl).find("form.wpforms-form").length
                ? "wpforms"
                : $(popupEl).find("form.wpcf7-form").length
                ? "cf7"
                : "unknown";

            if (formPlugin === "wpforms") {
                // Init CAPTCHA for WPForms
                if ("undefined" !== typeof wpformsRecaptchaLoad) {
                    wpformsRecaptchaLoad();
                }
            } else {
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

        wpb_gqb_get_wapf($(this));
        wpb_gqb_get_wcpa($(this));

        var button = $(this),
            id = button.attr("data-id"),
            post_id = button.attr("data-post_id"),
            form_style = button.attr("data-form_style") ? !0 : !1,
            cart_page = button.attr("data-cart-page") ? !0 : !1,
            allow_outside_click = button.attr("data-allow_outside_click")
                ? !0
                : !1,
            allow_esc_key = button.attr("data-allow_esc_key") ? !0 : !1,
            width = button.attr("data-width"),
            variations = button.attr("data-variations"),
            variation_sku = button.attr("data-variation-sku"),
            variation_price = button.attr("data-variation-price"),
            qty = button.attr("data-qty"),
            quote_list = button.attr("data-quote-list") ? !0 : !1;

        wpbGqbQuoteListPopupActive = quote_list;

        // Check if wapf_config exists before accessing attributes
        let wapf = "";
        if (typeof wapf_config !== "undefined") {
            wapf = button.attr("data-wapf-fields") || ""; // Ensure it's not undefined
        }

        // Check if wcpa_front exists before accessing attributes
        let wcpa = "";
        if (typeof wcpa_front !== "undefined") {
            wcpa = button.attr("data-wcpa-fields") || ""; // Ensure it's not undefined
        }

        var checkoutWrapper = button.parent().closest(".wpb-gqb-checkout");
        if (checkoutWrapper.length) {
            var paymentmethods = checkoutWrapper.attr("data-paymentmethods"),
                paymentmethodslable = checkoutWrapper.attr(
                    "data-paymentmethodslable",
                ),
                shippingcost = checkoutWrapper.attr("data-shippingcost");
        }

        wp.ajax.send({
            ajax_option: "fire_wpb_gqb_contact_form",
            data: {
                action: "fire_contact_form",
                contact_form_id: id,
                wpb_post_id: post_id,
                wpb_cart_page: cart_page,
                wpb_quote_list: quote_list,
                wpb_gqb_variations: variations,
                wpb_gqb_variation_sku: variation_sku,
                wpb_gqb_variation_price: variation_price,
                wpb_gqb_qty: qty,
                wpb_gqb_wapf: wapf ? wapf : "",
                wpb_gqb_wcpa: wcpa ? wcpa : "",
                wpb_gqb_paymentmethods: paymentmethods ? paymentmethods : "",
                wpb_gqb_paymentmethodslable: paymentmethodslable
                    ? paymentmethodslable
                    : "",
                wpb_gqb_shippingcost: shippingcost ? shippingcost : "",
            },
            beforeSend: function (xhr) {
                button.addClass("wpb-gqf-btn-loading");
            },
            success: function (res) {
                button.removeClass("wpb-gqf-btn-loading");

                // WPB_GQB_Shortcode_Handler::get_wpforms_add_form() renders a
                // hidden copy of this same WPForms form into wp_footer (on
                // page load) purely so WPForms detects the form and enqueues
                // its assets/conditional-logic config. That hidden copy and
                // this AJAX-rendered popup form are two separate WPForms
                // renders in two separate HTTP requests, so WPForms' own
                // per-request instance counter can't tell them apart and both
                // end up with identical field IDs. A <label for="..."> always
                // activates the first element in the DOM with that ID, which
                // is this hidden copy (it was added first) - so clicking a
                // checkbox/image-choice label in the popup silently toggles
                // the invisible footer copy instead of the visible one. It
                // has already done its job priming asset loading by the time
                // the user clicks the button, so it's safe to drop here,
                // right before the real form takes its place.
                $(".wpb-gqb-popup-content").remove();

                Swal.fire({
                    html: res,
                    showConfirmButton: false,
                    customClass: {
                        container:
                            "wpb-gqf-popup wpb-gqf-form-style-" + form_style,
                        popup: "wpb-gqf-popup-padding",
                    },
                    width: width,
                    showCloseButton: true,
                    backdrop: true,
                    allowOutsideClick: allow_outside_click,
                    allowEscapeKey: allow_esc_key,
                    didOpen: function (popupEl) {
                        gqbTurnstileSupport(popupEl);
                        reinitWPForms(popupEl);
                    },
                });

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
                                    },
                                );
                                document.dispatchEvent(event);

                                // Update the hidden input in the dynamically loaded form
                                $(".wpcf7-form")
                                    .find(
                                        'input[name="_wpcf7_recaptcha_response"]',
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
                    "wpb-gqb-product-type-variable-show",
                );
            } else {
                $(".wpb-gqb-product-type-variable").removeClass(
                    "wpb-gqb-product-type-variable-show",
                );
            }
        },
    );

    $(document).on("click", ".reset_variations", function (event) {
        $(".wpb-gqb-product-type-variable").removeClass(
            "wpb-gqb-product-type-variable-show",
        );
    });

    /**
     * Show the quote button only when a matching variation is selected
     * (buttons scoped to "Selected Variations").
     */
    $(document).on(
        "found_variation",
        "form.variations_form",
        function (event, variation) {
            $(".wpb-gqb-selected-variation").each(function () {
                let allowedIds = (
                    $(this).attr("data-selected-variations") || ""
                )
                    .split(",")
                    .filter(Boolean);

                if (allowedIds.includes(String(variation.variation_id))) {
                    $(this).addClass("wpb-gqb-selected-variation-show");
                } else {
                    $(this).removeClass("wpb-gqb-selected-variation-show");
                }
            });
        },
    );

    $(document).on(
        "reset_data hide_variation",
        "form.variations_form",
        function () {
            $(".wpb-gqb-selected-variation").removeClass(
                "wpb-gqb-selected-variation-show",
            );
        },
    );

    $(document).on("click", ".reset_variations", function (event) {
        $(".wpb-gqb-selected-variation").removeClass(
            "wpb-gqb-selected-variation-show",
        );
    });

    /**
     * Hide the add-to-cart button and/or price once a variation matched by a
     * "Selected Variations" shortcode's Hide Cart & Price option is selected.
     */
    $(document).on(
        "found_variation",
        "form.variations_form",
        function (event, variation) {
            let $hideData = $(".wpb-gqb-variation-hide-data");

            if (!$hideData.length) {
                return;
            }

            let hideCartIds = (
                $hideData.attr("data-hide-cart-variations") || ""
            )
                .split(",")
                .filter(Boolean);
            let hidePriceIds = (
                $hideData.attr("data-hide-price-variations") || ""
            )
                .split(",")
                .filter(Boolean);
            let variationId = String(variation.variation_id);

            $(this).toggleClass(
                "wpb-gqb-hide-variation-cart",
                hideCartIds.includes(variationId),
            );
            $(this).toggleClass(
                "wpb-gqb-hide-variation-price",
                hidePriceIds.includes(variationId),
            );
        },
    );

    /**
     * WooCommerce core sets an inline `display: block` on `.quantity` when a
     * variation is found, which overrides theme CSS (e.g. flex layouts).
     * Visibility here is already handled by our CSS classes above, so strip
     * the inline style and let the theme's own CSS take over.
     */
    $(document).on(
        "found_variation",
        "form.variations_form",
        function () {
            $(this)
                .find(".quantity")
                .each(function () {
                    if ($(this).css("display") !== "none") {
                        $(this).css("display", "");
                    }
                });
        },
    );

    $(document).on(
        "reset_data hide_variation",
        "form.variations_form",
        function () {
            $(this).removeClass(
                "wpb-gqb-hide-variation-cart wpb-gqb-hide-variation-price",
            );
        },
    );

    $(document).on("click", ".reset_variations", function (event) {
        $("form.variations_form").removeClass(
            "wpb-gqb-hide-variation-cart wpb-gqb-hide-variation-price",
        );
    });

    /**
     * Add variable product sku, proce and stock
     */
    $(document).on(
        "found_variation",
        "form.variations_form",
        function (event, variation) {
            //console.log(variation.attributes);
            $(".wpb-gqb-product-type-variable").attr(
                "data-variation-sku",
                JSON.stringify(variation.sku),
            );
            $(".wpb-gqb-product-type-variable").attr(
                "data-variation-price",
                JSON.stringify(variation.display_price),
            );
            $(".wpb-gqb-product-type-variable").attr(
                "data-variation-id",
                variation.variation_id,
            );

            $("body").addClass(
                "wpb-gqb-product-variation-stock-" + variation.is_in_stock,
            );
        },
    );

    /**
     * Add product variations selection data to the quote button
     */
    $(document).ready(function () {
        let form = $("form.variations_form");

        // Function to update the data-variations attribute.
        function updateVariationsData(attributes) {
            $(".wpb-gqb-product-type-variable").attr(
                "data-variations",
                JSON.stringify(attributes).replace(
                    /attribute_pa_|attribute_|pa_/g,
                    "",
                ),
            );
        }

        // If "found_variation" is selected, only update when a valid variation is found.
        if (WPB_GQB_Vars.variations_collection === "found_variation") {
            form.on("found_variation", function (event, variation) {
                updateVariationsData(variation.attributes);
            });
        }

        // If "select_variation" is selected, update on attribute selection as well.
        if (WPB_GQB_Vars.variations_collection === "select_variation") {
            form.on("change", ".variations select", function () {
                let selectedAttributes = {};

                form.find(".variations select").each(function () {
                    let attributeName = $(this).attr("name");
                    let attributeValue = $(this).val();

                    if (attributeValue) {
                        selectedAttributes[attributeName] = attributeValue;
                    }
                });

                updateVariationsData(selectedAttributes);
            });
        }
    });

    $(document).on("click", ".reset_variations", function (event) {
        $(".wpb-gqb-product-type-variable").removeAttr("data-variations");
        $(".wpb-gqb-product-type-variable").removeAttr("data-variation-sku");
        $(".wpb-gqb-product-type-variable").removeAttr("data-variation-id");
        $(".wpb-gqb-product-type-variable").removeAttr("data-variation-price");

        $("body")
            .removeClass("wpb-gqb-product-variation-stock-true")
            .removeClass("wpb-gqb-product-variation-stock-false");
    });

    /**
     * Add product quantity to the Quote button
     */
    // before changing anything return default qty
    $(document).ready(function () {
        $(".wpb-get-a-quote-button-form-fire").attr(
            "data-qty",
            $(".cart .quantity .qty").val(),
        );
    });

    // After changing qty update the quote qty
    $(".cart .quantity .qty").on("input change", function () {
        $(".wpb-get-a-quote-button-form-fire").attr("data-qty", $(this).val());
    });

    /**
     * Gray out quote button for variable products
     */

    $(document).on("hide_variation", "form.variations_form", function (event) {
        event.preventDefault();
        $(
            ".wpb-gqb-product-type-variable.wpb-get-a-quote-button-variable-gray-out-on",
        ).addClass("wpb-gqb-btn-disabled");
        $(
            ".wpb-gqb-product-type-variable.wpb-get-a-quote-button-variable-gray-out-on",
        ).attr("disabled", "disabled");
    });

    $(document).on(
        "show_variation",
        "form.variations_form",
        function (event, data) {
            event.preventDefault();
            $(
                ".wpb-gqb-product-type-variable.wpb-get-a-quote-button-variable-gray-out-on",
            ).removeClass("wpb-gqb-btn-disabled");
            $(
                ".wpb-gqb-product-type-variable.wpb-get-a-quote-button-variable-gray-out-on",
            ).removeAttr("disabled");
        },
    );

    /**
     * Close The popup on form sending success ( wpcf7mailsent, wpformsAjaxSubmitSuccess )
     */

    if (WPB_GQB_Vars.form_submit_close_popup == "on") {
        document.addEventListener(
            "wpcf7mailsent",
            function (event) {
                Swal.close();
            },
            false,
        );

        $(document).on("wpformsAjaxSubmitSuccess", function () {
            Swal.close();
        });
    }

    /**
     * Redirecting to another URL after submissions ( wpcf7mailsent, wpformsAjaxSubmitSuccess )
     */
    if (WPB_GQB_Vars.cf7_submission_redirect !== "") {
        document.addEventListener(
            "wpcf7mailsent",
            function () {
                setTimeout(function () {
                    window.location.href = WPB_GQB_Vars.cf7_submission_redirect;
                }, 1000);
            },
            false,
        );

        $(document).on("wpformsAjaxSubmitSuccess", function () {
            setTimeout(function () {
                window.location.href = WPB_GQB_Vars.cf7_submission_redirect;
            }, 1000);
        });
    }

    /**
     * Multi-Product Quote System: clear the quote session once a quote-list
     * popup has actually been submitted successfully (not just opened).
     */
    function wpb_gqb_maybe_clear_quote() {
        var wasQuoteList = wpbGqbQuoteListPopupActive;
        wpbGqbQuoteListPopupActive = false;

        if (
            !wasQuoteList ||
            typeof WPB_GQB_Vars === "undefined" ||
            WPB_GQB_Vars.quote_clear_after_submit !== "on"
        ) {
            return;
        }

        wp.ajax.send({
            ajax_option: "wpb_gqb_clear_quote",
            data: {
                action: "wpb_gqb_clear_quote",
                nonce: WPB_GQB_Vars.quote_nonce,
            },
            success: function (data) {
                wpb_gqb_update_quote_count(0);

                data = data || {};

                var $quoteList = $(".wpb-gqb-quote-list");

                if (data.html && $quoteList.length) {
                    $quoteList.replaceWith(data.html);
                }

                wpb_gqb_refresh_quote_widget(data.widget_html);
            },
        });
    }

    document.addEventListener(
        "wpcf7mailsent",
        wpb_gqb_maybe_clear_quote,
        false,
    );
    $(document).on("wpformsAjaxSubmitSuccess", wpb_gqb_maybe_clear_quote);

    /**
     * Add support for Contact Form 7 – Phone mask field
     *
     * https://wordpress.org/plugins/cf7-phone-mask-field/
     */

    $(document).ajaxComplete(function () {
        var $fields = $(".wpcf7-mask");

        if (!$fields.length) {
            return false;
        }

        $fields.each(function () {
            var $this = $(this),
                mask = $this.data("mask");

            if (!mask) {
                return;
            }

            $this.mask(mask, {
                autoclear: WPB_GQB_Get_Option_Mask($this.data("autoclear")),
            });

            if ("tel" != $this.attr("type") && -1 == mask.indexOf(".")) {
                $this.attr({
                    inputmode: "numeric",
                });
            }
        });
    });

    function WPB_GQB_Get_Option_Mask(valule) {
        if (typeof valule == "undefined") {
            return 0;
        }

        return valule;
    }

    /**
     * Add support for Advanced Product Fields for WooCommerce by studiowombat
     *
     * https://www.studiowombat.com/plugin/advanced-product-fields-for-woocommerce/
     */
    function wpb_gqb_get_wapf(quoteButton) {
        let customFields = {};

        // Loop through each custom field container
        $(".wapf-field-container").each(function () {
            let label = $(this)
                .find(".wapf-field-label label span")
                .text()
                .trim();
            let fieldValues = [];

            // Get all inputs/selects inside the field container
            $(this)
                .find(
                    '.wapf-field-input input:not([type="hidden"]), .wapf-field-input select, .wapf-field-input textarea, .wapf-field-input .wapf-calc-text',
                )
                .each(function () {
                    let inputType = $(this).attr("type");
                    let value = "";

                    // Handle True/False fields separately
                    if ($(this).closest(".wapf-field-true-false").length > 0) {
                        let trueLabel =
                            $(this).attr("data-true-label") || "True";
                        let falseLabel =
                            $(this).attr("data-false-label") || "False";
                        value = $(this).is(":checked") ? trueLabel : falseLabel;
                    } else if (
                        inputType === "checkbox" ||
                        inputType === "radio"
                    ) {
                        if ($(this).is(":checked")) {
                            value = $(this).attr("data-wapf-label"); // Try to get data-wapf-label

                            if (!value) {
                                value = $(this)
                                    .closest("label")
                                    .find(".wapf-label-text")
                                    .text()
                                    .trim(); // Fallback to .wapf-label-text
                            }
                        }
                    } else if ($(this).is("select")) {
                        value = $(this).find(":selected").text();
                    } else if ($(this).hasClass("wapf-calc-text")) {
                        value = $(this).text();
                    } else {
                        value = $(this).val();
                    }

                    if (
                        value &&
                        value !== "0" &&
                        value !== "Choose an option"
                    ) {
                        fieldValues.push(value);
                    }
                });

            // If values exist, store them in an object
            if (label && fieldValues.length > 0) {
                customFields[label] = fieldValues.join(", ");
            }
        });

        // Store data in the quote button as a JSON string
        quoteButton.attr("data-wapf-fields", JSON.stringify(customFields));
    }

    /**
     * Product Addons for Woocommerce – Product Options with Custom Fields by acowebs
     *
     * https://wordpress.org/plugins/woo-custom-product-addons/
     */
    function wpb_gqb_get_wcpa(quoteButton) {
        let customFields = {};

        // Loop through each custom field container
        $(".wcpa_row").each(function () {
            let label = $(this).find(".wcpa_field_label").text().trim();

            // Fallback for single checkbox (no wcpa_field_label)
            if (!label) {
                label = $(this).find(".wcpa_checkbox label").text().trim();
            }
            let fieldValues = [];

            // Get all inputs/selects inside the field container
            $(this)
                .find(
                    ".wcpa_field_wrap input, .wcpa_field_wrap select, .wcpa_field_wrap textarea",
                )
                .each(function () {
                    let inputType = $(this).attr("type");
                    let value = "";

                    if (inputType === "checkbox" || inputType === "radio") {
                        if (!$(this).is(":checked")) return; // skip unchecked
                        value = $(this).val();
                    } else if ($(this).is("select")) {
                        value = $(this).find(":selected").text();
                    } else {
                        value = $(this).val();
                    }

                    if (
                        value &&
                        value !== "0" &&
                        value !== "Choose an option"
                    ) {
                        fieldValues.push(value);
                    }
                });

            // If values exist, store them in an object
            if (label && fieldValues.length > 0) {
                customFields[label] = fieldValues.join(", ");
            }
        });

        // Store data in the quote button as a JSON string
        quoteButton.attr("data-wcpa-fields", JSON.stringify(customFields));
    }

    /**
     * Flat, single-path check/cross glyphs drawn by hand instead of relying
     * on SweetAlert2's built-in icon (which is sized/positioned in em units
     * tied to its own grid layout and fights a compact, single-line toast).
     */
    var WPB_GQB_TOAST_ICON_PATHS = {
        success: "M4 10.5l3.7 3.7L16 6",
        error: "M5.5 5.5l9 9M14.5 5.5l-9 9",
    };

    /**
     * Build the (safely DOM-constructed, no raw HTML string injection) body of
     * the "Added to Quote" toast: a flat status icon, a title, and - if
     * available - an inline link to the Quote List page.
     */
    function wpb_gqb_build_toast_body(icon, title, quoteUrl, linkLabel) {
        var wrap = document.createElement("div");
        wrap.className = "wpb-gqb-quote-toast-body";

        var iconWrap = document.createElement("span");
        iconWrap.className = "wpb-gqb-quote-toast-icon";
        iconWrap.setAttribute("aria-hidden", "true");

        var svgNS = "http://www.w3.org/2000/svg";
        var svg = document.createElementNS(svgNS, "svg");
        svg.setAttribute("viewBox", "0 0 20 20");
        svg.setAttribute("width", "14");
        svg.setAttribute("height", "14");
        svg.setAttribute("fill", "none");

        var path = document.createElementNS(svgNS, "path");
        path.setAttribute(
            "d",
            WPB_GQB_TOAST_ICON_PATHS[icon] || WPB_GQB_TOAST_ICON_PATHS.success,
        );
        path.setAttribute("stroke", "currentColor");
        path.setAttribute("stroke-width", "2.3");
        path.setAttribute("stroke-linecap", "round");
        path.setAttribute("stroke-linejoin", "round");

        svg.appendChild(path);
        iconWrap.appendChild(svg);
        wrap.appendChild(iconWrap);

        var textWrap = document.createElement("div");
        textWrap.className = "wpb-gqb-quote-toast-text";

        var titleEl = document.createElement("p");
        titleEl.className = "wpb-gqb-quote-toast-title";
        titleEl.textContent = title;
        textWrap.appendChild(titleEl);

        if (quoteUrl) {
            var link = document.createElement("a");
            link.className = "wpb-gqb-quote-toast-link";
            link.href = quoteUrl;
            link.textContent = linkLabel;
            textWrap.appendChild(link);
        }

        wrap.appendChild(textWrap);

        return wrap;
    }

    /**
     * Build the body of the slim "bottom text" alert variant: just the
     * message and - if available - an inline link, no icon/card chrome.
     */
    function wpb_gqb_build_toast_text_body(title, quoteUrl, linkLabel) {
        var wrap = document.createElement("span");
        wrap.className = "wpb-gqb-quote-toast-text-body";

        var titleEl = document.createElement("span");
        titleEl.className = "wpb-gqb-quote-toast-text-title";
        titleEl.textContent = title;
        wrap.appendChild(titleEl);

        if (quoteUrl) {
            var link = document.createElement("a");
            link.className = "wpb-gqb-quote-toast-text-link";
            link.href = quoteUrl;
            link.textContent = linkLabel;
            wrap.appendChild(link);
        }

        return wrap;
    }

    /**
     * "Replace Button" alert variant: on success, permanently swaps the
     * clicked button itself for a short "Product added" message with a link
     * to the quote list (the button stays hidden - the product is already
     * in the list, so there is nothing left to retry). On error the button
     * is left in place and clickable, with a temporary inline message next
     * to it so the customer can try again.
     */
    function wpb_gqb_replace_button_with_alert(icon, title, quoteUrl, linkLabel, button) {
        button.next(".wpb-gqb-quote-replace-alert").remove();

        var wrap = document.createElement("div");
        wrap.className = "wpb-gqb-quote-replace-alert wpb-gqb-quote-replace-alert-" + icon;

        var textEl = document.createElement("p");
        textEl.className = "wpb-gqb-quote-replace-text";
        textEl.textContent = title;
        wrap.appendChild(textEl);

        if (icon === "success" && quoteUrl) {
            var link = document.createElement("a");
            link.className = "wpb-gqb-quote-replace-link";
            link.href = quoteUrl;
            link.textContent = linkLabel;
            wrap.appendChild(link);
        }

        var $wrap = $(wrap);

        if (icon === "success") {
            button.hide();
            button.after($wrap);
        } else {
            button.after($wrap);
            setTimeout(function () {
                $wrap.remove();
            }, 3500);
        }
    }

    /**
     * Show a small, dismissible toast for quote actions (success or error).
     *
     * The "Added to Quote Alert Style" setting (WPB_GQB_Vars.quote_added_alert_type)
     * picks between the default top-right card toast, a slimmer text-only
     * alert anchored to the bottom of the screen, and an inline alert that
     * replaces the button itself.
     */
    function wpb_gqb_show_quote_toast(icon, title, quoteUrl, button) {
        var linkLabel =
            (WPB_GQB_Vars.i18n && WPB_GQB_Vars.i18n.viewQuote) ||
            "View Quote List";

        var alertType =
            (typeof WPB_GQB_Vars !== "undefined" &&
                WPB_GQB_Vars.quote_added_alert_type) ||
            "top_right";

        if (alertType === "replace_button" && button && button.length) {
            wpb_gqb_replace_button_with_alert(
                icon,
                title,
                quoteUrl,
                linkLabel,
                button,
            );
            return;
        }

        if (alertType === "bottom_text") {
            Swal.fire({
                toast: true,
                position: "bottom",
                showConfirmButton: false,
                showCloseButton: false,
                timer: icon === "success" ? 3500 : 3000,
                timerProgressBar: false,
                customClass: {
                    container: "wpb-gqb-quote-toast-text-container",
                    popup:
                        "wpb-gqb-quote-toast-text wpb-gqb-quote-toast-text-" +
                        icon,
                },
                html: wpb_gqb_build_toast_text_body(
                    title,
                    quoteUrl,
                    linkLabel,
                ),
            });
            return;
        }

        Swal.fire({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            showCloseButton: true,
            timer: icon === "success" ? 4500 : 3500,
            timerProgressBar: true,
            customClass: {
                container: "wpb-gqb-quote-toast-container",
                popup: "wpb-gqb-quote-toast wpb-gqb-quote-toast-" + icon,
            },
            html: wpb_gqb_build_toast_body(icon, title, quoteUrl, linkLabel),
        });
    }

    /**
     * Multi-Product Quote System: paint the quote count badge(s) with the
     * session's current count on every page load (badges are otherwise only
     * ever updated in response to an add/update/remove AJAX call on the same
     * page, so a fresh page load - e.g. after "Redirect to Quote List Page
     * After Adding" navigates away - would otherwise show a stale/empty badge).
     *
     * WPB_GQB_Vars.quote_count/quote_count_hover_panel_html are baked into
     * the page's PHP output at render time, so on a page served from a
     * full-page cache (WP Rocket, LiteSpeed, etc.) for a logged-out visitor
     * they can reflect a *different* guest's session - whoever's visit
     * happened to generate that cached copy. Painting with them first avoids
     * a flash of "0" while the AJAX request below is in flight, then its
     * response overwrites everything (badges, the [wpb-quote-widget]
     * instances, and the hover panel markup used for lazy-initialized
     * panels) with this visitor's real data.
     */
    $(document).ready(function () {
        if (typeof WPB_GQB_Vars === "undefined") {
            return;
        }

        wpb_gqb_update_quote_count(WPB_GQB_Vars.quote_count || 0);

        if (WPB_GQB_Vars.quote_system_type !== "multi") {
            return;
        }

        wp.ajax.send({
            ajax_option: "wpb_gqb_refresh_quote_widget",
            data: {
                action: "wpb_gqb_refresh_quote_widget",
            },
            success: function (data) {
                data = data || {};

                wpb_gqb_update_quote_count(data.count || 0);
                wpb_gqb_refresh_quote_widget(data.widget_html);

                WPB_GQB_Vars.quote_count_hover_panel_html =
                    data.quote_count_hover_panel_html || "";
            },
        });
    });

    /**
     * Multi-Product Quote System: "Add to Quote"
     *
     * Reuses the same variation/qty/WAPF/WCPA data attributes already captured
     * above for the single-quote flow, but adds the product to the session-backed
     * quote list via AJAX instead of opening the popup immediately.
     */
    $(document).on("click", ".wpb-gqb-add-to-quote-btn", function (e) {
        e.preventDefault();

        wpb_gqb_get_wapf($(this));
        wpb_gqb_get_wcpa($(this));

        var button = $(this),
            post_id = button.attr("data-post_id"),
            variations = button.attr("data-variations"),
            variation_id = button.attr("data-variation-id"),
            variation_price = button.attr("data-variation-price"),
            qty = button.attr("data-qty") || 1,
            wapf = button.attr("data-wapf-fields") || "",
            wcpa = button.attr("data-wcpa-fields") || "";

        wp.ajax.send({
            ajax_option: "wpb_gqb_add_to_quote",
            data: {
                action: "wpb_gqb_add_to_quote",
                nonce: WPB_GQB_Vars.quote_nonce,
                product_id: post_id,
                variation_id: variation_id || 0,
                variation: variations || "",
                qty: qty,
                price: variation_price || "",
                wapf: wapf,
                wcpa: wcpa,
            },
            beforeSend: function () {
                button.addClass("wpb-gqf-btn-loading");
            },
            success: function (data) {
                button.removeClass("wpb-gqf-btn-loading");

                data = data || {};

                $(document.body).trigger("wpb_gqb_added_to_quote", [
                    data,
                    button,
                ]);

                wpb_gqb_update_quote_count(data.count);
                wpb_gqb_refresh_quote_widget(data.widget_html);

                if (
                    WPB_GQB_Vars.quote_redirect_after_add === "on" &&
                    data.quote_url
                ) {
                    window.location.href = data.quote_url;
                    return;
                }

                wpb_gqb_show_quote_toast(
                    "success",
                    (WPB_GQB_Vars.i18n && WPB_GQB_Vars.i18n.addedToQuote) ||
                        data.message,
                    data.quote_url,
                    button,
                );
            },
            error: function () {
                button.removeClass("wpb-gqf-btn-loading");

                wpb_gqb_show_quote_toast(
                    "error",
                    (WPB_GQB_Vars.i18n && WPB_GQB_Vars.i18n.quoteAddError) ||
                        "Unable to add this product to your quote list.",
                    "",
                    button,
                );
            },
        });
    });

    /**
     * Multi-Product Quote System: Quote List page interactions (qty update / remove)
     */
    function wpb_gqb_apply_quote_totals(data) {
        wpb_gqb_update_quote_count(data.count);
        wpb_gqb_refresh_quote_widget(data.widget_html);
        $(".wpb-gqb-quote-list-total-qty").text(data.count);
        $(".wpb-gqb-quote-list-total").html(data.total);

        if (data.is_empty) {
            window.location.reload();
            return;
        }

        // Multi-Product Quote System: in "Inline" display mode the form is
        // always visible (no button to re-click), so the baked-in quote-data
        // hidden field needs to stay in sync whenever the list itself
        // changes. This only refreshes that one field's value - it must NOT
        // re-fetch/replace the whole form (see
        // wpb_gqb_refresh_inline_quote_data_field()'s comment for why).
        if (
            typeof WPB_GQB_Vars !== "undefined" &&
            WPB_GQB_Vars.quote_list_display_mode === "inline"
        ) {
            wpb_gqb_refresh_inline_quote_data_field();
        }
    }

    /**
     * Multi-Product Quote System: "Inline" display mode - keep the
     * `_wpb_gqb_auto_product_data` hidden field (baked into the form once,
     * at initial render, by the "Automatically Send Quote Data" feature)
     * in sync with qty/remove changes to the quote list.
     *
     * This intentionally does NOT re-fetch/replace the whole form the way
     * wpb_gqb_fetch_inline_quote_form() does. Doing that on every list
     * change used to (a) tear down and rebuild a live, already-initialized
     * WPForms/CF7 form in place - which raced with WPForms/Choices.js/date
     * picker field scripts still touching the previous render and threw
     * "Cannot read properties of null (reading 'removeChild')" - and (b)
     * hand the user a freshly re-rendered form (new nonce, new anti-spam
     * timestamps) on every qty change instead of the one they started
     * filling in, which is what caused WPForms to reject the eventual
     * submission with a 400 from admin-ajax.php.
     */
    function wpb_gqb_refresh_inline_quote_data_field() {
        var field = $(
            '.wpb-gqb-quote-list-inline-form input[name="_wpb_gqb_auto_product_data"]',
        );

        if (!field.length) {
            return;
        }

        wp.ajax.send({
            ajax_option: "wpb_gqb_refresh_quote_data_field",
            data: {
                action: "wpb_gqb_refresh_quote_data_field",
            },
            success: function (data) {
                field.val((data && data.value) || "");
            },
        });
    }

    /**
     * Multi-Product Quote System: "Inline" display mode.
     *
     * Instead of a "Send Quote" button that opens the form in a SweetAlert2
     * popup, this fires the exact same `fire_contact_form` AJAX request (the
     * one that carries `wpb_quote_list` in $_POST, which is what makes the
     * CF7/WPForms product-data tags, "Automatically Send Quote Data", and the
     * field visibility/editable settings work) and injects the response into
     * a placeholder container on the page instead of a modal.
     */
    function wpb_gqb_reinit_inline_form_scripts(container, res) {
        gqbTurnstileSupport(container);
        reinitWPForms(container);

        // For CF7 5.3.1 and before
        if (typeof wpcf7 !== "undefined" && typeof wpcf7.initForm === "function") {
            wpcf7.initForm(container.find(".wpcf7-form"));
        }

        // For CF7 5.4 and after
        if (typeof wpcf7 !== "undefined" && typeof wpcf7.init === "function") {
            container[0]
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
                            },
                        );
                        document.dispatchEvent(event);

                        container
                            .find(".wpcf7-form")
                            .find('input[name="_wpcf7_recaptcha_response"]')
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
            wpcf7cf.initForm(container.find(".wpcf7-form"));
        }

        // WP Armour – Honeypot Anti Spam By Dnesscarkey.
        if (typeof wpa_add_honeypot_field == "function") {
            wpa_add_honeypot_field();
        }

        // WP Armour PRO – Honeypot Anti Spam By Dnesscarkey.
        if (typeof wpae_add_honeypot_field == "function") {
            wpae_add_honeypot_field();
        }

        // Adding any custom JS code on form init
        if (typeof wpb_gqf_on_cf7_form_init === "function") {
            wpb_gqf_on_cf7_form_init();
        }
    }

    var wpbGqbInlineFormRequest = null;

    function wpb_gqb_fetch_inline_quote_form() {
        var container = $(".wpb-gqb-quote-list-inline-form");

        if (!container.length) {
            return;
        }

        // Guard against overlapping fetch/replace cycles (e.g. this function
        // getting called again before a previous fetch finished rendering) -
        // only ever let the latest request win.
        if (wpbGqbInlineFormRequest && wpbGqbInlineFormRequest.abort) {
            wpbGqbInlineFormRequest.abort();
        }

        var id = container.attr("data-id");

        container.addClass("wpb-gqb-quote-list-inline-form-loading");

        wpbGqbInlineFormRequest = wp.ajax.send({
            ajax_option: "fire_wpb_gqb_contact_form_inline",
            data: {
                action: "fire_contact_form",
                contact_form_id: id,
                wpb_post_id: container.attr("data-post_id"),
                wpb_cart_page: container.attr("data-cart-page") ? !0 : !1,
                wpb_quote_list: container.attr("data-quote-list") ? !0 : !1,
            },
            success: function (res) {
                wpbGqbInlineFormRequest = null;
                container.removeClass("wpb-gqb-quote-list-inline-form-loading");

                // Plain DOM assignment, not jQuery's `.html()`. The
                // WPForms/CF7 markup here always contains a <script>,
                // <style>, or <textarea> tag (e.g. WPForms' honeypot-hiding
                // snippet, which ends with `document.currentScript?.remove()`
                // to remove itself once it's run). jQuery's `.html()` treats
                // any of those tags as reason to fall through to its
                // `.empty().append()` path, which auto-executes embedded
                // <script> tags as part of its own DOM-insertion bookkeeping;
                // a script removing itself *while* jQuery's insertion loop
                // still expects it to be attached is exactly what threw
                // "Cannot read properties of null (reading 'removeChild')".
                // Setting innerHTML directly never auto-executes <script>
                // tags at all (per the HTML spec), so they land inert and
                // wpb_gqb_reinit_inline_form_scripts() below is the only
                // thing that ever runs them - via a plain, isolated eval()
                // with no DOM-insertion transaction for it to collide with.
                container[0].innerHTML = res;

                // "Clear Quote After Submit" (wpb_gqb_maybe_clear_quote) only
                // clears when this flag is true. The popup path sets it on
                // button click; the inline form has no click to hook, so it
                // must be set here instead, every time the form is (re)rendered.
                wpbGqbQuoteListPopupActive = container.attr("data-quote-list")
                    ? true
                    : false;

                wpb_gqb_reinit_inline_form_scripts(container, res);
            },
            error: function () {
                wpbGqbInlineFormRequest = null;
                container.removeClass("wpb-gqb-quote-list-inline-form-loading");
            },
        });
    }

    $(document).ready(function () {
        if (
            typeof WPB_GQB_Vars !== "undefined" &&
            WPB_GQB_Vars.quote_list_display_mode === "inline"
        ) {
            wpb_gqb_fetch_inline_quote_form();
        }
    });

    function wpb_gqb_update_quote_item_qty(input) {
        var key = input.attr("data-key"),
            qty = parseInt(input.val(), 10) || 1,
            row = input.closest(".wpb-gqb-quote-list-item");

        row.addClass("wpb-gqb-quote-list-item-loading");

        wp.ajax.send({
            ajax_option: "wpb_gqb_update_quote_item",
            data: {
                action: "wpb_gqb_update_quote_item",
                nonce: WPB_GQB_Vars.quote_nonce,
                key: key,
                qty: qty,
                quote_action: "update",
            },
            success: function (data) {
                row.removeClass("wpb-gqb-quote-list-item-loading");

                data = data || {};

                if (data.item_subtotal) {
                    row.find(".wpb-gqb-quote-list-col-subtotal").html(
                        data.item_subtotal,
                    );
                }

                wpb_gqb_apply_quote_totals(data);
            },
            error: function () {
                row.removeClass("wpb-gqb-quote-list-item-loading");
            },
        });
    }

    $(document).on("change", ".wpb-gqb-quote-list-item-qty", function () {
        wpb_gqb_update_quote_item_qty($(this));
    });

    $(document).on(
        "click",
        ".wpb-gqb-quote-list-qty-minus, .wpb-gqb-quote-list-qty-plus",
        function (e) {
            e.preventDefault();

            var button = $(this),
                stepper = button.closest(".wpb-gqb-quote-list-qty-stepper"),
                input = stepper.find(".wpb-gqb-quote-list-item-qty"),
                min = parseInt(input.attr("min"), 10) || 1,
                oldQty = parseInt(input.val(), 10) || min,
                newQty = button.hasClass("wpb-gqb-quote-list-qty-plus")
                    ? oldQty + 1
                    : Math.max(min, oldQty - 1);

            if (newQty === oldQty) {
                return;
            }

            input.val(newQty);

            wpb_gqb_update_quote_item_qty(input);
        },
    );

    $(document).on("click", ".wpb-gqb-quote-list-item-remove", function (e) {
        e.preventDefault();

        var link = $(this),
            key = link.attr("data-key"),
            row = link.closest(".wpb-gqb-quote-list-item");

        row.addClass("wpb-gqb-quote-list-item-loading");

        wp.ajax.send({
            ajax_option: "wpb_gqb_update_quote_item",
            data: {
                action: "wpb_gqb_update_quote_item",
                nonce: WPB_GQB_Vars.quote_nonce,
                key: key,
                quote_action: "remove",
            },
            success: function (data) {
                data = data || {};

                row.fadeOut(200, function () {
                    row.remove();
                    wpb_gqb_apply_quote_totals(data);
                });
            },
            error: function () {
                row.removeClass("wpb-gqb-quote-list-item-loading");
            },
        });
    });

    /**
     * Multi-Product Quote System: [wpb-quote-widget] hover dropdown.
     *
     * Hover open/close for desktop, plus a click-to-toggle fallback for
     * touch devices and keyboard/a11y users, closing on an outside click.
     */
    $(document).on("mouseenter", ".wpb-gqb-quote-widget", function () {
        $(this).addClass("wpb-gqb-quote-widget-open");
        wpb_gqb_position_quote_widget_panel($(this));
    });

    $(document).on("mouseleave", ".wpb-gqb-quote-widget", function () {
        $(this).removeClass("wpb-gqb-quote-widget-open");
    });

    $(document).on("click", ".wpb-gqb-quote-widget-toggle", function (e) {
        e.preventDefault();

        var $widget = $(this).closest(".wpb-gqb-quote-widget");

        $widget.toggleClass("wpb-gqb-quote-widget-open");

        if ($widget.hasClass("wpb-gqb-quote-widget-open")) {
            wpb_gqb_position_quote_widget_panel($widget);
        }
    });

    $(document).on("click", function (e) {
        if (!$(e.target).closest(".wpb-gqb-quote-widget").length) {
            $(".wpb-gqb-quote-widget").removeClass(
                "wpb-gqb-quote-widget-open",
            );
        }
    });

    /**
     * Multi-Product Quote System: "Show Quote List on Badge Hover".
     *
     * Reveals the same dropdown as [wpb-quote-widget] on hover of any
     * generic `.wpb-gqb-quote-count` element (e.g. a nav menu item classed
     * up via the WordPress Menu editor), not just the dedicated widget.
     * Excludes `.wpb-gqb-quote-widget-toggle` - that's the real widget's own
     * toggle button, already handled by the hover/click handlers above.
     * Hover-only (no click-to-toggle): unlike the widget's own icon, the
     * host element here is usually a real link the user still needs to be
     * able to click through normally.
     */
    $(document).on("mouseenter", ".wpb-gqb-quote-count", function () {
        if (
            typeof WPB_GQB_Vars === "undefined" ||
            WPB_GQB_Vars.quote_count_hover_dropdown !== "on" ||
            $(this).hasClass("wpb-gqb-quote-widget-toggle")
        ) {
            return;
        }

        var $el = $(this);

        wpb_gqb_ensure_quote_count_hover_panel($el);
        $el.addClass("wpb-gqb-quote-count-hover-open");
        wpb_gqb_position_quote_widget_panel($el);
    });

    $(document).on("mouseleave", ".wpb-gqb-quote-count", function () {
        $(this).removeClass("wpb-gqb-quote-count-hover-open");
    });

    /**
     * Multi-Product Quote System: remove an item from inside the widget's
     * own dropdown. Deliberately does not reuse wpb_gqb_apply_quote_totals()
     * - that helper reloads the page when the list becomes empty, which is
     * meant for the dedicated Quote List page and would be a jarring
     * full-page reload if triggered from a header dropdown on any page.
     * The freshly rendered widget markup already covers the empty state.
     */
    $(document).on(
        "click",
        ".wpb-gqb-quote-widget-item-remove",
        function (e) {
            e.preventDefault();

            var link = $(this),
                key = link.attr("data-key"),
                row = link.closest(".wpb-gqb-quote-widget-item");

            row.addClass("wpb-gqb-quote-widget-item-loading");

            wp.ajax.send({
                ajax_option: "wpb_gqb_update_quote_widget_item",
                data: {
                    action: "wpb_gqb_update_quote_item",
                    nonce: WPB_GQB_Vars.quote_nonce,
                    key: key,
                    quote_action: "remove",
                },
                success: function (data) {
                    data = data || {};

                    wpb_gqb_update_quote_count(data.count);
                    wpb_gqb_refresh_quote_widget(data.widget_html);
                },
                error: function () {
                    row.removeClass("wpb-gqb-quote-widget-item-loading");
                },
            });
        },
    );
})(jQuery);
