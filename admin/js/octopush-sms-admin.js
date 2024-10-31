//(function (jQuery) {
function initTab() {
    jQuery(function () {
        // init the table in list or detail view
        //initTableSorter();
        initTable();

        // if in detail view
        if (jQuery('#sendsms_form').length > 0) {
            initButtons();

            jQuery('input[id^=sendsms]').keyup(function (i) {
                jQuery(this).val(jQuery(this).val().ucfirst());
            });

            jQuery('#add_recipient').click(function (e) {
                addFreeRecipient();
            });

            jQuery('#add_csv').click(function (e) {
                addCSV();
            });

            jQuery('#sendsms_cancel').click(function (e) {
                e.stopPropagation();
                return confirm(sendsms_confirm_cancel);
            });

            jQuery('#sendsms_delete').click(function (e) {
                e.stopPropagation();
                return confirm(sendsms_confirm_delete);
            });

            jQuery('#sendsms_duplicate').click(function (e) {
                jQuery('#action').val('SendsmsSendTab');
            });

            jQuery('#sendsms_query_add').click(function (e) {
                addRecipientsFromQuery();
            });

            jQuery('#sendsms_query_user_add').click(function (e) {
                addUsersFromRole();
            });

            jQuery('#sendsms_query_orders_from, #sendsms_query_orders_to').keyup(function (e) {
                jQuery('#sendsms_query_orders_none').attr('checked', false);
                if (jQuery(this).val() != '') {
                    if (isNaN(jQuery(this).val()))
                        jQuery(this).val('');
                    else if (jQuery(this).val() < 1) {
                        jQuery(this).val('');
                        alert(sendsms_error_orders);
                    }
                }
            });

            jQuery('#sendsms_query_orders_none').click(function (e) {
                if (jQuery(this).attr('checked')) {
                    jQuery('#sendsms_query_orders_from').val('');
                    jQuery('#sendsms_query_orders_to').val('');
                }
            });

            jQuery('#sendsms_query select, #sendsms_query input').change(function (e) {
                countRecipientsFromQuery();
            });

            jQuery('#sendsms_customer_filter').keyup(function (e) {
                filterCustomer(jQuery('#sendsms_customer_filter').val());
            });

            //#2
            jQuery('#sendsms_query_user_role').change(function (e) {
                countUsersFromRole(jQuery('#sendsms_query_user_role').val());
            });
        }
    });
}

function initButtons(show) {
    status = jQuery('#current_status').val();
    if (status == 0) {
        jQuery('#sendsms_save').show();
    } else {
        jQuery('#sendsms_save').hide();
    }
    if (status <= 1 && jQuery('#nb_recipients').html() > 0) //	jQuerythis->_campaign->status <= 1 && !Tools::isSubmit('sendsms_transmit')
        jQuery('#sendsms_transmit').show();
    else
        jQuery('#sendsms_transmit').hide();
    if (status == 2 && jQuery('#nb_recipients').html() > 0) {
        jQuery('#sendsms_validate').show();
    } else {
        jQuery('#sendsms_validate').hide();
    }
    if (status > 0 && status < 3) {
        jQuery('#sendsms_cancel').show();
    } else {
        jQuery('#sendsms_cancel').hide();
    }
    if (jQuery('#id_sendsms_campaign').val() > 0 && (status == 0 || status >= 3)) {
        jQuery('#sendsms_delete').show();
    } else {
        jQuery('#sendsms_delete').hide();
    }
    if (jQuery('#id_sendsms_campaign').val() > 0)
        jQuery('#sendsms_duplicate').show();
    if (show)
        jQuery('#buttons').show();

    countRecipientsFromQuery();
    countUsersFromRole();
}

String.prototype.ucfirst = function () {
    return this.charAt(0).toUpperCase() + this.substr(1).toLowerCase();
};

function checkPhone(phone, international) {
    var reg = new RegExp("^[+]" + (international ? "" : "?") + "[0-9]{8,15}$");
    if (reg.test(phone) == 0) {
        alert(sendsms_error_phone_invalid);
        return false;
    }
    return true;
}

