BX.ready(()=>{
    BX.addCustomEvent("BX.UI.EntityConfigurationManager:onInitialize", function(entityEditor,data) {
        if (entityEditor.hasOwnProperty('_entityId') && entityEditor._entityId!=0 && entityEditor._entityTypeName=='USER') return renderBtn(entityEditor._entityId);
    });
});

function renderBtn(userId){
    let placementForInsert = document.querySelector('.profile-menu-mode .ui-side-panel-wrap-title-inner-container');

    let btn = new BX.UI.Button({
        id:"policy-get",
        text:'Получить полис',
        className:"ui-btn ui-btn-light-border ui-btn-themes",
        dataset: {userId:userId},
        onclick:()=> getPolicy(userId)
    });
    placementForInsert.append(btn.getContainer());
}

function getPolicy(userId){
    BX.ajax.runAction('sergei:policy.api.policycontroller.getpolicy',{data:{userId:userId}}).then(
        function(success){
            var a = document.createElement('a');
            a.href = success.data.path;
            a.setAttribute('download', success.data.path);
            a.style.display = 'none';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        },
        function(fail){
            fail.errors.forEach(element => {
                BX.UI.Notification.Center.notify({
                    content: element.message,
                }); 
            });
        }
    );
}