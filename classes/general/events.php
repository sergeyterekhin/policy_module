<?php
\Bitrix\Main\Loader::includeModule('crm');
class SergeiPolicyEvents {
    public static function includeJS(){
        $exts = array(
            "sergei.policy.btnusercard",
        );
        \Bitrix\Main\Ui\Extension::load($exts);
    }
}