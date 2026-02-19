require(['jquery','block_customnavigation/nestedSortable'],function(){

    $(document).ready(function () {

        /*==========================================
         Added by Dani Otelch at 29-09-2014
         to edit a menu item
         ============================================*/
        
        ns = $('ol.sortable').nestedSortable({
            forcePlaceholderSize: true,
            handle: 'div',
            helper: 'clone',
            items: 'li',
            opacity: .6,
            placeholder: 'placeholder',
            revert: 250,
            tabSize: 25,
            tolerance: 'pointer',
            toleranceElement: '> div',
            maxLevels: 4,
            isTree: true,
            expandOnHover: 700,
            startCollapsed: false,
            change: function(event, ui){
                
            }
        });

        $('.editMenu').click(function () {
            editing = true;
            var item_id = $(this).parents("li").attr("id");

            var res = item_id.split("_");

            $.ajax({
                type    : "GET",
                url     : "get_data.php",
                dataType: 'JSON',
                data    : "item=" + res[1],

                success: function (data) {
                    languages = data.languages;
                    if ( data !== null || data !== 'undefined' )
                    {
                        $('#item_cancel').show();

                        var da = $('input[name=label]').val();

                        font = /fa-/;
                        if ( data.icon !== null )
                            fa_icon = data.icon.search(font);

                        $('#fa-type').css('display', 'block');
                        $('#fa-type input').attr('value', data.icon);

                        if (data.type == 'link') {
                            $("input:radio[name='type']")[0].checked = true;
                            $("#tr_href, #tr_target").show();
                            $("#tr_module").hide();
                            $("#tr_label input").focus();

                            $('select[name=target]').val(data.target);


                        } else if (data.type == 'module') {
                            $('input:radio[name=type]')[2].checked = true;
                            $("#tr_href, #tr_target").hide();
                            $("#tr_module").show();
                            $("#tr_label input").focus();
                            $('select[name=module]').val(data.module);


                        } else {
                            $('input:radio[name=type]')[1].checked = true;
                            $("#tr_href, #tr_target").hide();
                            $("#tr_module").show();
                            $("#tr_label input").focus();
                        }

                        $('input[name=id_item]').val(res[1]);

                        $('input[name=h_icon]').val(data.icon);
                        $('input[name=h_sort]').val(data.sort);
                        $('input[name=h_roleid]').val(data.roleid);

                        $('input[name=h_parent]').val(data.parent);
                        $('input[name=type]').val(data.type);
                        $('input[name=module]').val(data.module);
                        $('input[name=label]').val(data.label);
                        $('input[name=href]').val(data.href);
                        $('input[name=inst_id]').val(data.inst_id);
                        /**
                        * change the onclick function to the button element
                        * @author Hugo S.
                        * @since june 05 of 2018
                        * @rlms
                        * @ticket 11
                        */
                        $('p.assign-role').parent().attr('onclick', 'mypopup("' + res[1] + '")');
                        $('#value_roles').val(data.roleid);
                        $('#item_add').val('Update');
                        $('input[name=action]').val('edit_item');
                        editing = true
                        
                        checkLanguage();
                        
                        $('html, body').animate({
                            scrollTop: $("body").offset().top
                        }, 1000);

                    }

                }
            });



        });

        $('#select_language').change(function() {
            checkLanguage();
        });
        
        function checkLanguage() {
            if($('#label').val() != '') {
                if (typeof languages[$('#select_language').val()] !== "undefined") {
                    $('#select_index_language').val(languages[$('#select_language').val()]);
                } else {
                    $('#select_index_language').val('');
                }
            }
        }
//=========================================//


        // 
        $("#tr_label input").focus();

        $("#save_menu").click(function () {
            var arraied = $('ol.sortable').nestedSortable('toArray', {startDepthCount: 0});

            var length = arraied.length;
            var arr = [];

            for (i = 1; i < length; i++)
            {
                var current = arraied[i];
                var id = current.id;
                var parent_id = current.parent_id;

                var html = $("#menuItem_" + id + " .itemObject").html();
                if (html) {
                    var obj = json_decode(html);
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


    function _deleteMenu()
    {
        $('.deleteMenu').unbind("click").click(function () {
            var item_id = $(this).parents("li").attr("id");
            $('#' + item_id).remove();
        });
    }

    //Add for Andrés Aguilar
    $('#item_cancel').click(function () {
        $(this).css('display', 'none');
        $('#item_add').val('Add');
        /**
        * change the onclick function to the button element
        * @author Hugo S.
        * @since june 05 of 2018
        * @rlms
        * @ticket 11
        */
        $('p.assign-role').parent().attr('onclick', 'mypopup("0")');
        $('#value_roles').val('');
        editing = false;
        
    })

    /*
     * @Author Andres
     * @since 20/03/2015
     * @rlms
     */
    $('#icon_font').click(function () {
        $("#icon-type").css('display', 'none');
        $("#fa-type").css('display', 'block');
    });

    $('#icon_image').click(function () {
        $("#fa-type").css('display', 'none');
        $("#icon-type").css('display', 'block');
    });

    $('#edit-font').click(function () {
        $('#icon-fonts div ul li a').removeClass('selected');
        var nameicon = $('#fa-type input[type=text]').attr('value') ;
        if(nameicon !== undefined){
            nameicon = nameicon.replace(' ','');
            $('#icon-fonts div ul li a[data-font=' + nameicon + ']').addClass('selected');
        }
        $('#icon-fonts').css('display', 'block');
    });

    $('#icon-fonts div .close-fa').click(function () {
        $('#icon-fonts').css('display', 'none');
    });

    $('#icon-fonts div ul li a').click(function (event) {
        $('#fa-type-text').attr('value', $(this).data('font'));
        $('#icon-fonts').css('display', 'none');
    });
})