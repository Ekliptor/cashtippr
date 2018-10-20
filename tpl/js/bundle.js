/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./index.ts");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./index.ts":
/*!******************!*\
  !*** ./index.ts ***!
  \******************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

Object.defineProperty(exports, "__esModule", { value: true });
var CashTippr_1 = __webpack_require__(/*! ./src/CashTippr */ "./src/CashTippr.ts");
var cashtippr = new CashTippr_1.CashTippr(window, jQuery);
window.cashtippr = cashtippr;


/***/ }),

/***/ "./src/BlurryImage.ts":
/*!****************************!*\
  !*** ./src/BlurryImage.ts ***!
  \****************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

Object.defineProperty(exports, "__esModule", { value: true });
/**
 * The class for displaying blurry images.
 * We bundle it together with all our JavaScript instead of with the addon because
 * a 2nd JS WebPack project would create unnecessary dependency + bootstrap overhead.
 */
var BlurryImage = /** @class */ (function () {
    function BlurryImage(cashtippr) {
        this.cashtippr = cashtippr;
        // TODO implement events this class (and other addons) can listen to so that we don't have to call functions in here directly
    }
    return BlurryImage;
}());
exports.BlurryImage = BlurryImage;


/***/ }),

/***/ "./src/CashTippr.ts":
/*!**************************!*\
  !*** ./src/CashTippr.ts ***!
  \**************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

Object.defineProperty(exports, "__esModule", { value: true });
var Tooltips_1 = __webpack_require__(/*! ./admin/Tooltips */ "./src/admin/Tooltips.ts");
var MoneyButton_1 = __webpack_require__(/*! ./MoneyButton */ "./src/MoneyButton.ts");
var WebHelpers_1 = __webpack_require__(/*! ./WebHelpers */ "./src/WebHelpers.ts");
var BlurryImage_1 = __webpack_require__(/*! ./BlurryImage */ "./src/BlurryImage.ts");
var Shout_1 = __webpack_require__(/*! ./Shout */ "./src/Shout.ts");
var QrCode_1 = __webpack_require__(/*! ./QrCode */ "./src/QrCode.ts");
var CashTippr = /** @class */ (function () {
    function CashTippr(window, $) {
        var _this = this;
        this.tooltips = new Tooltips_1.Tooltips(this);
        this.window = window;
        this.$ = $;
        this.config = this.window['cashtipprCfg'] || {};
        this.config.consentCookieName = CashTippr.CONSENT_COOKIE_NAME;
        this.config.confirmCookiesMsg = CashTippr.CONFIRM_COOKIES_MSG;
        this.config.confirmCookiesBtn = CashTippr.CONFIRM_COOKIES_BTN;
        this.webHelpers = new WebHelpers_1.WebHelpers(this.window, this.$, this.config);
        this.mb = new MoneyButton_1.MoneyButton(this, this.webHelpers, true);
        this.qr = new QrCode_1.QrCode(this, this.webHelpers);
        this.blurryImage = new BlurryImage_1.BlurryImage(this);
        this.shout = new Shout_1.Shout(this);
        this.$(this.window.document).ready(function ($) {
            _this.tooltips.initToolTips();
            _this.webHelpers.checkCookieConsent();
        });
    }
    CashTippr.prototype.getConfig = function () {
        return this.config;
    };
    CashTippr.prototype.getWebHelpers = function () {
        return this.webHelpers;
    };
    CashTippr.CONSENT_COOKIE_NAME = "ct-ck";
    CashTippr.CONFIRM_COOKIES_MSG = "#ct-cookieMsg";
    CashTippr.CONFIRM_COOKIES_BTN = "#ct-confirmCookies";
    return CashTippr;
}());
exports.CashTippr = CashTippr;


/***/ }),

