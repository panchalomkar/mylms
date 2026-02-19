window.addEventListener('load', function () { 
  
    jQuery(document).ready(function($) {
        /**
         * Activity video, I enable the video progress completion is not working
         * when the completion tracking is the third option
         */
         $("#page-mod-videofile-view h2").removeClass('hidden-xs hidden-sm hidden-md hidden-lg');
         
        if ($("#id_videoprogressenabled").length > 0 ) {
    
            var $videoprogressenabled = $("#id_videoprogressenabled");
    
            // Check when element is sets on disabled
            $videoprogressenabled.watch('disabled', function() {
                if( $videoprogressenabled.is(":disabled") ) {
                    $videoprogressenabled.parent( ".form-checkbox" ).addClass('form-disabled');
                }
            });
    
    
            $videoprogressenabled.on('click', function() {
    
                if( $(this).is(':checked') ) {
                    $("#id_completionview").prop({
                        checked: false,
                        disabled: true
                    });
                } else {
                    $("#id_completionview").prop({
                        checked: true,
                        disabled: false
                    });
                    $("#id_completionview").parent( ".form-checkbox" ).removeClass('form-disabled');
                }
            });
        }
    
        /**
         * When the third completion criteria condition is selected the video progress is disabled
         * Both completion criteria options should not be possible to be selected at the same time.
         */
        if ($("#id_completionview").length > 0 ) {
    
            var $completionview = $("#id_completionview");
    
            $completionview.on('click', function() {
    
                if( $(this).is(':checked') ) {
                    $("#id_videoprogressenabled").prop({
                        checked: false,
                        disabled: true
                    });
                    $("#id_videoprogress").prop('disabled', true);
                } else {
                    $("#id_videoprogressenabled").prop({
                        checked: true,
                        disabled: false
                    });
                    $("#id_videoprogress").prop('disabled', false);
                    $("#id_videoprogressenabled").parent( ".form-checkbox" ).removeClass('form-disabled');
                }
            });
    
            // Check when element is sets on disabled
            $completionview.watch('disabled', function() {
                if( $completionview.is(":disabled") ) {
                    $completionview.parent( ".form-checkbox" ).addClass('form-disabled');
                }
            });
        }
    
        $("#id_completion").on('change', function() {
    
            if( $(this).val() == '2' )
                $("body#page-mod-videofile-mod .form-checkbox").removeClass('form-disabled');
    
        });
    
    
    });
    
    var matched, browser;
    
    $.uaMatch = function( ua ) {
        ua = ua.toLowerCase();
    
        var match = /(chrome)[ \/]([\w.]+)/.exec( ua ) ||
            /(webkit)[ \/]([\w.]+)/.exec( ua ) ||
            /(opera)(?:.*version|)[ \/]([\w.]+)/.exec( ua ) ||
            /(msie) ([\w.]+)/.exec( ua ) ||
            ua.indexOf("compatible") < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec( ua ) ||
            [];
    
        return {
            browser: match[ 1 ] || "",
            version: match[ 2 ] || "0"
        };
    };
    
    matched = $.uaMatch( navigator.userAgent );
    browser = {};
    
    if ( matched.browser ) {
        browser[ matched.browser ] = true;
        browser.version = matched.version;
    }
    
    // Chrome is Webkit, but Webkit is also Safari.
    if ( browser.chrome ) {
        browser.webkit = true;
    } else if ( browser.webkit ) {
        browser.safari = true;
    }
    
    $.browser = browser;
    
    // Function to watch for attribute changes
    // http://darcyclarke.me/development/detect-attribute-changes-with-jquery
    $.fn.watch = function(props, callback, timeout){
        if(!timeout)
            timeout = 10;
        return this.each(function(){
            var el         = $(this),
                func     = function(){ __check.call(this, el) },
                data     = {    props:     props.split(","),
                            func:     callback,
                            vals:     [] };
            $.each(data.props, function(i) { data.vals[i] = el.attr(data.props[i]); });
            el.data(data);
            if (typeof (this.onpropertychange) == "object"){
                el.bind("propertychange", callback);
            } else if ($.browser.mozilla){
                el.bind("DOMAttrModified", callback);
            } else {
                setInterval(func, timeout);
            }
        });
        function __check(el) {
            var data     = el.data(),
                changed = false,
                temp    = "";
            for(var i=0;i < data.props.length; i++) {
                temp = el.attr(data.props[i]);
                if(data.vals[i] != temp){
                    data.vals[i] = temp;
                    changed = true;
                    break;
                }
            }
            if(changed && data.func) {
                data.func.call(el, data);
            }
        }
    }
    
    }, false)
    