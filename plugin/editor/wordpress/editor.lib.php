<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/*
    환경설정에서 에디터 선택이 없는 경우에 사용하는 라이브러리 입니다.
    에디터 선택시 "선택없음"이 아닌 경우 plugin/editor 하위 디렉토리의 각 에디터이름/editor.lib.php 를 수정하시기 바랍니다.
*/

function g5_editor_html($id, $content)
{
    ob_start();
    wp_editor($content, $id);
    return ob_get_clean();
}


// textarea 로 값을 넘긴다. javascript 반드시 필요
function g5_get_editor_js($id)
{
    //$js_code = "var {$id}_wp_editor = tinyMCE.get('$id');\n";
    $js_code = "var {$id}_wp_editor = tinyMCE.editors['$id'];\n";
    return $js_code;
}


//  textarea 의 값이 비어 있는지 검사
function g5_chk_editor_js($id)
{
    return "jQuery('#{$id}-tmce').trigger('click');if (!jQuery.trim({$id}_wp_editor.getContent())) { alert(\"내용을 입력해 주십시오.\"); {$id}_wp_editor.focus(); return false; }\n";
    //return "if (!{$id}_editor.value) { alert(\"내용을 입력해 주십시오.\"); {$id}_editor.focus(); return false; }\n";
}
?>