<?php 
namespace Sergei\Policy\Controller;
use \Bitrix\Main\Error;
use CUser;
use SoapClient;

\CModule::IncludeModule('webservice');

class PolicyController extends \Bitrix\Main\Engine\Controller{
    
    public const BadRequest = 400;
    public const OK = 200;

    public function getPolicyAction()
    {
        $result=[];
        $request = $this->getRequest();
        $userId=$request->get('userId');
        if ($userId==null || $userId<1) {
            $this->addError(new Error('Не передан ID пользователя', self::BadRequest));
            return null;
        }

        $user=CUser::GetByID($userId)->fetch();
        
        if ($user['UF_PASSPORT_FIELD']==null || $user['UF_PASSPORT_FIELD']=='' || preg_match('/^\d{4}\s?\d{6}$/', $user['UF_PASSPORT_FIELD'])!=1) 
            $this->addError(new Error('У пользователя не заполнено или некорректно поле "паспорт"', self::BadRequest));
        if ($user['PERSONAL_MOBILE']==null || $user['PERSONAL_MOBILE']=='' || $this->formatToPhoneNumber($user['PERSONAL_MOBILE'])===false)
            $this->addError(new Error('У пользователя не заполнено или некоректно поле "Мобильный телефон"', self::BadRequest));
        if ($user['PERSONAL_BIRTHDAY']==null || $user['PERSONAL_BIRTHDAY']=='') 
            $this->addError(new Error('У пользователя не заполнено поле "День рождения"', self::BadRequest));
        if ($user['EMAIL']==null || $user['EMAIL']=='') 
            $this->addError(new Error('У пользователя не заполнено поле "Контактный Email"', self::BadRequest));
        if (($user['NAME']==null || $user['NAME']=='') || ($user['LAST_NAME']==null || $user['LAST_NAME']=='')) 
            $this->addError(new Error('У пользователя не заполнена базовая информация', self::BadRequest));

        if (count($this->errorCollection)>0) return null;

        $client = new \SoapClient('https://soapdev.d2insur.ru/pay/PolicyPay.wsdl', array('login' => 'testForUser', 'password' => 'testUser520'));
        $response = $client->obtainCertificate(array(
            'applicationId' => rand(10000000, 99999999),
            'productId' => 3523309775,
            'person' =>array(
                'INSURER_FIRSTNAME' => $user['NAME'],
                'INSURER_LASTNAME' => $user['SECOND_NAME'],
                'INSURER_SURNAME' => $user['LAST_NAME'],
                'INSURER_EMAIL' => $user['EMAIL'],
                'INSURER_BIRTHDAY' => date('d.m.Y', strtotime($user['PERSONAL_BIRTHDAY'])),
                'PASSPORT_NUMBER' => $user['UF_PASSPORT_FIELD'],
                'INSURER_PHONE' => $this->formatToPhoneNumber($user['PERSONAL_MOBILE'])
            )
        ));
        
        if ($response->result->code == 'OK') {
            $result=[
                'certSeries' => $response->cert->series,
                'certNumber' => $response->cert->number,
                'certFile' => base64_decode($response->cert->certFile),
            ];
        } else {
            $this->addError(new Error($response->result->errorDescr, self::BadRequest));
        }
        
        if (count($this->errorCollection)>0) return null;
        
        $rute ="/upload/".$result['certSeries'].'-'.$result['certNumber'].".pdf";
        file_put_contents($_SERVER["DOCUMENT_ROOT"].$rute, $result['certFile']);

        return ['status' => self::OK, 'path' => $rute];
    }

    private function formatToPhoneNumber($phone)
    {
        $input = preg_replace('/\D/', '', $phone);
        if (preg_match('/^(7|8)\d{10}$/', $input) || preg_match('/^7\d{10}$/', $input) || preg_match('/^8\d{10}$/', $input)) {
            return $input;
        } else {
            return false; // Неверный формат
        }
    }

}
