<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\UI\Extension;
use Awz\Uplock\Access\AccessController;

Loc::loadMessages(__FILE__);
global $APPLICATION;
$module_id = "awz.uplock";
if(!Loader::includeModule($module_id)) return;
Extension::load('ui.sidepanel-content');
$request = Application::getInstance()->getContext()->getRequest();
$APPLICATION->SetTitle(Loc::getMessage('AWZ_UPLOCK_OPT_TITLE'));

if($request->get('IFRAME_TYPE')==='SIDE_SLIDER'){
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
    require_once('lib/access/include/moduleright.php');
    CMain::finalActions();
    die();
}

if(!AccessController::isViewSettings())
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

if(!class_exists('CUpdateClientPartner')){
    require_once(
        Application::getInstance()->getContext()->getServer()->getDocumentRoot()
        .'/bitrix/modules/main/classes/general/update_client_partner.php'
    );
}
$arModules = [];
if(class_exists('CUpdateClientPartner')){
    $strError = '';
    $arModulesTmp = \CUpdateClientPartner::GetCurrentModules($strError);
    if(is_array($arModulesTmp)){
        foreach($arModulesTmp as $moduleId=>$moduleData){
            if(trim($moduleId) && is_array($moduleData) && isset($moduleData['VERSION']))
                $arModules[$moduleId] = $moduleData;
        }
    }
}
foreach(glob(Application::getInstance()->getContext()->getServer()->getDocumentRoot().'/bitrix/modules/*/install/version.php') as $path){
    $pathAr = explode('/',$path);
    $moduleId = $pathAr[count($pathAr)-3];
    $arModules[$moduleId] = $arModules[$moduleId] ?? ['VERSION'=>'-'];
}

if ($request->getRequestMethod()==='POST' && AccessController::isEditSettings() && $request->get('Update'))
{
    $LOCKED_str = $request->get('LOCKED');
    $LOCKED_str = preg_replace('/(\t|\n|,)/is', ' ', $LOCKED_str);
    $LOCKED_modules = [];
    foreach(explode(" ", $LOCKED_str) as $moduleVariant){
        $moduleVariant = trim($moduleVariant);
        if($moduleVariant && strpos($moduleVariant, '.')!==false){
            if(!in_array($moduleVariant, $LOCKED_modules))
                $LOCKED_modules[] = $moduleVariant;
        }
    }
    $LOCKED = $request->get('MODULES');
    if(!is_array($LOCKED)) $LOCKED = [];
    $MSGS = $request->get('MODULES_MSG');
    if(!is_array($MSGS)) $MSGS = [];

    $opts = [];
    foreach($LOCKED as $moduleId=>$val){
        if($val=='Y'){
            $opts[$moduleId] = ['lock'=>'Y','msg'=>''];
        }
    }
    foreach($MSGS as $moduleId=>$val){
        if(!$val) continue;
        $opts[$moduleId] = $opts[$moduleId] ?? ['lock'=>'N','msg'=>''];
        $opts[$moduleId]['msg'] = $val;
    }
    Option::set($module_id, "MODULES", serialize($opts), "");
    Option::set($module_id, "LOCKED", implode(",", $LOCKED_modules), "");
    Option::set($module_id, "DSBL", $request->get('DSBL')=='Y' ? 'Y' : 'N', "");
}

$aTabs = array();

$aTabs[] = array(
    "DIV" => "edit1",
    "TAB" => Loc::getMessage('AWZ_UPLOCK_OPT_SECT1'),
    "ICON" => "vote_settings",
    "TITLE" => Loc::getMessage('AWZ_UPLOCK_OPT_SECT1')
);

