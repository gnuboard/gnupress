function g5_check_all(f)
{
    var chk = document.getElementsByName("chk[]");

    for (i=0; i<chk.length; i++)
        chk[i].checked = f.chkall.checked;
}

function g5_btn_check(f, act)
{
    if (act == "update") // 선택수정
    {
        f.action = list_update_php;
        str = g5_object.mtxt;   //수정
    }
    else if (act == "delete") // 선택삭제
    {
        f.action = list_delete_php;
        str = g5_object.dtxt;   //삭제
    }
    else
        return;

    var chk = document.getElementsByName("chk[]");
    var bchk = false;

    for (i=0; i<chk.length; i++)
    {
        if (chk[i].checked)
            bchk = true;
    }

    if (!bchk)
    {
        alert(str + g5_object.bchkchk); //할 자료를 하나 이상 선택하세요.
        return;
    }

    if (act == "delete")
    {
        if (!confirm(g5_object.del2))    //선택한 자료를 정말 삭제 하시겠습니까?
            return;
    }

    f.submit();
}

function g5_is_checked(elements_name)
{
    var checked = false;
    var chk = document.getElementsByName(elements_name);
    for (var i=0; i<chk.length; i++) {
        if (chk[i].checked) {
            checked = true;
        }
    }
    return checked;
}

function g5_delete_confirm()
{
    if(confirm(g5_object.del1+"\n\n"+g5_object.del2)) //한번 삭제한 자료는 복구할 방법이 없습니다.\n\n정말 삭제하시겠습니까?
        return true;
    else
        return false;
}

function g5_delete_confirm2(msg)
{
    if(confirm(msg))
        return true;
    else
        return false;
}