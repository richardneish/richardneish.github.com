/* 
 * All dynamic events
 * 
 */

$(document).ready(function() {
    add_events();
});


function add_events() {
    var ajax_file       = $("#ajax-file").val();
    var current_state   = $("#current-state");
    var task_list       = $("#view-tasks");
    var edit_area       = $("#edit-tasks");
    var text_area       = $("#edit-tasks>textarea");

    edit_area.hide();
    task_list.show();

    $("#edit-button").live("click", function() {
        if(current_state.val() != 'editclick|true') {
            $(this).attr({"enabled": false});
            current_state.val('editclick:true');
            $.get(ajax_file,
                { event: "editclick" },
                function(data) {
                    task_list.hide();
                    edit_area.show();
                    text_area.val(data);
                }
            );
        }
    });

    $("#replace-button").live("click", function() {
        var find_text = $("#find-word").val();
        var replace_text = $("#replace-word").val();
        if(find_text != "" && replace_text != "") {
            find_text = new RegExp(find_text, "gi");
            var edit_text = text_area.val();
            edit_text = edit_text.replace(find_text, replace_text);
            text_area.val(edit_text);
        }
    });

    /* this is the 'Save' button, for editing area */
    $("#edit-tasks input.save-button").live("click", function() {
        current_state.val("startpage|true");
        $.post(ajax_file,
            { event: "saveclick", value: text_area.val() },
            function(data){
                edit_area.hide();
                task_list.show();
                update_sidebars();
                show_tasks(data);
            }
        );
    });

    $("#back-button").live("click", function() {
        current_state.val("startpage|true");
        $("#find-text").val("");
        $.get(ajax_file,
            { event: "startpage" },
            function(data) {
                edit_area.hide();
                task_list.show();
                show_tasks(data);
            }
        );
    });

    $(".tag").live("click", function() {
        current_state.val("tagclick|" + this.innerHTML);
        $.get(ajax_file,
            { event: "tagclick", value: this.innerHTML },
            function(data) {
                show_tasks(data);
            }
        );
    });

    $("#find-box").live("keyup", function(event){
        if (event.keyCode == 13) {
            find_box();
        }
    });

    $("#find-button").live("click", function() {
        find_box();
    });

    function find_box() {
        var find_box = $("#find-box");
        var expression = find_box.val();
        find_box.val('');
        var prev_state = current_state.val();
        current_state.val("findtext|" + expression);
        $.get(ajax_file,
            { event: "findtext", value: expression , previous: prev_state},
            function(data) {
                var tasks = data.split("||");
                show_tasks(tasks[0]);
                var is_new_task = tasks[1];
                if(is_new_task == "true") {
                    current_state.val("startpage|true");
                    update_sidebars();
                    show_message("Task added!", 3);
                }
            }
        );
    }

    $(".tasks h3").live("click", function() {
        current_state.val("projectclick|" + this.innerHTML);
        $.get(ajax_file,
            { event: "projectclick", value: this.innerHTML },
            function(data) {
                show_tasks(data);
            }
        );

    });

    $(".projects li").live("click", function() {
        current_state.val("projectclick|" + this.innerHTML);
        $.get(ajax_file,
            { event: "projectclick", value: this.innerHTML },
            function(data) {
                show_tasks(data);
            }
        );

    });

    $(".tagdue").live("click", function() {
        current_state.val("tagdueclick|" + this.innerHTML);
        $.get(ajax_file,
            { event: "tagdueclick", value: this.innerHTML },
            function(data) {
                show_tasks(data);
            }
        );
    });

    $(".star-tag").live("click", function() {
        current_state.val("findstar|true");
        $.get(ajax_file,
            { event: "findstar" },
            function(data) {
                show_tasks(data);
            }
        );
    });

    $(".done-tag").live("click", function() {
        current_state.val("findstar|true");
        $.get(ajax_file,
            { event: "finddone" },
            function(data) {
                show_tasks(data);
            }
        );
    });

    $(".due-tag").live("click", function() {
        current_state.val("finddue|true");
        $.get(ajax_file,
            { event: "finddue" },
            function(data) {
                show_tasks(data);
            }
        );
    });

    // all the task buttons

    $(".check-done").live("click", function() {
        $.get(ajax_file,
            { event: "doneclick", value: $(this).attr("value"), current: current_state.val() },
            function(data) {
                show_tasks(data);
            }
        );
    });

    $(".star-button").live("click", function() {
        $.get(ajax_file,
            { event: "starclick", value: $(this).attr("name"), current: current_state.val() },
            function(data) {
                show_tasks(data);
                show_message("Changed highlighting!", 0);
            }
        );
    });

    $("#staroff-button").live("click", function() {
        $.get(ajax_file,
            { event: "staroffclick", current: current_state.val() },
            function(data) {
                show_tasks(data);
            }
        );
    });

    $(".archive-button").live("click", function() {
        $.get(ajax_file,
            { event: "archiveclick", value: $(this).attr("name"), current: current_state.val() },
            function(data) {
                show_tasks(data);
                update_sidebars();
                show_message("Task archived!", 2);
            }
        );
    });

    $(".delete-button").live("click", function() {
        $.get(ajax_file,
            { event: "deleteclick", value: $(this).attr("name"), current: current_state.val() },
            function(data) {
                show_tasks(data);
                update_sidebars();
                show_message("Task deleted!", 1);
            }
        );
    });

    $(".tab li").live("click", function() {
        var fileindex = $(this).attr("value");
        if(fileindex >= 0) {
            current_state.val("startpage|true");
            $(".tab li").removeClass("active");
            $(this).addClass("active");
            $.get(ajax_file,
                { event: "changefile", value: fileindex },
                function(data) {
                    show_tasks(data);
                    update_sidebars();
                }
            );
        }
    });

    function show_message(message, backgrd) {
        // yellow, orange, green, blue
        var colours = ["#FFFF88", "#FF7400", "#CDEB8B", "#C3D9FF"];
        var colour = colours[backgrd];
        $("#message-banner span").text(message);
        var message_banner = $("#message-banner");
        var new_top = $(window).scrollTop()-15+"px";
        message_banner.css({ "background":colour, "top":new_top });
        message_banner.animate({ top:"+=20px", opacity:200 }, { duration:900 });
        message_banner.animate({ top:"-=20px", opacity:0 }, { duration:600 });
    }

    function update_sidebars() {
        $.get(ajax_file,
            { event: "update_sidebars" },
            function(data) {
                var lists = data.split("||");
                $(".projects").html(lists[0]);
                $(".tags").html(lists[1]);
            }
        );
    }

    function show_tasks(results) {
        task_list.html(results);
    }
} /* add_events */

