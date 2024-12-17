<?define("NOT_CHECK_PERMISSIONS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader,
    Bitrix\Main\Localization\Loc,
    Bitrix\Main\Context;


if(!Loader::IncludeModule("iblock"))
    return;

Loc::loadMessages(__FILE__);

$request = Context::getCurrent()->getRequest();
$files = $request->getFileList();
for ($i = 0; $i <count($files) ; $i++) {
    $id_d=rand();
    $value = $request->getFile("fil".$i);

    $arr_file=Array(
        "name" => $value["name"],
        "size" => $value["size"],
        "tmp_name" => $value["tmp_name"],
        "type" => "jpg, gif, bmp, png, jpeg,doc, txt, rtf,pdf",
        "old_file" => "",
        "del" => "Y",
        "MODULE_ID" => "iblock");
    $fid = CFile::SaveFile($arr_file, "forms");
    if($fid>0){
        $html.="<div id='file_wrap_".$id_d."' class='user-fileinput-item'>
               <span class='user-btn-del' id='del_".$id_d."' onclick='delBlock(this)'>&nbsp;</span>
                <div class='user-fileinput-item-name'>".$value["name"]."</div>
                <input type='hidden' name='PHOTO[n".$id_d."][id]'  value='".$fid."'>
                <input type='hidden' name='PHOTO[n".$id_d."][tmp_name]'  value='".CFile::GetPath($fid)."'>
               </div>
        ";
    }else{

        $html.="<div id='file_wrap_".$id_d."' class='user-fileinput-item'>".Loc::getMessage('ERROR_LOAD_FILE')."
               
                <span class='user-btn-del' id='del_".$id_d."' onclick='delBlock(this)'>&nbsp;</span></div>
        ";
    }
}

print_r($html)

?>