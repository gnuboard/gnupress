jQuery(function($){
    var mp3_url = "",
        g5_captcha_url = gnupress.new_url ? gnupress.new_url+((gnupress.new_url.indexOf('?') === -1) ? '?' : '&')+'action=kcaptcha_image' : '';

    $(".g5_captchar_wrap").on("click", "#captcha_reload", function(e){
        e.preventDefault();
        if( !g5_captcha_url ) return;
        $.ajax({
            type: 'POST',
            url: gnupress.ajax_url,
            data: {
                "action": "g5_kcaptcha_image"
            },
            cache: false,
            async: false,
            success: function(text) {
                $('#captcha_img').attr('src', g5_captcha_url+'&t=' + (new Date).getTime());
            }
        });

        $.ajax({
            type: 'POST',
            url: gnupress.ajax_url,
            data: {
                "action": "g5_kcaptcha_mp3"
            },
            cache: false,
            async: false,
            success: function(url) {
                if (url) {
                    mp3_url = url + "?t="+new Date().getTime();
                    $("#captcha_audio").attr("src", mp3_url);
                }
            }
        });
    }).find("#captcha_reload").trigger("click");

    $(".g5_captchar_wrap").on("click", "#captcha_mp3", function(e){
        e.preventDefault();
        $("body").css("cursor", "wait");

        $.ajax({
            type: 'POST',
            url: gnupress.ajax_url,
            data: {
                "action": "g5_kcaptcha_mp3"
            },
            cache: false,
            async: false,
            success: function(url) {
                if (url) {
                    mp3_url = url + "?t="+new Date().getTime();
                }
            }
        });

        var html5use = false;
        var html5audio = document.createElement("audio");
        if (html5audio.canPlayType && html5audio.canPlayType("audio/mpeg")) {
            var wav = new Audio(mp3_url);
            wav.id = "mp3_audio";
            wav.autoplay = true;
            wav.controls = false;
            wav.autobuffer = false;
            wav.loop = false;

            if ($("#mp3_audio").length) $("#mp3_audio").remove();
            $("#captcha_mp3").after(wav);

            html5use = true;
        }

        if (!html5use) {
            var object = '<object id="mp3_object" classid="clsid:22d6f312-b0f6-11d0-94ab-0080c74c7e95" height="0" width="0" style="width:0; height:0;">';
            object += '<param name="AutoStart" value="1" />';
            object += '<param name="Volume" value="0" />';
            object += '<param name="PlayCount" value="1" />';
            object += '<param name="FileName" value="' + mp3_url + '" />';
            object += '<embed id="mp3_embed" src="' + mp3_url + '" autoplay="true" hidden="true" volume="100" type="audio/x-wav" style="display:inline;" />';
            object += '</object>';
            if ($("#mp3_object").length)
                $("#mp3_object").remove();
            $("#captcha_mp3").after(object);
        }

        $("body").css("cursor", "default");
        return false;

    }).css('cursor', 'pointer');

    // 출력된 캡챠이미지의 키값과 입력한 키값이 같은지 비교한다.
    gnupress.chk_captcha = function()
    {
        var captcha_result = false;
        var captcha_key = document.getElementById('captcha_key');
        $.ajax({
            type: 'POST',
            url: gnupress.ajax_url,
            data: {
                'captcha_key': captcha_key.value,
                'action' : 'g5_kcaptcha_result'
            },
            cache: false,
            async: false,
            success: function(text) {
                captcha_result = text;
            }
        });

        if (!captcha_result) {
            alert('자동등록방지 입력 글자가 틀렸거나 입력 횟수가 넘었습니다.\n\n새로고침을 클릭하여 다시 입력해 주십시오.');
            captcha_key.select();
            captcha_key.focus();
            return false;
        }

        return true;
    }
});