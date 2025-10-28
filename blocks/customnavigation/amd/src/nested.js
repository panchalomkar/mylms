/* eslint-disable require-jsdoc */
define(["jquery", "block_customnavigation/nestedSortable"], function (
    $,
    nestedSortable
) {
    var languages = new Array();

    function _deleteMenu() {
        $(".deleteMenu")
            .unbind("click")
            .click(function () {
                var item_id = $(this)
                    .parents("li")
                    .attr("id");
                $("#" + item_id).remove();
            });
    }

    function checkLanguage() {
        if ($("#label").val() != "") {
            if (typeof languages[$("#select_language").val()] !== "undefined") {
                $("#select_index_language").val(languages[$("#select_language").val()]);
            } else {
                $("#select_index_language").val("");
            }
        }
    }
    function searchicon(icon) {
        var iconlist = [];
        if (icon == "") {
            icon = "";
        }
        $.getJSON(
            M.cfg.wwwroot + "/theme/remui/json/font-awesome-data.json",
            function (result) {
                $.each(result, function (i, obj) {
                    title = i.replace("fa-", "");
                    if (icon != "") {
                        if (i.indexOf(icon) != -1) {
                            iconlist.push(
                                "<li> <a title = '"+title+"' data-font='" +
                                i +
                                "'><i class='fa " +
                                i +
                                "'></i></a></li>"
                            );
                        }
                    } else {
                        iconlist.push(
                            "<li> <a title = '"+title+"' data-font='" +
                            i +
                            "'><i class='fa " +
                            i +
                            "'></i></a></li>"
                        );
                    }
                });
                $("#listicons").html(iconlist);
            }
        );
    }
    return {
        init: function () {
            $(document).ready(function () {
                ns = $("ol.sortable").nestedSortable( {
                    forcePlaceholderSize: true,
                    handle: "div",
                    helper: "clone",
                    items: "li",
                    opacity: 0.6,
                    placeholder: "placeholder",
                    revert: 250,
                    tabSize: 25,
                    tolerance: "pointer",
                    toleranceElement: "> div",
                    maxLevels: 5,
                    isTree: true,
                    expandOnHover: 700,
                    startCollapsed: false,
                    change: function (event, ui) { }
                });

                $(".editMenu").click(function () {
                    editing = true;
                    var item_id = $(this)
                        .parents("li")
                        .attr("id");

                    var res = item_id.split("_");

                    $.ajax({
                        type: "GET",
                        url: "get_data.php",
                        dataType: "JSON",
                        data: "item=" + res[1],

                        success: function (data) {
                            languages = data.languages;
                            if (data !== null || data !== "undefined") {
                                $("#item_cancel").show();

                                var da = $("input[name=label]").val();

                                font = /fa-/;
                                if (data.icon !== null) fa_icon = data.icon.search(font);

                                $("#fa-type").css("display", "block");
                                $("#fa-type input").attr("value", data.icon);

                                if (data.type == "link") {
                                    $("input:radio[name='type']")[0].checked = true;
                                    $("#tr_href, #tr_target").show();
                                    $("#tr_module").hide();
                                    $("#tr_label input").focus();

                                    $("select[name=target]").val(data.target);
                                } else if (data.type == "module") {
                                    $("input:radio[name=type]")[2].checked = true;
                                    $("#tr_href, #tr_target").hide();
                                    $("#tr_module").show();
                                    $("#tr_label input").focus();
                                    $("select[name=module]").val(data.module);
                                } else {
                                    $("input:radio[name=type]")[1].checked = true;
                                    $("#tr_href, #tr_target").hide();
                                    $("#tr_module").show();
                                    $("#tr_label input").focus();
                                }

                                $("input[name=id_item]").val(res[1]);

                                $("input[name=h_icon]").val(data.icon);
                                $("input[name=h_sort]").val(data.sort);
                                $("input[name=h_roleid]").val(data.roleid);

                                $("input[name=h_parent]").val(data.parent);
                                $("input[name=type]").val(data.type);
                                $("input[name=module]").val(data.module);
                                $("input[name=label]").val(data.label);
                                $("input[name=href]").val(data.href);
                                $("input[name=inst_id]").val(data.inst_id);

                                $("#roles")
                                    .val(data.roleid)
                                    .trigger("change");
                                $("#item_add").val("Update");
                                $("input[name=action]").val("edit_item");
                                editing = true;

                                checkLanguage();

                                $("html, body").animate(
                                    {
                                        scrollTop: $("body").offset().top
                                    },
                                    1000
                                );
                            }
                        }
                    });
                });

                $("#select_language").change(function () {
                    checkLanguage();
                });

                $("#tr_label input").focus();

                $("#save_menu").click(function () {
                    var arraied = $("ol.sortable").nestedSortable("toArray", {
                        startDepthCount: 0
                    });

                    var length = arraied.length;
                    var arr = [];

                    for (i = 1; i < length; i++) {
                        var current = arraied[i];
                        var id = current.id;
                        var parent_id = current.parent_id;

                        var html = $("#menuItem_" + id + " .itemObject").html();
                        if (html) {
                            //var obj = json_decode(html);
                            var obj = jQuery.parseJSON(html);
                        } else {
                            var obj = {};
                        }

                        obj.id = id;
                        obj.parent_id = parent_id;

                        arr.push(obj);
                    }

                    $("#customnavigation_structure").val(json_encode(arr));
                    $("#customnavigation_structure_form").submit();

                    return true;
                });

                $("#type_link").click(function () {
                    $("#tr_href, #tr_target").show();
                    $("#tr_module").hide();
                    $("#tr_label input").focus();
                });

                $("#type_container").click(function () {
                    $("#tr_href, #tr_target").hide();
                    $("#tr_module").hide();
                    $("#tr_label input").focus();
                });

                $("#type_module").click(function () {
                    $("#tr_href, #tr_target").hide();
                    $("#tr_module").show();
                    $("#tr_label input").focus();
                });

                _deleteMenu();
            });
            //clearall all selected roles
            $('#clearall').on('click', function(){
                $('#roles').val(null).trigger("change");
            });

            $("#save_menu").click();

            $("#item_cancel").click(function () {
                $(this).css("display", "none");
                $("#item_add").val("Add");
                $("#roles")
                    .val("")
                    .trigger("change");
                editing = false;
            });

            $("#icon_font").click(function () {
                $("#icon-type").css("display", "none");
                $("#fa-type").css("display", "block");
            });

            $("#icon_image").click(function () {
                $("#fa-type").css("display", "none");
                $("#icon-type").css("display", "block");
            });

            $("#edit-font").click(function () {
                $("#search_icon").val("");
                searchicon("");
                $("#icon-fonts div ul li a").removeClass("selected");
                var nameicon = $("#fa-type input[type=text]").attr("value");
                if (nameicon !== undefined) {
                    nameicon = nameicon.replace(" ", "");
                    $('#icon-fonts div ul li a[data-font="' + nameicon + '"]').addClass(
                        "selected"
                    );
                }
                $("#icon-fonts").css("display", "block");
            });

            $(document).on("click", "#icon-fonts div .close-fa", function (event) {
                $("#icon-fonts").css("display", "none");
            });

            $(document).on("click", "#icon-fonts ul li a", function (event) {
                $("#fa-type-text").attr("value", $(this).data("font"));
                $("#icon-fonts").css("display", "none");
            });

            // Icon search
            $("#search_icon").keyup(function (event) {
                $("#listicons").html("");
                var icon = $(this).val();
                searchicon(icon);
            });
        }
    };
});
