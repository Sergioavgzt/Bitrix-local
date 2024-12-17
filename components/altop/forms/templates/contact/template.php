<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$this->setFrameMode(true);

use Bitrix\Main\Localization\Loc; ?>

<h2><?=$arResult["IBLOCK"]["NAME"]?></h2>
<div class="popup-window popup-window-with-titlebar pop-up forms short">
    <form action="<?= $this->__component->__path ?>/script.php" id="<?= $arResult['ELEMENT_AREA_ID'] ?>_form"
          enctype="multipart/form-data">
        <span class="alert"></span>
         <?foreach ($arResult["IBLOCK"]["PROPERTIES"] as $arProp){?>
            <div class="row">
                <div class="span1"><?= $arProp["NAME"] . ($arProp["IS_REQUIRED"] == "Y" ? "<span class='mf-req'>*</span>" : "")?></div>
                <div class="span2">
                    <?if ($arProp["PROPERTY_TYPE"] == "S") {
                        if ($arProp["USER_TYPE"] != "HTML") {?>
                            <input type="text" name="<?= $arProp['CODE']?>"
                                   value="<?= ($arProp['CODE'] == 'NAME' ? $arResult['USER']['NAME'] : ($arProp['CODE'] == 'EMAIL' ? $arResult['USER']['EMAIL'] : '')); ?>"/>
                        <?}else{?>
                            <textarea name="<?= $arProp['CODE'] ?>" rows="3"
                                      style="height:<?= $arProp['USER_TYPE_SETTINGS']['height'] ?>px; min-height:<?= $arProp['USER_TYPE_SETTINGS']['height'] ?>px; max-height:<?= $arProp['USER_TYPE_SETTINGS']['height'] ?>px;"></textarea>
                        <?}
                    }elseif ($arProp["PROPERTY_TYPE"] == "F" ){
                        ?>
                        <input type="file" id="fileselect" name="fileselect[]" multiple="multiple"/>

                        <div class="filedrag-wrap">
                            <div id="fileinput-item"></div>
                            <div id="filedrag">
                                <?= Loc::getMessage("FORMS_ADD_PHOTO") ?>
                            </div>
                        </div>
                        <script>
                            (function () {

                                function $id(id) {
                                    return document.getElementById(id);
                                }

                                function FileDragHover(e) {
                                    e.stopPropagation();
                                    e.preventDefault();
                                    e.target.className = (e.type == "dragover" ? "hover" : "");
                                }

                                function FileSelectHandler(e) {
                                    countImg=getCookie("count");
                                    FileDragHover(e);
                                    var files = e.target.files || e.dataTransfer.files;

                                    form = new FormData();

                                    if(countImg<=5) {

                                        for (var i = 0, f; f = files[i]; i++) {
                                            console.log(countImg);
                                            countImg++;
                                            if(countImg>5){
                                                alert(" <?= Loc::getMessage('FORMS_WARNING_MORE_5') ?>");
                                                countImg--;
                                                break;
                                            }else {
                                                var upload_file = files[i];
                                                form.append("fil" + i, upload_file);
                                            }
                                        }
                                        $.ajax({
                                            url: "/bitrix/components/altop/forms/script2.php",
                                            type: "post",
                                            data: form,
                                            processData: false,
                                            contentType: false,
                                            cache: false,
                                            success: function (data) {
                                                var sp = $id("fileinput-item");
                                               $(sp).append(data);
                                                delete form;
                                            },
                                            error: function (error) {
                                                console.log(error);
                                            }
                                        });
                                    }else{
                                        alert(" <?= Loc::getMessage('FORMS_WARNING_MORE_5') ?>");
                                    }
                                    setCookie("count", countImg, 10000);
                                }


                                $('#filedrag').on('click', function() {
                                    $('#fileselect').trigger('click');
                                });

                                function Init() {

                                    var fileselect = $id("fileselect"),
                                        filedrag = $id("filedrag");

                                    fileselect.addEventListener("change", FileSelectHandler, false);

                                    var xhr = new XMLHttpRequest();
                                    if (xhr.upload) {

                                        // file drop
                                        filedrag.addEventListener("dragover", FileDragHover, false);
                                        filedrag.addEventListener("dragleave", FileDragHover, false);
                                        filedrag.addEventListener("drop", FileSelectHandler, false);
                                        filedrag.style.display = "block";
                                    }
                                }

                                if (window.File && window.FileList && window.FileReader) {
                                    Init();
                                }
                            })();
                        </script>
                    <?}?>
                </div>
            </div>
         <?}
        if ($arParams["USE_CAPTCHA"] == "Y"):?>
            <div class="row">
                <div class="span1"><?= Loc::getMessage("FORMS_CAPTCHA") ?><span class="mf-req">*</span></div>
                <div class="span2">
                    <input type="text" name="CAPTCHA_WORD" maxlength="5" value=""/>
                    <img src="" width="127" height="30" alt="CAPTCHA" style="display:none;"/>
                    <input type="hidden" name="CAPTCHA_SID" value=""/>
                </div>
            </div>
        <? endif; ?>
        <input type="hidden" name="PARAMS_STRING" value="<?= $arParams['PARAMS_STRING'] ?>"/>
        <input type="hidden" name="IBLOCK_STRING" value="<?= $arResult['IBLOCK']['STRING'] ?>"/>
        <? //AGREEMENT//
        if ($arParams["SHOW_PERSONAL_DATA"] == "Y") { ?>
            <div class="hint_agreement">
                <input type="hidden" name="PERSONAL_DATA" id="PERSONAL_DATA_<?= $arResult['ELEMENT_AREA_ID'] ?>"
                       value="N">
                <div class="checkbox">
                    <span class="input-checkbox" id="input-checkbox_<?= $arResult['ELEMENT_AREA_ID'] ?>"></span>
                </div>
                <div class="label">
                    <?= $arParams["TEXT_PERSONAL_DATA"] ?>
                </div>
            </div>
        <? } ?>
        <div class="submit">
            <button type="button" id="<?= $arResult['ELEMENT_AREA_ID'] ?>_btn"
                    class="btn_buy popdef"><?= Loc::getMessage("FORMS_SEND") ?></button>
        </div>
    </form>
