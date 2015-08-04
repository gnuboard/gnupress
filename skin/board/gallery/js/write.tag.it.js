(function($){

    $.when( g5_tag_ajax() ).then(function( data, textStatus, jqXHR ) {
        
        gnupress.wr_tags = data;

        $("#wr_tag_input").hide();
        $('#g5_singleFieldTags').tagit({
            availableTags: gnupress.wr_tags,
            placeholderText : g5_object.placeholderText,
            autocomplete: {delay: 0, minLength: 1},
            singleField: true,
            singleFieldNode: $('#wr_tag_input'),
            allowSpaces : true,
            onTagLimitExceeded : function(e, ui){}
        }).on("keypress keydown keyup change", "input", function(e){
            if(e.keyCode == 13) { // Enter 방지
                e.preventDefault();
                return false;
            }
        });
    });

    function g5_tag_ajax() {
        // NOTE:  This function must return the value 
        //        from calling the $.ajax() method.

        var bo_table = $("input[name='bo_table']").val(),
			security = $("input[name='g5_nonce_field']").val(); //nonce체크

        return $.ajax({
            url: gnupress.ajax_url,
            dataType: "json",
            data: { action : 'g5_get_tags', bo_table : bo_table, security : security }
        });
    }

})(jQuery);