/***/ "./src/MoneyButton.ts":
/*!****************************!*\
  !*** ./src/MoneyButton.ts ***!
  \****************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

Object.defineProperty(exports, "__esModule", { value: true });
var MoneyButton = /** @class */ (function () {
    function MoneyButton(cashtippr, webHelpers, globalCallbacks) {
        if (globalCallbacks === void 0) { globalCallbacks = false; }
        var _this = this;
        this.scheduleUpdateAmountTimerID = 0;
        this.cashtippr = cashtippr;
        this.webHelpers = webHelpers;
        this.globalCallbacks = globalCallbacks;
        if (this.globalCallbacks === true) {
            setTimeout(function () {
                _this.cashtippr.window['onMoneyButtonPayment'] = _this.onMoneyButtonPayment;
                _this.cashtippr.window['onMoneyButtonError'] = _this.onMoneyButtonError;
                _this.cashtippr.window['onMoneyButtonClientPayment'] = _this.sendPaymentReceived;
            }, 0);
        }
        this.cashtippr.$(this.cashtippr.window.document).ready(function ($) {
            _this.cashtippr.$(".ct-input-amount").keyup(function (event) {
                _this.scheduleUpdateAmount(event.target);
            });
            _this.cashtippr.$(".ct-input-amount").change(function (event) {
                _this.scheduleUpdateAmount(event.target);
            });
        });
    }
    MoneyButton.prototype.onMoneyButtonPayment = function (payment) {
        var _this = this;
        if (typeof payment.buttonData === "string" && payment.buttonData.length !== 0 && payment.buttonData[0] !== "{")
            payment.buttonDataObj = JSON.parse(this.cashtippr.getWebHelpers().fromBase64(payment.buttonData)) || {}; // this.webHelpers is undefined
        if (payment.domID === undefined)
            payment.domID = "ct-btn-wrap-" + payment.buttonId;
        //console.log("MB payment", payment)
        // buttonDataObj.days === 0
        // TODO make an ajax call to WP to check if the payment has actually been received (once they support webhooks)
        // then depending on if the content is hidden with CSS: modify style or reload page (server can tell this, or we add a variable)
        // simply always remove the style
        if (this.cashtippr.getConfig().show_search_engines === true) {
            //this.cashtippr.$(".ct-hidden-text").css("cssText", "display: inherit!important;");
            // especially with editable + hidden button on same page
            if (!payment.buttonDataObj || payment.buttonDataObj.days !== 0) { // show everything for full passes
                this.cashtippr.$(".ct-hidden-text").fadeIn("slow");
                this.cashtippr.$(".ct-more, .ct-button-text, .ct-image-blurry").fadeOut("slow");
            }
            else { // only show the contents hidden in this button
                this.cashtippr.$("#ct-hidden-" + payment.buttonId).fadeIn("slow");
                this.cashtippr.$("#ct-button-text-" + payment.buttonId + ", #ct-image-blurry-" + payment.buttonId).fadeOut("slow");
                if (payment.buttonDataObj.postHide === true) // auto hide of the full post. there can only be 1 such element
                    this.cashtippr.$(".ct-more").fadeOut("slow");
            }
            //this.sendPaymentReceived(payment);
            this.cashtippr.window['onMoneyButtonClientPayment'].call(this.cashtippr.mb, payment, function (res) {
                if (payment.buttonDataObj && payment.buttonDataObj.shout === true)
                    _this.cashtippr.shout.onPayment(payment);
            });
            // TODO increment tips received and donation goal progress with JS
            if (typeof this.cashtippr.window['onCashtipprPayment'] === "function")
                this.cashtippr.window['onCashtipprPayment'].call(this.cashtippr.window, { moneybutton: payment });
            return;
        }
        this.cashtippr.window['onMoneyButtonClientPayment'].call(this.cashtippr.mb, payment, function (res) {
            if (payment.buttonDataObj && payment.buttonDataObj.shout === true) {
                _this.cashtippr.shout.onPayment(payment);
                return; // this will already reload the page
            }
            // TODO add random query param to bypass caching? look at different WP caching plugins first
            // some might ignore query strings
            // better way would be to implement WP hooks to bypass cache for paid users (if provided by cache plugins)
            // to make it work with full-page caching we have to fetch params such as TXID via ajax either way
            _this.cashtippr.window.location.reload(true);
        });
    };
    MoneyButton.prototype.onMoneyButtonError = function (error) {
        this.cashtippr.window.console.error("MoneyButton payment error", error);
    };
    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################
    MoneyButton.prototype.sendPaymentReceived = function (payment, callback) {
        var params = {
            txid: payment.buttonId,
            am: payment.amount
        };
        this.webHelpers.getApi("/wp-json/cashtippr/v1/mb-client", params, function (data) {
            callback && callback(data);
        });
    };
    MoneyButton.prototype.scheduleUpdateAmount = function (target) {
        var _this = this;
        if (this.scheduleUpdateAmountTimerID !== 0)
            clearTimeout(this.scheduleUpdateAmountTimerID);
        this.scheduleUpdateAmountTimerID = setTimeout(function () {
            _this.updateButtonAmount(target);
        }, MoneyButton.UPDATE_AMOUNT_DELAY_MS);
    };
    MoneyButton.prototype.updateButtonAmount = function (target) {
        var btnContainer = this.cashtippr.$(target).parent().parent();
        var btnFrame = btnContainer.find("iframe");
        if (!btnFrame) {
            console.error("Unable to find MoneyButton iframe at element", btnContainer);
            return;
        }
        var src = btnFrame.attr("src");
        if (src.indexOf("&amt=") === -1 || src.indexOf("&lbl=") === -1) {
            console.error("Unable to find MoneyButton amount in iframe src. Please report this, as most likely their API has changed. src=%s", src);
            return;
        }
        var amountNew = parseFloat(this.cashtippr.$(target).val());
        if (amountNew === 0.0 || amountNew === Number.NaN)
            return;
        // TODO check if the amount really changed? shouldn't be needed
        src = src.replace(/&amt=[0-9\.]+&/, "&amt=" + amountNew + "&")
            .replace(/&lbl=[0-9\.]+/, "&lbl=" + amountNew);
        btnFrame.attr("src", src);
    };
    MoneyButton.UPDATE_AMOUNT_DELAY_MS = 300; // delay it to prevent flickering if the user updates multiplet imes
    return MoneyButton;
}());
exports.MoneyButton = MoneyButton;