</div>
<script type="text/javascript">

    //FORM_SUBMIT//
    BX.bind(BX("<?=$arResult['ELEMENT_AREA_ID']?>_btn"), "click", BX.delegate(BX.FormSubmit, BX));

    //CHEKED//
    BX.bind(BX("input-checkbox_<?=$arResult['ELEMENT_AREA_ID']?>"), "click", function () {
        if (!BX.hasClass(BX("input-checkbox_<?=$arResult['ELEMENT_AREA_ID']?>"), "cheked")) {
            BX.addClass(BX("input-checkbox_<?=$arResult['ELEMENT_AREA_ID']?>"), "cheked");
            BX.adjust(BX("input-checkbox_<?=$arResult['ELEMENT_AREA_ID']?>"), {
                children: [
                    BX.create("i", {
                        props: {
                            className: "fa fa-check"
                        }
                    })
                ]
            });
            BX.adjust(BX("PERSONAL_DATA_<?=$arResult['ELEMENT_AREA_ID']?>"), {
                props: {
                    "value": "Y"
                }
            });
        } else {
            BX.removeClass(BX("input-checkbox_<?=$arResult['ELEMENT_AREA_ID']?>"), "cheked");
            BX.remove(BX.findChild(BX("input-checkbox_<?=$arResult['ELEMENT_AREA_ID']?>"), {
                className: "fa fa-check"
            }));
            BX.adjust(BX("PERSONAL_DATA_<?=$arResult['ELEMENT_AREA_ID']?>"), {
                props: {
                    "value": "N"
                }
            });
        }
    });


</script>