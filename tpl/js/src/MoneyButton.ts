import {AbstractPayment, CashTippr, CashtipprApiRes} from "./CashTippr";
import {WebHelpers} from "./WebHelpers";

export interface MoneyButtonPayment extends AbstractPayment {
    amount: string;
    buttonData: string; // JSON string "{}" or base64 data in our app
    buttonDataObj: any; // unserialized JS object from buttonData
    buttonId: string; // unique ID, tx ID in our case
    clientId: string;
    createdAt: string; // ISO date
    currency: string;
    deletedAt: string; // ISO date
    id: string;
    normalizedTxid: string;
    satoshis: string;
    status: string;
    statusDescription: string;
    transactionId: string;
    txid: string;
    updatedAt: string; // ISO date
    userId: string;
}

export class MoneyButton {
    protected static readonly UPDATE_AMOUNT_DELAY_MS = 300; // delay it to prevent flickering if the user updates multiplet imes

    protected cashtippr: CashTippr;
    protected webHelpers: WebHelpers;
    protected globalCallbacks: boolean; // expose callback functions to window object because MoneyButton currently doesn't support functions on objects
    protected scheduleUpdateAmountTimerID = 0;

    constructor(cashtippr: CashTippr, webHelpers: WebHelpers, globalCallbacks = false) {
        this.cashtippr = cashtippr;
        this.webHelpers = webHelpers;
        this.globalCallbacks = globalCallbacks;
        if (this.globalCallbacks === true) {
            setTimeout(() => { // must be done after constructor or else functions are undefined
                this.cashtippr.window['onMoneyButtonPayment'] = this.onMoneyButtonPayment;
                this.cashtippr.window['onMoneyButtonError'] = this.onMoneyButtonError;
                this.cashtippr.window['onMoneyButtonClientPayment'] = this.sendPaymentReceived;
            }, 0);
        }

        this.cashtippr.$(this.cashtippr.window.document).ready(($) => {
            this.cashtippr.$(".ct-input-amount").keyup((event) => {
                this.scheduleUpdateAmount(event.target);
            });
            this.cashtippr.$(".ct-input-amount").change((event) => {
                this.scheduleUpdateAmount(event.target);
            });
        });
    }

    public onMoneyButtonPayment(payment: MoneyButtonPayment) {
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
            this.cashtippr.window['onMoneyButtonClientPayment'].call(this.cashtippr.mb, payment, (res: CashtipprApiRes) => {
                if (payment.buttonDataObj && payment.buttonDataObj.shout === true)
                    this.cashtippr.shout.onPayment(payment);
            });
            // TODO increment tips received and donation goal progress with JS
            if (typeof this.cashtippr.window['onCashtipprPayment'] === "function")
                this.cashtippr.window['onCashtipprPayment'].call(this.cashtippr.window, {moneybutton: payment});
            return;
        }

        this.cashtippr.window['onMoneyButtonClientPayment'].call(this.cashtippr.mb, payment, (res: CashtipprApiRes) => {
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

    public onMoneyButtonError(error: any) {
        this.cashtippr.window.console.error("MoneyButton payment error", error)
    }

    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################

    protected sendPaymentReceived(payment: MoneyButtonPayment, callback?: (res: CashtipprApiRes) => void) {
        let params = {
            txid: payment.buttonId,
            am: payment.amount
        }
        this.webHelpers.getApi("/wp-json/cashtippr/v1/mb-client", params, (data) => {
            callback && callback(data);
        });
    }

    protected scheduleUpdateAmount(target: Element) {
        if (this.scheduleUpdateAmountTimerID !== 0)
            clearTimeout(this.scheduleUpdateAmountTimerID);
        this.scheduleUpdateAmountTimerID = setTimeout(() => {
            this.updateButtonAmount(target);
        }, MoneyButton.UPDATE_AMOUNT_DELAY_MS);
    }

    protected updateButtonAmount(target: Element) {
        const btnContainer = this.cashtippr.$(target).parent().parent();
        const btnFrame = btnContainer.find("iframe");
        if (!btnFrame) {
            console.error("Unable to find MoneyButton iframe at element", btnContainer);
            return;
        }
        let src = btnFrame.attr("src");
        if (src.indexOf("&amt=") === -1 || src.indexOf("&lbl=") === -1) {
            console.error("Unable to find MoneyButton amount in iframe src. Please report this, as most likely their API has changed. src=%s", src);
            return;
        }
        const amountNew = parseFloat(this.cashtippr.$(target).val());
        if (amountNew === 0.0 || amountNew === Number.NaN)
            return;
        // TODO check if the amount really changed? shouldn't be needed
        src = src.replace(/&amt=[0-9\.]+&/, "&amt=" + amountNew + "&")
            .replace(/&lbl=[0-9\.]+/, "&lbl=" + amountNew);
        btnFrame.attr("src", src);
    }
}