/***/ }),

/***/ "./src/QrCode.ts":
/*!***********************!*\
  !*** ./src/QrCode.ts ***!
  \***********************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

Object.defineProperty(exports, "__esModule", { value: true });
var QrCode = /** @class */ (function () {
    function QrCode(cashtippr, webHelpers) {
        var _this = this;
        this.cashtippr = cashtippr;
        this.webHelpers = webHelpers;
        this.cashtippr.$(this.cashtippr.window.document).ready(function ($) {
            _this.cashtippr.$(".ct-qrcode-btn").click(function (event) {
                _this.showQrDialog(event.target);
            });
        });
    }
    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################
    QrCode.prototype.showQrDialog = function (target) {
        var _this = this;
        var paymentButtonlWrapper = this.cashtippr.$(target).parent().parent().parent();
        var txid = paymentButtonlWrapper.attr("id").replace(/^ct-btn-wrap-/, "");
        var paymentCtrlWrapper = paymentButtonlWrapper.parent();
        var amount = 0.0;
        if (paymentCtrlWrapper.find("input[name='amount']").length !== 0)
            amount = paymentCtrlWrapper.find("input[name='amount']").val(); // custom editable amount by user
        var params = {
            txid: txid,
            am: amount
        };
        var dialog = this.webHelpers.translate(this.cashtippr.$("#ct-modal-qrcode-dialog-tpl").html(), {
            dialog_class: "dialog-" + txid
        });
        this.cashtippr.$("body").append(dialog); // its position is fixed relative to the viewport
        this.webHelpers.getApi("/wp-json/cashtippr/v1/qrcode", params, function (data) {
            _this.cashtippr.$(".ct-loading").remove();
            if (data.error === true) {
                _this.cashtippr.window.console.error("Error starting QR code tip");
                _this.cashtippr.window.console.error(data.errorMsg);
                return;
            }
            _this.cashtippr.$(".ct-qr-code").fadeIn("slow").attr("src", data.data[0]);
        });
        this.cashtippr.$(".ct-close-dialog").click(function (event) {
            _this.cashtippr.$(".dialog-" + txid).remove();
        });
        this.cashtippr.$(".ct-copy-field").click(function (event) {
            var inputEl = _this.cashtippr.$(event.target).parent().find("input[type='text']");
            //let text = inputEl.val();
            inputEl.select();
            try {
                _this.cashtippr.window.document.execCommand("copy");
            }
            catch (err) { // try-catch shouldn't be needed except for some really old browsers
            }
            inputEl.select(); // ensure it's selected again for iOS devices to copy it manually. not working, remove?
        });
        this.cashtippr.$("#ct-qrcode-form input[type='text']").click(function (event) {
            _this.cashtippr.$(event.target).select();
        });
        if (this.webHelpers.isAppleIOS() === true) // copy to clipboard button doesn't work there
            this.cashtippr.$("#ct-qrcode-form .ct-copy-field").addClass("hidden");
    };
    return QrCode;
}());
exports.QrCode = QrCode;


/***/ }),

/***/ "./src/Shout.ts":
/*!**********************!*\
  !*** ./src/Shout.ts ***!
  \**********************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

Object.defineProperty(exports, "__esModule", { value: true });
/**
 * The class for displaying the shout addon.
 * We bundle it together with all our JavaScript instead of with the addon because
 * a 2nd JS WebPack project would create unnecessary dependency + bootstrap overhead.
 */
