<?php
IncludeModuleLangFile(__FILE__);

\CModule::AddAutoloadClasses(
    "sergei.policy",
    [
        "Sergei\Policy\Controller\PolicyController" => "lib/controller/PolicyController.php",

        "SergeiPolicyRestService"=>"classes/general/restservice.php",
        'SergeiPolicyEvents' => "classes/general/events.php",
    ]
);
