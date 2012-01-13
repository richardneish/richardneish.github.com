var delay = 1000;
var timerID = null;
var secs = 0;
var checked = {};
function display_notice(page, notice) {
    innerXHTML('<div><b><i>' + notice + '<\/i><\/b></div>', document.getElementById("clock_div"));
    if (page) {
        page.form.submit();
    }
}
function hm_confirm(message) {
    var warn = 0;
    if (document.getElementById("enable_delete_warning")) {
        warn = document.getElementById("enable_delete_warning").value;
    }
    if (warn == 1) {
        return confirm(message);
    }
    else {
        return true;
    }
}
function expand_folder(div, link) {
    if (!document.getElementById(div)) {
        if (document.getElementById(link).innerHTML == '-') {
            document.getElementById(link).innerHTML = '+';
        }
        else {
            document.getElementById(link).innerHTML = '-';
        }
        return false;
    }
    if (document.getElementById(div).style.display == 'block') {
        document.getElementById(div).style.display = 'none';
        document.getElementById(link).innerHTML = '+';
    }
    else {
        document.getElementById(div).style.display = 'block';
        document.getElementById(link).innerHTML = '-';
    }
    return false;
}
function update_page(mailbox, title) {
    var page_id = false;
    if (document.getElementById("page_id")) {
        page_id = document.getElementById("page_id").value;
    }
    display_notice(false, update_notice);
    function update_page_callback(output) {
        var sections = output.split('^^' + page_id + '^^');
        if (sections.length == 6) {
            if (sections[0]) {
                innerXHTML(sections[0], document.getElementById("new_page"));
            }
            if (sections[1]) {
                innerXHTML(sections[1], document.getElementById("dd_inner"));
            }
            if (sections[2]) {
                innerXHTML(sections[2], document.getElementById("clock_div"));
            }
            if (sections[3]) {
                innerXHTML(sections[3], document.getElementById("unread_total"));
            }
            if (sections[4]) {
                var ta=document.createElement("textarea");
                ta.innerHTML = sections[4];
                var new_title = ta.value;
                document.title = new_title;
            }
            if (sections[5]) {
                innerXHTML(sections[5], document.getElementById("folder_outer"));
            }
        }
        innerXHTML('', document.getElementById('notices'));
    }
    x_ajax_update_page(false, mailbox, page_id, title, do_new_page_refresh, do_folder_list, update_page_callback);
}
function toggle_all(range_start, range_stop, folder_page) {
    var max = 0;
    var min = 0;
    var i = 0;
    var prefix = 'message_';
    if (folder_page) {
        prefix = folder_page;
    }
    if (range_start && range_stop) {
        max = range_stop;
        min = range_start;
    }
    else {
        if (document.getElementById("page_count")) {
            max = document.getElementById("page_count").value;
            min = 1;
        }
    }
    if (min && max) {
        for (i=min;i<=max;i++) {
            if (document.getElementById(prefix + i)) {
                if (document.getElementById(prefix + i).checked) {
                    document.getElementById(prefix + i).checked = false;
                }
                else {
                    document.getElementById(prefix + i).checked = 'checked';
                }
                if (do_new_page_refresh) {
                    save_checked_state(i);
                }
            }
        }
    }
}
function save_checked_state(index) {
    var uid = document.getElementById("message_" + index).value;
    var state = document.getElementById("message_" + index).checked;
    if (state) {
        checked[uid] = document.getElementById("mailboxes-" + uid + "").value;
    }
    else {
        checked[uid] = false;
    }
}
function restore_checked_state() {
    var max = document.getElementById("page_count").value;
    var uid;
    var state;
    var mbx;
    var str = '';
    for (key in checked) {
        str += key + ' ' + checked[key];
    }
    for (index=1;index<max;index++) {
        if (document.getElementById("message_" + index)) {
            uid = document.getElementById("message_" + index).value;
            mbx = document.getElementById("mailboxes-" + uid + "").value;
            if (checked[uid] == mbx) {
                document.getElementById("message_" + index).checked = true;
            }
        }
    }
}
function show_prev_next(div) {
    if (document.getElementById(div)) {
        if (document.getElementById(div).style.display == 'none') {
            document.getElementById(div).style.display = "block";
        }
        else {
            document.getElementById(div).style.display = "none";
        }
    }
}
function check_search_submit(event) {
    if (event.keyCode == 13 || event.which == 13) { 
        document.getElementById("search_button").click();
        return false;
    }
    else {
        return true;
    }
}
function show_contacts() {
    if (document.getElementById("contacts_select")) {
        if (document.getElementById("contacts_select").style.display == 'none') {
            document.getElementById("contacts_select").style.display = 'block';
            document.getElementById("contacts_visible").value = 1;
        }
        else {
            document.getElementById("contacts_select").style.display = 'none';
            document.getElementById("contacts_visible").value = 0;
        }
    }
}
function add_address(fld_id) {
    if (document.getElementById(fld_id)) {
        if (document.getElementById("contacts")) {
            var select = document.getElementById("contacts");
            var new_addy;
            var to = document.getElementById(fld_id).value;
            while (select.selectedIndex != -1) {
                new_addy = select.options[select.selectedIndex].value;
                select.options[select.selectedIndex].selected = false;
                if (to) {
                    to = to + ', ';
                }
                to = to + new_addy;
            }
            document.getElementById(fld_id).value = to;
            select.selectedIndex = -1;
        }
    }
}
function autosave_message() {
    var c_subject = false;
    var c_body = false;
    var c_to = false;
    var c_from = false;
    var c_cc = false;
    var message_id = false;
    var in_reply_to = false;
    var refs = false;
    var priority = false;
    var mdn = false;
    if (document.getElementById("compose_subject")) {
        c_subject = document.getElementById("compose_subject").value;
    }
    if (document.getElementById("compose_to")) {
        c_to = document.getElementById("compose_to").value;
    }
    if (document.getElementById("compose_from")) {
        c_from = document.getElementById("compose_from").selectedIndex;
    }
    if (document.getElementById("compose_cc")) {
        c_cc = document.getElementById("compose_cc").value;
    }
    if (document.getElementById("message_id")) {
        message_id = document.getElementById("message_id").value;
    }
    if (document.getElementById("compose_message")) {
        c_body = document.getElementById("compose_message").value;
    }
    if (document.getElementById("compose_in_reply_to")) {
        in_reply_to = document.getElementById("compose_in_reply_to").value;
    }
    if (document.getElementById("compose_references")) {
        refs = document.getElementById("compose_references").value;
    }
    if (document.getElementById("compose_priority")) {
        priority = document.getElementById("compose_priority").selectedIndex + 1;
    }
    if (document.getElementById("compose_mdn")) {
        mdn = document.getElementById("compose_mdn").checked;
    }
    var old_clock = document.getElementById("clock_div").innerHTML;
    function autosave_callback(php_output) {
        if (php_output) {
            document.getElementById("message_id").value = php_output;
        }
        document.getElementById("clock_div").innerHTML = old_clock;
    }
    display_notice(false, 'Auto-saving message ...');
    x_ajax_save_outgoing_message(false, c_subject, c_body, c_to, c_cc, c_from, message_id, in_reply_to, refs, priority, mdn, autosave_callback);
}
function innerXHTML(myxhtml, myObject) {
    if (myObject) {
        myObject.innerHTML = myxhtml;
    }
    return;
     /* XHTML/XML compliant innerHTML replacement.  still buggy 
     var parser = new DOMParser();
     var XMLdoc = parser.parseFromString("<div>" + myxhtml + "<\/div>", "text/html");
     for (i = 0; i < myObject.childNodes.length; i++) {
        //myObject.removeChild(myObject.childNodes[i]);
     }
     var root = XMLdoc.documentElement;
     for(i = 0; i < root.childNodes.length; i++) {
        myObject.appendChild(document.importNode(root.childNodes[i], true));
     }*/
}
function start_timer() {
    secs = secs + 1;
    if (c_autosave && secs % c_autosave == 0) {
        autosave_message();
    }
    if (do_new_page_refresh && secs % do_new_page_refresh == 0) {
        update_page(do_folder_dropdown, page_title);
    }
    if (secs % update_delay == 0 && do_folder_dropdown && !do_new_page_refresh) {
        update_page(do_folder_dropdown, page_title);
    }
    timerID = self.setTimeout("start_timer()", delay)
}
function get_contact_page(page) {
    function contact_page_callback(php_output) {
        innerXHTML(php_output, document.getElementById("compose_contacts"));
    }
    if (page) {
        x_ajax_next_contacts(1, contact_page_callback);
    }
    else {
        x_ajax_prev_contacts(0, contact_page_callback);
    }
}
function save_folder_vis_state(state) {
    x_ajax_save_folder_vis_state(false, state, false);
}
function hide_folder_list() {
    if (document.getElementById("folder_cell_inner")) { 
        if (document.getElementById("folder_cell_inner").style.display == 'none') {
            document.getElementById("folder_cell_inner").style.display = 'block';
            document.getElementById("show_folders").style.display = 'none';
            save_folder_vis_state(0);
        }
        else {
            document.getElementById("folder_cell_inner").style.display = 'none';
            document.getElementById("show_folders").style.display = 'inline';
            save_folder_vis_state(1);
        }
    }
}
function save_folder_state(folder) {
    x_ajax_save_folder_state(false, folder, false);
    return false;
}
function check_prev_next_del(message) {
    var action = document.getElementById('prev_next_action').value;
    if (action == 'delete') {
        return hm_confirm(message);
    }
    else {
        return true;
    }
}
function disable_destination() {
    var select = document.getElementById('prev_next_action');
    var action = select.options[select.selectedIndex].value;
    if (action == 'move' || action == 'copy') {
        document.getElementById('prev_next_folder').disabled = false;
    }
    else {
        document.getElementById('prev_next_folder').disabled = true;
    }
}
function autoAdjustIFrame(iframe) {
    try {
        if (iframe.contentWindow.location.href.indexOf('http') != -1) {
         
            var body = iframe.contentWindow.document.body;
            var height = Math.max(body.offsetHeight, body.scrollHeight);
            height += 40;
            if (height > 640) {
                iframe.style.height = "auto";
                iframe.style.height = height + "px";
            }
            else {
                if (iframe.contentWindow.document.defaultView) {
                    if (iframe.contentWindow.document.defaultView.getComputedStyle) {
                        height = iframe.contentWindow.document.defaultView.getComputedStyle(body,"").getPropertyValue("height");
                    }
                }
                else {
                    if (body.currentStyle["height"]) {
                        height = body.currentStyle["height"];
                    }
                }
                height = height.replace(/px$/, '');
                height = height*1 + 40;
                iframe.style.minHeight = height + "px";
                iframe.style.height = "auto";
                iframe.style.height = height + "px";
            }
            return height;
         
        }
    } catch(err) {}
}
function open_window(url, width, height, id) {
    if (!id) {
        id = '_blank';
    }
    window.open(url, id, 'scrollbars=yes,statusbar=no,resizable=yes,width=' + width + ',height=' + height);
    return false;
}
function refresh_parent() {
    if (opener && opener.refresh_self) {
        opener.refresh_self();
    }
    return false;
}
function refresh_self() {
    document.location.href = document.location.href; 
    return false;
}
function open_parent_window(url) {
    if (opener && opener.open_parent_window) {
        opener.document.location.href = url;
    }
    else {
        window.open(url, '_blank', '');
    }
}