var Shout = /** @class */ (function () {
    function Shout(cashtippr) {
        var _this = this;
        this.scheduleUpdateTimerID = 0;
        this.cashtippr = cashtippr;
        // TODO implement events this class (and other addons) can listen to so that we don't have to call functions in here directly
        this.cashtippr.$(this.cashtippr.window.document).ready(function ($) {
            if (_this.cashtippr.$(".ct-message").length !== 0) {
                _this.updateRemainingChars();
                _this.addEventListeners();
            }
        });
        // admin area
        this.cashtippr.$(this.cashtippr.window.document).ready(function ($) {
            _this.addConfirmDeleteMessages();
        });
    }
    Shout.prototype.onPayment = function (payment) {
        this.cashtippr.$("#" + payment.domID).parent().parent().submit();
    };
    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################
    Shout.prototype.updateRemainingChars = function () {
        var _this = this;
        var forms = this.cashtippr.$(".ct-message").parent().parent();
        this.cashtippr.$.each(forms, function (index, value) {
            var formEl = _this.cashtippr.$(value);
            var text = formEl.find(".ct-message").val();
            var maxChars = parseInt(formEl.find(".ct-max-chars").val());
            var remaining = maxChars - text.length;
            formEl.find(".ct-chars-left").text(remaining);
            if (remaining < 0) {
                formEl.find(".ct-chars-left").css("color", "red");
                formEl.find(".ct-shout").prop("disabled", true);
                formEl.find(".ct-button").fadeOut("slow");
            }
            else {
                formEl.find(".ct-chars-left").css("color", "");
                if (text.length === 0) { // don't let the user submit empty messages
                    formEl.find(".ct-shout").prop("disabled", true);
                    formEl.find(".ct-button").fadeOut("slow");
                }
                else {
                    formEl.find(".ct-shout").prop("disabled", false);
                    formEl.find(".ct-button").fadeIn("slow");
                }
            }
        });
    };
    Shout.prototype.addEventListeners = function () {
        var _this = this;
        this.cashtippr.$(".ct-message").keyup(function (event) {
            _this.scheduleCharsUpdate();
        });
        this.cashtippr.$(".ct-message").change(function (event) {
            _this.scheduleCharsUpdate();
        });
    };
    Shout.prototype.addConfirmDeleteMessages = function () {
        var _this = this;
        this.cashtippr.$(".ct-delete-shout-link").on("click", function (event) {
            var question = _this.cashtippr.$("#ct-delete-shout-confirm").text();
            if (_this.cashtippr.window.confirm(question) === true)
                return;
            event.preventDefault();
            return false; // should't be needed anymore in 2018
        });
    };
    Shout.prototype.scheduleCharsUpdate = function () {
        var _this = this;
        if (this.scheduleUpdateTimerID !== 0)
            clearTimeout(this.scheduleUpdateTimerID);
        this.scheduleUpdateTimerID = setTimeout(function () {
            _this.updateRemainingChars();
        }, 50);
    };
    return Shout;
}());
exports.Shout = Shout;


/***/ }),

