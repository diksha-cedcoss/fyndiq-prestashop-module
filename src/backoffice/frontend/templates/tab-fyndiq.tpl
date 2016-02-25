<div id="fyndiq-export" class="panel product-tab">
    <h3>Fyndiq</h3>

    <div class="form-group">
        <div class="col-lg-1"><span class="pull-right"></span></div>
        <label class="control-label col-lg-2" for="wholesale_price">
            <span class="label-tooltip" data-toggle="tooltip" title="" data-original-title="The wholesale price is the price you paid for the product. Do not include the tax.">Export to Fyndiq</span>
        </label>
        <div class="col-lg-2">
            <div class="checkbox">
                <label for="fyndiq_exported">
                    <input type="checkbox" name="fyndiq_exported" id="fyndiq_exported" value="1"  {if $fyndiq_exported}checked="checked"{/if} />
                    Export</label>
            </div>
        </div>
    </div>
    <div class="panel-footer">
        <a href="{$link->getAdminLink('AdminProducts')|escape:'html':'UTF-8'}{if isset($smarty.request.page) && $smarty.request.page > 1}&amp;submitFilterproduct={$smarty.request.page|intval}{/if}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel'}</a>
        <button type="submit" name="submitAddproduct" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save'}</button>
        <button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save and stay'}</button>
    </div>
</div>