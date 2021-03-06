{include './common.tpl'}

<div class="fm-container {$version}">
    {include file='./header.tpl' buttons=false}
    <div class="fm-content-wrapper">
        <div class="fm-update-message-container"></div>
        <div class="fm-panel">
            <div class="fm-panel-header text-center">{fi18n s='Authentication'}</div>
            <div class="fm-panel-body text-center">
                <form action="" method="post" class="form-horizontal fm-form authenticate">
                    <fieldset>
                        <div class="form-group">
                            <label for="fm-auth-username">{fi18n s='Username'}</label>
                            <input type="text" name="username" id="fm-auth-username">
                        </div>

                        <div class="form-group">
                            <label for="fm-auth-api-token">{fi18n s='API Token'}</label>
                            <input type="text" name="api_token" id="fm-auth-api-token">
                        </div>

                        <div class="form-group">
                            <label for="fm-import-orders-disabled">{fi18n s='Disable order import from Fyndiq'}</label>
                            <input type="checkbox" name="import_orders_disabled" value="1" id="fm-import-orders-disabled">
                        </div>

                        <p>
                            {fi18n s='By authenticating you will create a permanent connection to your Fyndiq merchant account.'}<br>
                            {fi18n s='You will not have to authenticate again when coming here next time.'}<br>
                        </p>
                        <button class="fm-button fyndiq green" type="submit" name="submit_authenticate">{fi18n s='Authenticate'}</button>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
    <div class="fm-content-wrapper fm-footer muted text-right">
        <img class="fm-update-check" style="display:none" src="{$shared_path}frontend/images/update-loader.gif" />
        {$module_version}
    </div>
</div>