$saveUrl = $APPLICATION->GetCurPage(false).'?mid='.htmlspecialcharsbx($module_id).'&lang='.LANGUAGE_ID.'&mid_menu=1';
$tabControl = new CAdminTabControl("tabControl", $aTabs);
$tabControl->Begin();
?>
    <style>.adm-workarea option:checked {background-color: rgb(206, 206, 206);}</style>
    <form method="POST" action="<?=$saveUrl?>" id="FORMACTION">
        <?
        $tabControl->BeginNextTab();
        Extension::load("ui.alerts");
        ?>
        <?
        $opts = [];
        try{
            $opts = unserialize(
                    Option::get($module_id, "MODULES", "",""),
                    ['allowed_classes' => false]
            );
        }catch (\Exception $e){
            $opts = [];
        }
        if(!is_array($opts)) $opts = [];
        ?>
        <?
        $style = 'text-align: left!important;border-bottom:1px solid #9ea7b1;padding-top:5px;padding-bottom:5px;';
        ?>

        <tr>
            <td style="padding-bottom:20px;text-align: left;"><?=Loc::getMessage('AWZ_UPLOCK_OPT_MODULE_DSBL')?></td>
            <td style="padding-bottom:20px;text-align: left;">
                <?$val = Option::get($module_id, "DSBL", "N","");?>
                <input type="checkbox" value="Y" name="DSBL" <?if ($val=="Y") echo "checked";?>></td>
            </td>
            <td></td>
            <td></td>
        </tr>

        <tr>
            <td style="padding-bottom:20px;text-align: left;"><?=Loc::getMessage('AWZ_UPLOCK_OPT_MODULE_INSTALL')?></td>
            <td style="padding-bottom:20px;text-align: left;" colspan="3">
                <?$val = Option::get($module_id, "LOCKED", "","");?>
                <textarea name="LOCKED" style="width:calc(100% - 10px);"><?=htmlspecialcharsEx($val)?></textarea>
            </td>
        </tr>

        <tr>
            <th style="<?=$style?>">
                <?=Loc::getMessage('AWZ_UPLOCK_OPT_MODULE')?>
            </th>
            <th style="<?=$style?>">
                <?=Loc::getMessage('AWZ_UPLOCK_OPT_VERSION')?>
            </th>
            <th style="<?=$style?>">
                <?=Loc::getMessage('AWZ_UPLOCK_OPT_LOCK')?>
            </th>
            <th style="<?=$style?>">
                <?=Loc::getMessage('AWZ_UPLOCK_OPT_LOCK_MSG')?>
            </th>
        </tr>

        <?foreach($arModules as $moduleCode=>$moduleData){
            if(strpos($moduleCode,'.')===false) continue;
            $active = $opts[$moduleCode]['lock'] ?? "N";
            $msg = $opts[$moduleCode]['msg'] ?? "";
            ?>
            <tr>
                <td style="<?=$style?>"><?=$moduleCode?></td>
                <td style="<?=$style?>"><?=$moduleData['VERSION']?></td>
                <td style="<?=$style?>">
                    <input type="checkbox" value="Y" name="MODULES[<?=$moduleCode?>]" <?if ($active=="Y") echo "checked";?>></td>
                </td>
                <td style="<?=$style?>">
                    <input type="text" value="<?=htmlspecialcharsEx($msg)?>" name="MODULES_MSG[<?=$moduleCode?>]"></td>
                </td>
            </tr>
        <?}?>


        <?
        $tabControl->Buttons();
        ?>
        <input <?if (!AccessController::isEditSettings()) echo "disabled" ?> type="submit" class="adm-btn-green" name="Update" value="<?=Loc::getMessage('AWZ_UPLOCK_OPT_L_BTN_SAVE')?>" />
        <input type="hidden" name="Update" value="Y" />
        <?if(AccessController::isViewRight()){?>
            <button class="adm-header-btn adm-security-btn" onclick="BX.SidePanel.Instance.open('<?=$saveUrl?>');return false;">
                <?=Loc::getMessage('AWZ_UPLOCK_OPT_SECT2')?>
            </button>
        <?}?>
        <?$tabControl->End();?>
    </form>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");