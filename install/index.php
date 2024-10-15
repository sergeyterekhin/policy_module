<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class sergei_policy extends CModule{
    public $MODULE_ID = "sergei.policy";
    public $errors ="";
    static $events = array(
        array(
            "FROM_MODULE" => "main",
            "FROM_EVENT" => "OnEpilog",
            "TO_CLASS" => "SergeiPolicyEvents",
            "TO_FUNCTION" => "includeJS",
            "VERSION" => "1"
        ),
    );

    static $userFields=array(
        array(
            'ENTITY_ID' => 'USER',
            'FIELD_NAME' => 'UF_PASSPORT_FIELD',
            'USER_TYPE_ID' => 'string',
            'MULTIPLE' => 'N',
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'I',
            'SHOW_IN_LIST'=>'Y',
            'EDIT_FORM_LABEL'   => array('ru' => 'Паспорт'),
            'LIST_COLUMN_LABEL' => array('ru' => 'Паспорт'),
            'LIST_FILTER_LABEL' => array('ru' => 'Паспорт')
        )
    );

    public function __construct() {
        $this->MODULE_NAME=Loc::getMessage("SP_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("SP_MODULE_DESCRIPTION");
        $this->PARTNER_NAME = Loc::getMessage("SP_MODULE_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("SP_MODULE_PARTNER_URI");

        $arModuleVersion = array();

        include __DIR__ . "/version.php";

        if (
            is_array($arModuleVersion)
            && array_key_exists("VERSION", $arModuleVersion)
            && array_key_exists("VERSION_DATE", $arModuleVersion)
        ) {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        }
    }

    public function DoInstall()
    {
        /**
         * @var \CMain $APPLICATION
         */
        global $APPLICATION, $USER, $DB, $step;
        try{
            $this->InstallFiles();
            $this->InstallEvents();
            $this->InstallRest();
            $this->InstallUserField();
            \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
        } catch (\Exception $e){
            $APPLICATION->ThrowException($e->getMessage());
            return false;
        }
        return true;
    }

    public function DoUninstall() {
        /**
         * @var \CMain $APPLICATION
         */
        global $APPLICATION;
        try{
            $this->UnInstallFiles();
            $this->UnInstallEvents();
            $this->UnInstallUserFiled();
            $this->UnInstallRest();
            \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);

        } catch (\Exception $e){
            $APPLICATION->ThrowException($e->getMessage());
            return false;
        }
        return true;
    }

    function InstallRest(){
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->registerEventHandlerCompatible(
            "rest", 
            "onRestServiceBuildDescription", 
            $this->MODULE_ID, 
            "MyWebstorInnerPhoneRestService",
            "onRestServiceBuildDescription"
        );
    }

    function UnInstallRest(){
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->unRegisterEventHandler(
            "rest", 
            "onRestServiceBuildDescription", 
            $this->MODULE_ID, 
            "MyWebstorInnerPhoneRestService", 
            "onRestServiceBuildDescription"
        );
    }

    public function InstallUserField(){
        $oUserTypeEntity = new CUserTypeEntity();
        foreach (self::$userFields as $userField) {
           $id=$oUserTypeEntity->Add($userField);
        }
    }

    public function UnInstallUserFiled(){
        $oUserTypeEntity = new CUserTypeEntity();
        foreach (self::$userFields as $userField) {
            $rsData = CUserTypeEntity::GetList(array(),array("ENTITY_ID"=>$userField['ENTITY_ID'], "FIELD_NAME"=>$userField['FIELD_NAME']));
            $userFieldId = $rsData->fetch()['ID'];
            if ($userFieldId==null) continue;
            else $oUserTypeEntity->Delete( $userFieldId );
         }
    }

    public function InstallEvents() {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        foreach (static::$events as $event)
            switch ($event["VERSION"]) {
                case "2":
                    $eventManager->registerEventHandler($event["FROM_MODULE"], $event["FROM_EVENT"], $this->MODULE_ID, $event["TO_CLASS"], $event["TO_FUNCTION"]);
                    break;
                case "1":
                default:
                    $eventManager->registerEventHandlerCompatible($event["FROM_MODULE"], $event["FROM_EVENT"], $this->MODULE_ID, $event["TO_CLASS"], $event["TO_FUNCTION"]);
                    break;
            }

        return true;
    }

    public function UnInstallEvents() {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        foreach (static::$events as $event)
            $eventManager->unRegisterEventHandler($event["FROM_MODULE"], $event["FROM_EVENT"], $this->MODULE_ID, $event["TO_CLASS"], $event["TO_FUNCTION"]);
        return true;
    }

    public function InstallFiles() {
        CopyDirFiles(__DIR__ . "/js", \Bitrix\Main\Application::getDocumentRoot() . "/local/js/", true, true);
        return true;
    }

    public function UnInstallFiles() {
        DeleteDirFilesEx("/local/js/sergei/policy/");
        return true;
    }

}