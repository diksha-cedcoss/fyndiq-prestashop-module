
{literal}

<script type="text/x-handlebars-template" class="handlebars-template" id="fm-loading-overlay">
<div class="fm-loading-overlay">
    <img src="{{module_path}}backoffice/frontend/images/ajax-loader.gif" alt="Loading animation">
</div>
</script>

<script type="text/x-handlebars-template" class="handlebars-template" id="fm-message-overlay">
<div class="fm-message-overlay fm-{{type}}">
    <img class="close" src="{{module_path}}backoffice/frontend/images/close-icon.png" alt="Close" title="Close message">
    <h3>{{title}}</h3>
    <p>{{message}}</p>
</div>
</script>

<script type="text/x-handlebars-template" class="handlebars-template" id="fm-modal-overlay">
<div class="fm-modal-overlay">
    <div class="container">
        <div class="content"></div>
        <div class="controls">
            {{#each buttons}}
                <button class="{{type}}" data-modal_type="{{type}}">{{label}}</button>
            {{/each}}
        </div>
    </div>
</div>
</script>

<script type="text/x-handlebars-template" class="handlebars-template" id="fm-accept-product-export">
<div class="fm-accept-product-export">
    <h3>Warning!</h3>
    <p>
        Some of the products that you selected have combinations with a different price than the product they belong to.<br>
        Fyndiq does not support different prices on different articles, Fyndiq supports only one price per product and all of its articles.<br>
        If you choose to proceed, we will set one common price for all your combinations.<br>
        We have calculated the recommended price for each product, which you can see below.<br>
        You may choose to alter these values now, before pressing the Accept and Export button.<br>
        Or you may choose to press Cancel to go back and alter your selection of products and combinations.
    </p>

    <ul>
    {{#each product_warnings}}
        <li>
            {{#with product}}
                {{#with product}}
                    <div class="image">
                        {{#if image}}
                            <img src="{{image}}" alt="Product image">
                        {{/if}}
                    </div>
                    <div class="name">
                        {{name}}
                    </div>
                {{/with}}
            {{/with}}

            <div class="highest-price">
                Highest: {{highest_price}}
            </div>
            <div class="lowest-price">
                Lowest: {{lowest_price}}
            </div>
        </li>
    {{/each}}
    </ul>
</div>
</script>

<script type="text/x-handlebars-template" class="handlebars-template" id="fm-category-tree">
<ul class="fm-category-tree">
    {{#each categories}}
        {{#with this}}
            <li data-category_id="{{id}}">
                {{level}}
                <a href="#">{{name}}</a>
            </li>
        {{/with}}
    {{/each}}
</ul>
</script>

<script type="text/x-handlebars-template" class="handlebars-partial" id="fm-product-list-controls">
    <div class="fm-product-list-controls">
        <form class="fm-form select">
            <input class="submit" type="submit" name="select-all" value="Select all">
            <input class="submit" type="submit" name="deselect-all" value="Deselect all">
        </form>
        <form class="fm-form submit-buttons">
            <input class="submit important-action" type="submit" name="submit_export" value="Export products">
        </form>
    </div>
</script>

<script type="text/x-handlebars-template" class="handlebars-template" id="fm-product-list">
{{> fm-product-list-controls}}
{{#if products}}
    <ul class="fm-product-list">
        {{#each products}}
            {{#with this}}
            <li
                data-id="{{id}}"
                data-name="{{name}}"
                data-reference="{{reference}}"
                data-price="{{price}}"
                data-quantity="{{quantity}}"
                data-image="{{image}}"
            >
                <div class="product">
                    <div class="title">
                        <label for="select_product_{{id}}">
                            <h4>{{name}} <span class="reference">({{reference}})</span></h4>
                        </label>
                    </div>

                    <div class="select">
                        <input type="checkbox" id="select_product_{{id}}">
                    </div>

                    <div class="image">
                        {{#if image}}
                        <label for="select_product_{{id}}">
                            <img src="{{image}}" alt="Product image">
                        </label>
                        {{/if}}
                    </div>

                    <div class="prices">
                        <div class="price">
                            <label>Price:</label>
                            <input type="text" value="{{price}}">
                        </div>
                        <div class="price">
                            <label>Fyndiq Price:</label>
                            <input type="text">
                        </div>
                    </div>

                    <div class="quantities">
                        <div>Qty: {{quantity}}</div>
                        <div>Exported Qty: 6</div>
                        <div>Fyndiq Qty: 4</div>
                    </div>

                    <div class="expand
                        {{#unless combinations}}
                            inactive
                        {{/unless}}
                        ">
                        <a href="#">
                            <img src="{{../../module_path}}backoffice/frontend/images/down-arrow.png"
                                alt="Down pointing arrow"
                                {{#if combinations}}
                                    title="Show combinations"
                                {{else}}
                                    title="Product does not have any combinations"
                                {{/if}}
                                >
                        </a>
                    </div>
                </div>
                {{#if combinations}}
                    <ul class="combinations">
                    {{#each combinations}}
                        <li
                            data-id="{{id}}"
                            data-price="{{price}}"
                            data-quantity="{{quantity}}"
                        >
                            <div class="select">
                                <input type="checkbox" class="checkbox" id="select_combination_{{id}}">
                            </div>

                            <div class="image">
                                {{#if image}}
                                <label for="select_combination_{{id}}">
                                    <img src="{{image}}" alt="Product combination image">
                                </label>
                                {{/if}}
                            </div>

                            <div class="attributes">
                                {{#each attributes}}
                                    {{name}} - {{value}},
                                {{/each}}
                            </div>

                            <div>
                                Price: {{price}}
                                Qty: {{quantity}}
                            </div>
                        </li>
                    {{/each}}
                    </ul>
                {{/if}}
            </li>
            {{/with}}
        {{/each}}
    </ul>
{{else}}
    Category is empty.
{{/if}}
{{> fm-product-list-controls}}
</script>

{/literal}
