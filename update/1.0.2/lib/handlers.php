<?php
namespace Awz\Uplock;

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

class Handlers {

    const MODULE_ID = 'awz.uplock';

    public static function onPageStart()
    {
        if(Option::get(static::MODULE_ID, 'DSBL', 'N', '')=='Y')
            return;

        $request = Application::getInstance()->getContext()->getRequest();
        $curPage = $request->getRequestUri();
        if(strpos($curPage, '/bitrix/admin/update_system_partner_call.php')!==false)
        {
            if(!class_exists('CUpdateClientPartner')){
                require_once(
                    Application::getInstance()->getContext()->getServer()->getDocumentRoot()
                    .'/bitrix/modules/main/classes/general/update_client_partner.php'
                );
            }

            $opts = [];
            try{
                $opts = unserialize(
                    Option::get(static::MODULE_ID, "MODULES", "",""),
                    ['allowed_classes' => false]
                );
            }catch (\Exception $e){
                $opts = [];
            }
            if(!is_array($opts)) $opts = [];
            $checkLocked = false;
            foreach($opts as $data){
                if($data['lock']=='Y'){
                    $checkLocked = true;
                    break;
                }
            }

            $arRequestedModules = explode(",", $request->get('reqm'));
            if(empty($arRequestedModules)){
                $arRequestedModules = \CUpdateClientPartner::GetRequestedModules($request->get('addmodule'));
            }
            $tmp = [];
            foreach($arRequestedModules as $module){
                if($module) $tmp[] = $module;
            }
            $arRequestedModules = $tmp;
            unset($tmp);

            if(empty($arRequestedModules) && $checkLocked){
                echo "ERR<b style=\"color:red;\">".Loc::getMessage('AWZ_UPLOCK_HANDLERS_MSG_NOTALL').'</b>';
                \Bitrix\Main\Application::getInstance()->end();
                die();
            }

            $notInstall = explode(",", Option::get(static::MODULE_ID, "LOCKED", "", ""));
            foreach($notInstall as $moduleId){
                if($moduleId){
                    $opts[$moduleId] = $opts[$moduleId] ?? ['lock'=>'Y','msg'=>Loc::getMessage('AWZ_UPLOCK_HANDLERS_LOCK_ADD')];
                }
            }

            if(empty($arRequestedModules))
                return;

            foreach($opts as $moduleId=>$data){
                if($data['lock']!='Y') continue;
                $msg = $data['msg'];
                if(in_array($moduleId, $arRequestedModules)){
                    $errorMessage = Loc::getMessage('AWZ_UPLOCK_HANDLERS_MSG',['#MODULE_ID#'=>$moduleId])
                        .' '.$msg;
                    echo "ERR".$errorMessage;
                    \Bitrix\Main\Application::getInstance()->end();
                    die();
                }
            }
        }
    }

}