function addFreeRecipient() {
    var customer = { 'id_customer': '', 'phone': jQuery.trim(jQuery('#sendsms_phone').val()), 'firstname': jQuery.trim(jQuery('#sendsms_firstname').val()), 'lastname': jQuery.trim(jQuery('#sendsms_lastname').val()), 'iso_country': '', 'country': '' };
    return addRecipient(customer, true, true);
}

function addRecipient(customer, reset, international) {
    if (customer.phone && checkPhone(customer.phone, international)) {
        jQuery.ajax({
            type: "POST",
            async: false,
            cache: false,
            url: ajaxurl,
            dataType: "json",
            data: jQuery('#sendsms_form').serialize() + "&ajax=1&action=addRecipient&phone=" + encodeURIComponent(customer.phone) + "&firstname=" + customer.firstname + "&lastname=" + customer.lastname + "&id_customer=" + customer.id_customer + "&iso_country=" + customer.iso_country,
            success: function (data) {
                if (data) {
                    if (data.error)
                        alert(data.error);
                    else {
                        jQuery('#id_sendsms_campaign').val(data.campaign.id_sendsms_campaign);
                        jQuery('#id_campaign').html(data.campaign.id_sendsms_campaign);
                        jQuery('#ticket').html(data.campaign.ticket);
                        jQuery('#sendsms_title').val(data.campaign.title);
                        jQuery('#nb_recipients').html(data.campaign.nb_recipients);
                        initButtons();
                        var data = {
                            paged: parseInt(jQuery('input[name=paged]').val()) || '1',
                            order: jQuery('input[name=order]').val() || 'asc',
                            orderby: jQuery('input[name=orderby]').val() || 'title',
                            s: jQuery('input[name=s]').val() || '',
                            list: jQuery('input[name=list]').val(),
                            id_sendsms_campaign: jQuery('input[name=id_sendsms_campaign]').val()
                        };
                        list.update(data);
                    }
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert(textStatus + " " + errorThrown);
            }
        });
    }
    if (reset) {
        jQuery('#sendsms_firstname').val('');
        jQuery('#sendsms_lastname').val('');
        jQuery('#sendsms_phone').val('').focus();
    }
    return false;
}

function delRecipient(obj) {
    jQuery.ajax({
        type: "POST",
        async: false,
        cache: false,
        url: ajaxurl,
        dataType: "json",
        data: "action=delRecipient&" + "id_sendsms_campaign=" + jQuery('#id_sendsms_campaign').val() + "&id_sendsms_recipient=" + jQuery(obj).attr('id'),
        success: function (data) {
            if (data.valid) {
                // TODO force pager to make an ajax call
                //jQuery('.tablesorter')[0].config.pager.last.size = null;
                jQuery(obj).closest('tr').remove();
                jQuery(".displaying-num").text(data.campaign.nb_recipients + " elements");
                //jQuery('.tablesorter').trigger('update');
                //jQuery('#nb_recipients').html(data.campaign.nb_recipients);
                initButtons();
                //list.init();
            }
        }
    });
    return false;
}


function filterCustomer(parameter) {
    console.log("filterCustomer(" + parameter + ") - " + parameter.length);
    if (parameter.length > 2) {
        jQuery.ajax({
            type: "POST",
            async: false,
            cache: false,
            url: ajaxurl,
            dataType: "json",
            data: "ajax=1&action=filter&q=" + parameter,
            success: function (data) {
                if (data) {
                    if (data.error)
                        alert(data.error);
                    else {
                        console.log(data);
                        jQuery('#resultFilter').html("");
                        if (data.length == 0) {
                            jQuery('#resultFilter').hide();
                        } else {
                            $result = jQuery('#resultFilter');
                            $result.show();
                            for (i = 0; i < data.length; ++i) {
                                $result.append(jQuery("<br/>" + data[i].label + '<span class="plus" id="customerFilter' + data[i].obj.id_customer + '"></span>'));//.attr("value", data[i]).text(data[i].label));
                                jQuery('#customerFilter' + data[i].obj.id_customer).click((function () {
                                    var customer = data[i].obj;
                                    return () => {
                                        addRecipient(customer, true);
                                        jQuery('#resultFilter').hide();
                                    }
                                }()));
                            }
                        }
                    }
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert(textStatus + " " + errorThrown);
            }
        });
    }
    return false;
}

function countUsersFromRole() {
    var role = jQuery('#sendsms_query_user_role').val();
    jQuery.ajax({
        type: "POST",
        async: true,
        cache: false,
        url: ajaxurl,
        dataType: "json",
        data: "ajax=1&action=filterUser&q=" + role + "&id_sendsms_campaign=" + jQuery('#id_sendsms_campaign').val(),
        success: function (data) {
            if (data) {
                jQuery('#sendsms_query_user_result').html(data.total_rows);
                if (data.total_rows > 0)
                    jQuery('#sendsms_query_user_add').show();
                else
                    jQuery('#sendsms_query_user_add').hide();
            }
        }
    });
}

function addUsersFromRole() {
    if (jQuery('#sendsms_query_user_result').html() != 0) {
        var query = '';
        query += '&' + "q=" + jQuery('#sendsms_query_user_role').val();

        jQuery.ajax({
            type: "POST",
            async: false,
            cache: false,
            url: ajaxurl,
            dataType: "json",
            data: "action=addRecipientsFromRole&id_sendsms_campaign=" + jQuery('#id_sendsms_campaign').val() + query,
            success: function (data) {
                if (data) {
                    jQuery('#id_sendsms_campaign').val(data.campaign.id_sendsms_campaign);
                    jQuery('#id_campaign').html(data.campaign.id_sendsms_campaign);
                    jQuery('#ticket').html(data.campaign.ticket);
                    jQuery('#sendsms_title').val(data.campaign.title);
                    initButtons();
                    jQuery('#sendsms_query_user_result').html(data.total_rows);

                    if (data.errors.length > 0) {
                        var i = 0;
                        var message = "";
                        for (i = 0; i < data.errors.length && i<50; ++i) {
                            message += data.errors[i].message + '\r\n';
                        }
                        if (i==49) {
                            message += "..."
                        }
                        alert(message);
                    }

                    //reload recipient grid
                    var data = {
                        paged: list.__query(query, 'paged') || '1',
                        order: list.__query(query, 'order') || 'desc',
                        orderby: list.__query(query, 'orderby') || '',
                        s: jQuery('input[name=s]').val(),
                        list: jQuery('input[name=list]').val(),
                        id_sendsms_campaign: jQuery('input[name=id_sendsms_campaign]').val()
                    };
                    list.update(data);                    
                }
            }
        });
    }
}

function transmitToOWS() {
    jQuery('#sendsms_transmit').attr('disabled', true);
    jQuery.ajax({
        type: "POST",
        async: true,
        cache: false,
        url: ajaxurl,
        dataType: "json",
        data: "action=transmitOWS&id_sendsms_campaign=" + jQuery('#id_sendsms_campaign').val(),
        success: function (data) {
            if (data) {
                if (data.error) {
                    jQuery('#sendsms_transmit').attr('disabled', false);
                    initButtons(true);
                    alert(data.error);
                } else {
                    jQuery('#nb_recipients').html(data.campaign.nb_recipients);
                    //jQuery('#nb_sms').html(data.campaign.nb_sms); .toFixed(3)
                    jQuery('#price').html(data.campaign.price + ' €');
                    jQuery('#waiting_transfert').html(data.total_rows);
                    //refresh recipient list
                    var dataList = {
                        paged: parseInt(jQuery('input[name=paged]').val()) || '1',
                        order: jQuery('input[name=order]').val() || 'asc',
                        orderby: jQuery('input[name=orderby]').val() || 'title',
                        s: jQuery('input[name=s]').val() || '',
                        list: jQuery('input[name=list]').val(),
                        id_sendsms_campaign: jQuery('input[name=id_sendsms_campaign]').val()
                    };
                    list.update(dataList);

                    if (data.message) {
                        jQuery('#progress_bar').text(data.message);
                    }

                    if (data.finished) {
                        jQuery('#progress_bar').hide();
                        jQuery('#message').hide();
                        jQuery('#status').html(data.campaign.status_label);
                        jQuery('#current_status').val(data.campaign.status);
                        initButtons(true);
                    } else {
                        transmitToOWS();
                    }
                }
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            jQuery('#sendsms_transmit').attr('disabled', false);
            initButtons(true);
            alert(errorThrown);
        }
    });
}

function addCSV() {
    if (!jQuery('#sendsms_csv').val())
        alert(sendsms_error_csv);
    else
        jQuery('#sendsms_form').submit();
}

function countRecipientsFromQuery() {
    if (jQuery('#sendsms_query_result').length > 0) {
        var query = '';
        jQuery('#sendsms_query select, #sendsms_query input[type=text], #sendsms_query input:checked').each(function (e) {
            query += '&' + jQuery(this).attr('name') + '=' + jQuery(this).val();
        });

        jQuery.ajax({
            type: "POST",
            async: true,
            cache: false,
            url: ajaxurl,
            dataType: "json",
            data: "action=countRecipientFromQuery&id_sendsms_campaign=" + jQuery('#id_sendsms_campaign').val() + query,
            success: function (data) {
                if (data) {
                    jQuery('#sendsms_query_result').html(data.total_rows);
                    if (data.total_rows > 0)
                        jQuery('#sendsms_query_add').show();
                    else
                        jQuery('#sendsms_query_add').hide();
                }
            }
        });
    }
}

function addRecipientsFromQuery() {
    if (jQuery('#sendsms_query_result').html() != 0) {
        var query = '';
        jQuery('#sendsms_query select, #sendsms_query input[type=text], #sendsms_query input:checked').each(function (e) {
            query += '&' + jQuery(this).attr('name') + '=' + jQuery(this).val();
        });

        jQuery.ajax({
            type: "POST",
            async: false,
            cache: false,
            url: ajaxurl,
            dataType: "json",
            data: "action=addRecipientsFromQuery&id_sendsms_campaign=" + jQuery('#id_sendsms_campaign').val() + query,
            success: function (data) {
                if (data) {
                    jQuery('#id_sendsms_campaign').val(data.campaign.id_sendsms_campaign);
                    jQuery('#id_campaign').html(data.campaign.id_sendsms_campaign);
                    jQuery('#ticket').html(data.campaign.ticket);
                    jQuery('#sendsms_title').val(data.campaign.title);
                    initButtons();
                    jQuery('#sendsms_query_result').html(0);

                    //reload recipient grid
                    var data = {
                        paged: list.__query(query, 'paged') || '1',
                        order: list.__query(query, 'order') || 'desc',
                        orderby: list.__query(query, 'orderby') || '',
                        s: jQuery('input[name=s]').val(),
                        list: jQuery('input[name=list]').val(),
                        id_sendsms_campaign: jQuery('input[name=id_sendsms_campaign]').val()
                    };
                    list.update(data);

                }
            }
        });
    }
}

function initTableSorter() {
    jQuery('.recipients').delegate('img.delete', 'click', function () {
        return delRecipient(jQuery(this));
    });
}

function initTable() {
    list = {
        /**
         * Register our triggers
         * 
         * We want to capture clicks on specific links, but also value change in
         * the pagination input field. The links contain all the information we
         * need concerning the wanted page number or ordering, so we'll just
         * parse the URL to extract these variables.
         * 
         * The page number input is trickier: it has no URL so we have to find a
         * way around. We'll use the hidden inputs added in TT_Example_List_Table::display()
         * to recover the ordering variables, and the default paged input added
         * automatically by WordPress.
         */
        init: function () {

            // This will have its utility when dealing with the page number input
            var timer;
            var delay = 500;

            // Pagination links, sortable link
            jQuery('.tablenav-pages a, .manage-column.sortable a, .manage-column.sorted a').on('click', function (e) {
                // We don't want to actually follow these links
                e.preventDefault();
                // Simple way: use the URL to extract our needed variables
                var query = this.search.substring(1);
                var data = {
                    paged: list.__query(query, 'paged') || '1',
                    order: list.__query(query, 'order') || 'desc',
                    orderby: list.__query(query, 'orderby') || '',
                    s: jQuery('input[name=s]').val(),
                    list: jQuery('input[name=list]').val(),
                    id_sendsms_campaign: jQuery('input[name=id_sendsms_campaign]').val()
                };
                list.update(data);
            });

            // Page number input
            jQuery('input[name=paged]').on('keyup', function (e) {

                // If user hit enter, we don't want to submit the form
                // We don't preventDefault() for all keys because it would
                // also prevent to get the page number!
                if (13 == e.which)
                    e.preventDefault();

                // This time we fetch the variables in inputs
                var data = {
                    paged: parseInt(jQuery('input[name=paged]').val()) || '1',
                    order: jQuery('input[name=order]').val() || 'asc',
                    orderby: jQuery('input[name=orderby]').val() || 'title',
                    s: jQuery('input[name=s]').val() || '',
                    list: jQuery('input[name=list]').val()
                };

                // Now the timer comes to use: we wait half a second after
                // the user stopped typing to actually send the call. If
                // we don't, the keyup event will trigger instantly and
                // thus may cause duplicate calls before sending the intended
                // value
                window.clearTimeout(timer);
                timer = window.setTimeout(function () {
                    list.update(data);
                }, delay);
            });

            //del button
            jQuery('a.delete').click(function () {
                return delRecipient(jQuery(this));
            });

            jQuery('#search-form').on('submit', function (e) {
                e.preventDefault();
                // This time we fetch the variables in inputs
                var data = {
                    paged: parseInt(jQuery('input[name=paged]').val()) || '1',
                    order: jQuery('input[name=order]').val() || '',
                    orderby: jQuery('input[name=orderby]').val() || '',
                    s: jQuery('input[name=s]').val(),
                    list: jQuery('input[name=list]').val(),
                    id_sendsms_campaign: jQuery('input[name=id_sendsms_campaign]').val()
                };
                list.update(data);
            });
        },
        /** AJAX call
         * 
         * Send the call and replace table parts with updated version!
         * 
         * @param    object    data The data to pass through AJAX
         */
        update: function (data) {
            jQuery.ajax({
                // /wp-admin/admin-ajax.php
                url: ajaxurl,
                // Add action and nonce to our collected data
                data: jQuery.extend(
                    {
                        _ajax_custom_list_nonce: jQuery('#_ajax_custom_list_nonce').val(),
                        action: '_ajax_fetch_custom_list',
                    },
                    data
                ),
                // Handle the successful result
                success: function (response) {

                    // WP_List_Table::ajax_response() returns json
                    var response = jQuery.parseJSON(response);

                    // Add the requested rows
                    if (response.rows.length)
                        jQuery('#the-list').html(response.rows);
                    // Update column headers for sorting
                    if (response.column_headers.length)
                        jQuery('thead tr, tfoot tr').html(response.column_headers);
                    // Update pagination for navigation
                    if (response.pagination.bottom.length)
                        jQuery('.tablenav.top .tablenav-pages').html(jQuery(response.pagination.top).html());
                    if (response.pagination.top.length)
                        jQuery('.tablenav.bottom .tablenav-pages').html(jQuery(response.pagination.bottom).html());

                    // Init back our event handlers
                    list.init();
                }
            });
        },
        /**
         * Filter the URL Query to extract variables
         * 
         * @see http://css-tricks.com/snippets/javascript/get-url-variables/
         * 
         * @param    string    query The URL query part containing the variables
         * @param    string    variable Name of the variable we want to get
         * 
         * @return   string|boolean The variable value if available, false else.
         */
        __query: function (query, variable) {

            var vars = query.split("&");
            for (var i = 0; i < vars.length; i++) {
                var pair = vars[i].split("=");
                if (pair[0] == variable)
                    return pair[1];
            }
            return false;
        },
    }

    // Show time!
    list.init();

}