/***/ "./src/WebHelpers.ts":
/*!***************************!*\
  !*** ./src/WebHelpers.ts ***!
  \***************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

Object.defineProperty(exports, "__esModule", { value: true });
var WebHelpers = /** @class */ (function () {
    function WebHelpers(window, $, config) {
        this.window = window;
        this.$ = $;
        this.config = config;
    }
    WebHelpers.prototype.getBrowserLang = function () {
        return this.window.navigator.language.substr(0, 2).toLowerCase();
    };
    WebHelpers.prototype.getCookie = function (c_name) {
        var i, x, y;
        var ARRcookies = this.window.document.cookie.split(";");
        for (i = 0; i < ARRcookies.length; i++) {
            x = ARRcookies[i].substr(0, ARRcookies[i].indexOf("="));
            y = ARRcookies[i].substr(ARRcookies[i].indexOf("=") + 1);
            x = x.replace(/^\s+|\s+$/g, "");
            if (x == c_name)
                return decodeURI(y);
        }
        return null;
    };
    WebHelpers.prototype.setCookie = function (name, value, expireDays) {
        var date = new Date();
        date = new Date(date.getTime() + 1000 * 60 * 60 * 24 * (expireDays ? expireDays : this.config.cookieLifeDays));
        //document.cookie = name + "=" + value + "; expires=" + date.toGMTString() + "; path=" + pageData.cookiePath + "; domain=." + location.host;
        this.window.document.cookie = name + "=" + value + "; expires=" + date.toUTCString() + "; path=" + this.config.cookiePath;
    };
    WebHelpers.prototype.removeCookie = function (name) {
        //document.cookie = name + "=; expires=Thu, 02 Jan 1970 00:00:00 UTC; path=" + pageData.cookiePath + "; domain=." + location.host;
        this.window.document.cookie = name + "=; expires=Thu, 02 Jan 1970 00:00:00 UTC; path=" + this.config.cookiePath;
    };
    WebHelpers.prototype.checkCookieConsent = function () {
        var _this = this;
        this.$(this.config.confirmCookiesBtn).click(function () {
            _this.confirmCookies();
        });
        if (this.getCookie(this.config.consentCookieName) !== null)
            this.$(this.config.confirmCookiesMsg).remove(); // we recently showed the cookie confirm message. some pages might still be in browser cache
    };
    WebHelpers.prototype.fromBase64 = function (data) {
        if (typeof this.window.atob !== "function") {
            this.window.console.error("Base64 decoding is not supported in your browser");
            return "";
        }
        return this.window.atob(data);
    };
    WebHelpers.prototype.toBase64 = function (data) {
        if (typeof this.window.btoa !== "function") {
            this.window.console.error("Base64 encoding is not supported in your browser");
            return "";
        }
        return this.window.btoa(data);
    };
    /**
     * Populate a html template
     * @param text {String}: The html template (or just normal text with variables)
     * @param variables {Object}: the key-value pairs with variables names and their content to be set in text
     * @param safeHtml {boolean, default false}: don't escape html characters if set to true
     * @returns {String} the translated html
     */
    WebHelpers.prototype.translate = function (text, variables, safeHtml) {
        if (safeHtml === void 0) { safeHtml = false; }
        if (typeof text !== "string") {
            try {
                // @ts-ignore
                text = text.toString();
            }
            catch (e) {
                this.log("Text to translate is not a string");
                return text;
            }
        }
        var start = 0, end = 0;
        while ((start = text.indexOf("{", start)) !== -1) {
            if (start > 0 && text.charAt(start - 1) === "\\") { // escaped javascript code beginning
                start++;
                continue;
            }
            end = text.indexOf("}", start);
            if (end === -1) {
                this.log("Can not find end position while translating HTML");
                break;
            }
            var placeHolder = text.substring(start + 1, end);
            var translation = null;
            if (placeHolder.substring(0, 3) === "tr:") {
                var key = placeHolder.substring(3);
                //translation = this.tr(key.toUpperCase());
                translation = this.tr(key);
            }
            else if (typeof variables === "object") {
                var textPiece = variables[placeHolder];
                if (typeof textPiece !== "undefined") {
                    if (typeof safeHtml === "boolean" && safeHtml)
                        translation = textPiece;
                    else
                        translation = this.escapeOutput(textPiece);
                }
            }
            if (translation !== null) {
                var reg = new RegExp("\\{" + placeHolder + "\\}", "g");
                text = text.replace(reg, translation);
            }
            else if (placeHolder.match("[A-Za-z0-9_]+") !== null) {
                this.log("No translation found for place holder: " + placeHolder);
                var reg = new RegExp("\\{" + placeHolder + "\\}", "g");
                text = text.replace(reg, "MISSING: " + this.escapeOutput(placeHolder));
            }
            else
                start += placeHolder.length;
        }
        text = text.replace(/\\\\\\{/, "{");
        return text;
    };
    WebHelpers.prototype.escapeOutput = function (text, convertNewlines) {
        if (convertNewlines === void 0) { convertNewlines = true; }
        if (typeof text !== "string")
            return text;
        text = text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        if (typeof convertNewlines === "undefined" || convertNewlines === true)
            text = text.replace(/\r?\n/g, "<br>");
        return text;
    };
    WebHelpers.prototype.escapeRegex = function (str) {
        return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
    };
    WebHelpers.prototype.tr = function (key) {
        //if (typeof language[key] === "undefined")
        //return "MISSING: " + key;
        //return language[key];
        //return i18next.t(key) // will just print the key if it doesn't exist and debug is disabled
        return key; // we don't have client-side translation support on this WP plugin yet. just return the key // TODO do we need this if we make our plugin use more ajax?
    };
    WebHelpers.prototype.log = function (args) {
        if ( //typeof pageData.debugLog !== "boolean" || pageData.debugLog === false || // TODO add in CashTipprConfig
        typeof this.window.console !== "object" || typeof this.window.console.log !== "function")
            return;
        this.window.console.log(arguments);
    };
    WebHelpers.prototype.isAppleIOS = function () {
        // https://stackoverflow.com/questions/9038625/detect-if-device-is-ios
        return /iPad|iPhone|iPod/.test(this.window.navigator.userAgent) && !this.window.MSStream;
    };
    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################
    WebHelpers.prototype.confirmCookies = function () {
        /* // no data stored in session
        this.getApi('/json/cookies', function(data) {
            if (data.error)
                //Hlp.showMsg(i18next.t('errorSending'), 'danger');
                console.error("Error sending data");
            else {
            }
        })
        */
        this.$(this.config.confirmCookiesMsg).remove();
        this.setCookie(this.config.consentCookieName, "1", this.config.cookieLifeDays);
    };
    WebHelpers.prototype.getApi = function (path, data, callback, dataType) {
        if (typeof data === "function") {
            callback = data;
            data = null;
        }
        else if (data === undefined)
            data = null;
        var url = path;
        if (url.toLowerCase().indexOf("http") !== 0)
            url = this.config.siteUrl + url;
        return this.$.get(url, data, function (data, textStatus, jqXHR) {
            callback(data, textStatus, jqXHR);
        }, dataType);
    };
    return WebHelpers;
}());
exports.WebHelpers = WebHelpers;


/***/ }),

