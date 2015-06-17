(function($) {
    //ajax로 문서를 가져올 경우 중복으로 이벤트를 가지게 되는 경우가 있어서 off를 쓴다.

    $(document).off("click", "#good_button, #nogood_button");
    // 추천, 비추천
    $(document).on("click", "#good_button, #nogood_button", function(e) {
        e.preventDefault();

        var $tx;
        if(this.id == "good_button")
            $tx = $("#bo_v_act_good");
        else
            $tx = $("#bo_v_act_nogood");

        excute_good($(this), $tx);
        return false;
    });
    
    $(document).off("click", "a.view_file_download");
    //파일 다운로드
    $(document).on("click", "a.view_file_download", function(e) {
        var othis = this;
        if(typeof view_file_download == 'function') {
            e.preventDefault();

            setTimeout(
                $.proxy(view_file_download, othis)
            , 1);
        }
    });

})(jQuery);