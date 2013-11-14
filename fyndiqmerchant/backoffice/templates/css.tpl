
<style type="text/css">

#fm-container {
    position: relative;
    width: 920px;
    margin: 0 auto;
    overflow: hidden;
}

#fm-logo {
    display: block;
    margin: 0 auto;
    width: 200px;
}


.fm-form {
    margin: 20px 0;
}

.fm-form .submit {
    border: 1px solid gray;
    padding: 3px;
    font-size: 12px;
}

.fm-form label {
    text-align: left;
}

.fm-form div {
    overflow: hidden;
}


.fm-loading-overlay {

    /* http://robertnyman.com/2010/01/11/css-background-transparency-without-affecting-child-elements-through-rgba-and-filters/ */
    background: rgb(0, 0, 0);
    background: rgba(0, 0, 0, 0.8);
    -ms-filter: "progid:DXImageTransform.Microsoft.gradient(startColorstr=#CC000000, endColorstr=#CC000000)";
    filter:~"progid:DXImageTransform.Microsoft.gradient(startColorstr=#CC000000, endColorstr=#CC000000)";

    position: absolute;
    width: 100%;
    height: 100%;
    z-index: 999;
}

.fm-loading-overlay img {
    width: 128px;
    height: 128px;
    display: block;
    margin: 150px auto;
}


#fm-main-panel {
    float: left;
    width: 620px;
}

#fm-sidebar {
    float: left;
    width: 285px;
    margin-left: 15px;
}


.fm-form.products .submit-container {
    overflow: hidden;
}
.fm-form.products .submit-container .submit {
    float: right;
}

.fm-product-list-container {
    clear: both;
    background-color: #e5e5e5;
    padding: 2px;
    margin: 10px 0;
}

.fm-category-tree {
    margin: 0;
    padding: 0;
}
.fm-category-tree a.active {
    font-weight: bold;
}

.fm-product-list {
    margin: 0;
    padding: 0;
}
.fm-product-list > li {
    overflow: hidden;
    background-color: #f5f5f5;
    margin-bottom: 3px;
    padding: 3px;
}
.fm-product-list li:last-child {
    margin-bottom: 0;
}

.fm-product-list .product {
    position: relative;
}

.fm-product-list .product .title {
    font-size: 14px;
    margin: 0 0 5px 0;
}

.fm-product-list .product .title .reference {
    font-size: 13px;
    font-weight: normal;
}

.fm-product-list .product .image {
    float: left;
    width: 50px;
    height: 50px;
}

.fm-product-list .product .prices {
    float: left;
    margin-left: 10px;
}

.fm-product-list .product .quantities {
    float: left;
    margin-left: 20px;
}

.fm-product-list .product .image img {
    display: block;
    margin: 0 auto;
    max-width: 50px;
    max-height: 50px;
}

.fm-product-list .product .prices .price {
    margin-bottom: 2px;
}

.fm-product-list .product .prices label {
    display: block;
    width: 90px;
    text-align: right;
    float: left;
}

.fm-product-list .product .prices input {
    display: block;
    float: left;
    width: 100px;
}

.fm-product-list .product .expand {
    position: absolute;
    right: 0;
    bottom: 0;
    width: 28px;
    height: 28px;
}
.fm-product-list .product .expand img {
    display: block;
    width: 28px;
    height: 28px;
}
.fm-product-list .product .expand.inactive {
    opacity: 0.2;
    filter: alpha(opacity=20);
}

.fm-product-list .combinations {
    padding-left: 8px;
    margin-top: 3px;
}

.fm-product-list .combinations > li {
    overflow: hidden;
    margin-bottom: 1px;
    padding: 1px;
    background: #e5e5e5;
}

.fm-product-list .combinations .image {
    width: 30px;
    height: 30px;
    float: left;
}
.fm-product-list .combinations .image img {
    display: block;
    margin: 0 auto;
    max-width: 30px;
    max-height: 30px;
}

</style>