/***/ "./src/admin/Tooltips.ts":
/*!*******************************!*\
  !*** ./src/admin/Tooltips.ts ***!
  \*******************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

Object.defineProperty(exports, "__esModule", { value: true });
var Tooltips = /** @class */ (function () {
    function Tooltips(cashtippr) {
        this.cashtippr = cashtippr;
    }
    /**
     * Initializes status bar hover entries.
     */
    Tooltips.prototype.initToolTips = function () {
        var window = this.cashtippr.window;
        var jQuery = this.cashtippr.$;
        var that = this;
        var tsfL10n = {
            states: {
                debug: false
            }
        };
        var touchBuffer = 0, inTouchBuffer = false;
        var setTouchBuffer = function () {
            inTouchBuffer = true;
            clearTimeout(touchBuffer);
            touchBuffer = setTimeout(function () {
                inTouchBuffer = false;
            }, 250);
        };
        var setEvents = function (target, unset) {
            if (unset === void 0) { unset = false; }
            unset = unset || false;
            var touchEvents = 'pointerdown.tsfTT touchstart.tsfTT click.tsfTT', $target = jQuery(target);
            if (unset) {
                $target.off('mousemove mouseleave mouseout ct-tooltip-update');
                jQuery(document.body).off(touchEvents);
            }
            else {
                $target.on({
                    'mousemove': mouseMove,
                    'mouseleave': mouseLeave,
                    'mouseout': mouseLeave,
                });
                jQuery(document.body).off(touchEvents).on(touchEvents, touchRemove);
            }
            $target.on('ct-tooltip-update', updateDesc);
        };
        var unsetEvents = function (target) {
            setEvents(target, true);
        };
        var updateDesc = function (event) {
            if (event.target.classList.contains('ct-tooltip-item')) {
                var tooltipText = event.target.querySelector('.ct-tooltip-text');
                if (tooltipText instanceof Element)
                    tooltipText.innerHTML = event.target.dataset.desc;
            }
        };
        var mouseEnter = function (event) {
            var $hoverItem = jQuery(event.target), desc = event.target.dataset.desc;
            if (desc && 0 === $hoverItem.find('div').length) {
                //= Remove any titles attached.
                event.target.title = "";
                var $tooltip = jQuery('<div class="ct-tooltip"><span class="ct-tooltip-text-wrap"><span class="ct-tooltip-text">'
                    + desc +
                    '</span></span><div class="ct-tooltip-arrow"></div></div>');
                $hoverItem.append($tooltip);
                var $boundary = $hoverItem.closest('.ct-tooltip-boundary');
                $boundary = $boundary.length && $boundary || jQuery(document.body);
                //= 9 = arrow (8) + shadow (1)
                var tooltipHeight = $hoverItem.outerHeight() + 9, tooltipTop = $tooltip.offset().top - tooltipHeight, boundaryTop = $boundary.offset().top - ($boundary.prop('scrolltop') || 0);
                if (boundaryTop > tooltipTop) {
                    $tooltip.addClass('ct-tooltip-down');
                    $tooltip.css('top', tooltipHeight + 'px');
                }
                else {
                    $tooltip.css('bottom', tooltipHeight + 'px');
                }
                var $hoverItemWrap = $hoverItem.closest('.ct-tooltip-wrap'), $textWrap = $tooltip.find('.ct-tooltip-text-wrap'), $innerText = $textWrap.find('.ct-tooltip-text'), hoverItemWrapWidth = $hoverItemWrap.width(), textWrapWidth = $textWrap.outerWidth(true), textWidth = $innerText.outerWidth(true), textLeft = $textWrap.offset().left, textRight = textLeft + textWidth, boundaryLeft = $boundary.offset().left - ($boundary.prop('scrollLeft') || 0), boundaryRight = boundaryLeft + $boundary.outerWidth();
                //= RTL and LTR are normalized to abide to left.
                var direction = 'left';
                if (textLeft < boundaryLeft) {
                    //= Overflown over left boundary (likely window)
                    //= Add indent relative to boundary. 24px width of arrow / 2 = 12 middle
                    var horIndent = boundaryLeft - textLeft + 12, basis = parseInt($textWrap.css('flex-basis'), 10);
                    /**
                     * If the overflow is greater than the tooltip flex basis,
                     * the tooltip was grown. Shrink it back to basis and use that.
                     */
                    if (horIndent < -basis)
                        horIndent = -basis;
                    $tooltip.css(direction, horIndent + 'px');
                    $tooltip.data('overflow', horIndent);
                    $tooltip.data('overflowDir', direction);
                }
                else if (textRight > boundaryRight) {
                    //= Overflown over right boundary (likely window)
                    //= Add indent relative to boundary. Add 12px for visual appeal.
                    var horIndent = boundaryRight - textRight - hoverItemWrapWidth - 12, basis = parseInt($textWrap.css('flex-basis'), 10);
                    /**
                     * If the overflow is greater than the tooltip flex basis,
                     * the tooltip was grown. Shrink it back to basis and use that.
                     */
                    if (horIndent < -basis)
                        horIndent = -basis;
                    $tooltip.css(direction, horIndent + 'px');
                    $tooltip.data('overflow', horIndent);
                    $tooltip.data('overflowDir', direction);
                }
                else if (hoverItemWrapWidth < 42) {
                    //= Small tooltip container. Add indent to make it visually appealing.
                    var indent = -15;
                    $tooltip.css(direction, indent + 'px');
                    $tooltip.data('overflow', indent);
                    $tooltip.data('overflowDir', direction);
                }
                else if (hoverItemWrapWidth > textWrapWidth) {
                    //= Wrap is bigger than tooltip. Adjust accordingly.
                    var pagex = event.originalEvent && event.originalEvent.pageX || event.pageX, // iOS touch support,
                    hoverItemLeft = $hoverItemWrap.offset().left, center = pagex - hoverItemLeft, left = center - textWrapWidth / 2, right = left + textWrapWidth;
                    if (left < 0) {
                        //= Don't overflow left.
                        left = 0;
                    }
                    else if (right > hoverItemWrapWidth) {
                        //= Don't overflow right.
                        //* Use textWidth instead of textWrapWidth as it gets squashed in flex.
                        left = hoverItemWrapWidth - textWidth;
                    }
                    $tooltip.css(direction, left + 'px');
                    $tooltip.data('adjust', left);
                    $tooltip.data('adjustDir', direction);
                }
            }
        };
        var mouseMove = function (event) {
            var $target = jQuery(event.target), $tooltip = $target.find('.ct-tooltip'), $arrow = $tooltip.find('.ct-tooltip-arrow'), overflow = $tooltip.data('overflow'), overflowDir = $tooltip.data('overflowDir');
            overflow = parseInt(overflow, 10);
            overflow = isNaN(overflow) ? 0 : -Math.round(overflow);
            if (overflow) {
                //= Static arrow based on static overflow.
                $arrow.css(overflowDir, overflow + "px");
            }
            else {
                var pagex = event.originalEvent && event.originalEvent.pageX || event.pageX, // iOS touch support
                arrowBoundary = 7, arrowWidth = 16, $hoverItemWrap = $target.closest('.ct-tooltip-wrap'), mousex = pagex - $hoverItemWrap.offset().left - arrowWidth / 2, originalMousex = mousex, $textWrap = $tooltip.find('.ct-tooltip-text-wrap'), textWrapWidth = $textWrap.outerWidth(true), adjust = $tooltip.data('adjust'), adjustDir = $tooltip.data('adjustDir'), boundaryRight = textWrapWidth - arrowWidth - arrowBoundary;
                //= mousex is skewed, adjust.
                adjust = parseInt(adjust, 10);
                adjust = isNaN(adjust) ? 0 : Math.round(adjust);
                if (adjust) {
                    adjust = 'left' === adjustDir ? -adjust : adjust;
                    mousex = mousex + adjust;
                    //= Use textWidth for right boundary if adjustment exceeds.
                    if (boundaryRight - adjust > $hoverItemWrap.outerWidth(true)) {
                        var $innerText = $textWrap.find('.ct-tooltip-text'), textWidth = $innerText.outerWidth(true);
                        boundaryRight = textWidth - arrowWidth - arrowBoundary;
                    }
                }
                if (mousex <= arrowBoundary) {
                    //* Overflown left.
                    $arrow.css('left', arrowBoundary + "px");
                }
                else if (mousex >= boundaryRight) {
                    //* Overflown right.
                    $arrow.css('left', boundaryRight + "px");
                }
                else {
                    //= Somewhere in the middle.
                    $arrow.css('left', mousex + "px");
                }
            }
        };
        var mouseLeave = function (event) {
            //* @see touchMove
            if (inTouchBuffer)
                return;
            jQuery(event.target).find('.ct-tooltip').remove();
            unsetEvents(event.target);
        };
        /**
         * ^^^
         * These two methods conflict eachother in EdgeHTML.
         * Thusly, touch buffer.
         * vvv
         */
        var touchRemove = function (event) {
            //* @see mouseLeave
            setTouchBuffer();
            var itemSelector = '.ct-tooltip-item', balloonSelector = '.ct-tooltip';
            var $target = jQuery(event.target), $keepBalloon;
            if ($target.hasClass('ct-tooltip-item')) {
                $keepBalloon = $target.find(balloonSelector);
            }
            if (!$keepBalloon) {
                var $children = $target.children(itemSelector);
                if ($children.length) {
                    $keepBalloon = $children.find(balloonSelector);
                }
            }
            if ($keepBalloon && $keepBalloon.length) {
                //= Remove all but this.
                jQuery(balloonSelector).not($keepBalloon).remove();
            }
            else {
                //= Remove all.
                jQuery(balloonSelector).remove();
            }
        };
        /**
         * Loads tooltips within wrapper.
         * @function
         */
        var loadToolTip = function (event) {
            if (inTouchBuffer)
                return;
            var isTouch = false;
            switch (event.type) {
                case 'mouseenter':
                    //= Most likely, thus placed first.
                    break;
                case 'pointerdown':
                case 'touchstart':
                    isTouch = true;
                    break;
                default:
                    break;
            }
            if (event.target.classList.contains('ct-tooltip-item')) {
                //= Removes previous items and sets buffer.
                isTouch && touchRemove(event);
                mouseEnter(event);
                //= Initiate placement directly for Windows Touch or when overflown.
                mouseMove(event);
                setEvents(event.target);
            }
            else {
                //= Delegate or bubble, and go back to this method with the correct item.
                var item = event.target.querySelector('.ct-tooltip-item:hover'), _event = new jQuery.Event(event.type);
                _event.pageX = event.originalEvent && event.originalEvent.pageX || event.pageX;
                if (item) {
                    if (tsfL10n.states.debug)
                        console.log('Tooltip event warning: delegation');
                    jQuery(item).trigger(_event);
                }
                else {
                    if (tsfL10n.states.debug)
                        console.log('Tooltip event warning: bubbling');
                    jQuery(event.target).closest('.ct-tooltip-wrap').find('.ct-tooltip-item:hover').trigger(_event);
                }
            }
            //* Stop further propagation.
            event.stopPropagation();
        };
        /**
         * Initializes tooltips.
         * @function
         */
        var initTooltips = function () {
            var $wrap = jQuery('.ct-tooltip-wrap');
            $wrap.off('mouseenter pointerdown touchstart');
            $wrap.on('mouseenter pointerdown touchstart', '.ct-tooltip-item', loadToolTip);
        };
        initTooltips();
        jQuery(window).on('ct-reset-tooltips', initTooltips);
        (function () {
            var e = jQuery('#wpcontent');
            that.addTooltipBoundary(e);
        })();
    };
    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################
    /**
     * Adds tooltip boundaries.
     */
    Tooltips.prototype.addTooltipBoundary = function (e) {
        jQuery(e).addClass('ct-tooltip-boundary');
    };
    /**
     * Triggers tooltip reset.
     */
    Tooltips.prototype._triggerTooltipReset = function () {
        jQuery(window).trigger('ct-reset-tooltips');
    };
    /**
     * Triggers active tooltip update.
     */
    Tooltips.prototype._triggerTooltipUpdate = function (item) {
        jQuery(item).trigger('ct-tooltip-update');
    };
    return Tooltips;
}());
exports.Tooltips = Tooltips;


/***/ })

/******/ });
//# sourceMappingURL=bundle.js.map