import {AbstractPayment, CashTippr, CashtipprApiRes} from "./CashTippr";
import {WebHelpers} from "./WebHelpers";

export interface BadgerWalletPayment extends AbstractPayment {
    buttonId: string; // unique ID, tx ID in our case
    txid: string;
    amount: number;
    currency: string;
    buttonData: string; // JSON string "{}" or base64 data in our app
    buttonDataObj: any; // unserialized JS object from buttonData
}

export class BadgerWallet {
    protected cashtippr: CashTippr;
    protected webHelpers: WebHelpers;
    protected globalCallbacks: boolean; // expose callback functions to window object because BadgerButton currently doesn't support functions on objects

    constructor(cashtippr: CashTippr, webHelpers: WebHelpers, globalCallbacks = false) {
        this.cashtippr = cashtippr;
        this.webHelpers = webHelpers;
        this.globalCallbacks = globalCallbacks;

        if (this.globalCallbacks === true) {
            setTimeout(() => { // must be done after constructor or else functions are undefined
                this.cashtippr.window['onBadgerPayment'] = this.onBadgerPayment;
                this.cashtippr.window['onBadgerClientPayment'] = this.sendPaymentReceived;
            }, 0);
        }

        this.cashtippr.$(this.cashtippr.window.document).ready(($) => {
            this.cashtippr.$(".ct-input-amount").keyup((event) => {
                this.updateButtonAmount(event.target);
            });
            this.cashtippr.$(".ct-input-amount").change((event) => {
                this.updateButtonAmount(event.target);
            });
        });
    }

    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################

    protected onBadgerPayment(payment: BadgerWalletPayment) {
        if (typeof payment.buttonData === "string" && payment.buttonData.length !== 0 && payment.buttonData[0] !== "{")
            payment.buttonDataObj = JSON.parse(this.cashtippr.getWebHelpers().fromBase64(payment.buttonData)) || {}; // this.webHelpers is undefined
        if (payment.domID === undefined)
            payment.domID = "ct-btn-wrap-" + payment.buttonId;
        //console.log("Badger Payment", payment);

        // TODO make an ajax call to WP to check if the payment has actually been received (will an extension support some webhook callback? likely not)
        // then depending on if the content is hidden with CSS: modify style or reload page (server can tell this, or we add a variable)
        // simply always remove the style
        const config = this.cashtippr.getConfig();
        if (config.show_search_engines === true || config.ajaxConfirm === true) {
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
            this.cashtippr.window['onBadgerClientPayment'].call(this.cashtippr.badger, payment, (res: CashtipprApiRes) => {
                if (payment.buttonDataObj && payment.buttonDataObj.shout === true)
                    this.cashtippr.shout.onPayment(payment);
                if (payment.buttonDataObj && payment.buttonDataObj.woocommerce === true)
                    this.cashtippr.woocommerce.onPayment(payment);
            });
            // TODO increment tips received and donation goal progress with JS
            if (typeof this.cashtippr.window['onCashtipprPayment'] === "function")
                this.cashtippr.window['onCashtipprPayment'].call(this.cashtippr.window, {badger: payment});
            return;
        }

        this.cashtippr.window['onBadgerClientPayment'].call(this.cashtippr.badger, payment, (res: CashtipprApiRes) => {
            if (payment.buttonDataObj && payment.buttonDataObj.shout === true) {
                this.cashtippr.shout.onPayment(payment);
                return; // this will already reload the page
            }
            // TODO add random query param to bypass caching? look at different WP caching plugins first
            // some might ignore query strings
            // better way would be to implement WP hooks to bypass cache for paid users (if provided by cache plugins)
            // to make it work with full-page caching we have to fetch params such as TXID via ajax either way
            this.cashtippr.window.location.reload(true);
        });
    }

    protected sendPaymentReceived(payment: BadgerWalletPayment, callback?: (res: CashtipprApiRes) => void) {
        let params = {
            txid: payment.buttonId,
            am: payment.amount,
            keep: this.cashtippr.getConfig().keepTransaction === true
        }
        this.webHelpers.getApi("/wp-json/cashtippr/v1/mb-client", params, (data) => {
            callback && callback(data);
        });
    }

    protected updateButtonAmount(target: Element) {
        const btnContainer = this.cashtippr.$(target).parent().parent();
        const btn = btnContainer.find(".badger-button");
        if (!btn) {
            this.cashtippr.window.console.error("Unable to find Badger button", btnContainer);
            return;
        }
        const amountNewUserCurrency = parseFloat(this.cashtippr.$(target).val());
        if (amountNewUserCurrency === 0.0 || amountNewUserCurrency === Number.NaN)
            return;
        const currency = this.cashtippr.getConfig().display_currency;
        const amountSats = CashTippr.toSatoshis(amountNewUserCurrency / this.cashtippr.getConfig().rate[currency]);
        btn.attr("data-satoshis", amountSats);
        btn.find(".ct-btn-display-amount").text(amountNewUserCurrency);
    }
}