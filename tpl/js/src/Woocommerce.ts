import {AbstractPayment, CashTippr} from "./CashTippr";
import {WebHelpers} from "./WebHelpers";

/**
 * Class to interact with the Woocommerce store of a WordPress installation.
 */
export class Woocommerce {
    protected cashtippr: CashTippr;
    protected webHelpers: WebHelpers;

    constructor(cashtippr: CashTippr, webHelpers: WebHelpers) {
        this.cashtippr = cashtippr;
        this.webHelpers = webHelpers;
        this.cashtippr.$(this.cashtippr.window.document).ready(($) => {
            const config = this.cashtippr.getConfig();
            this.addButtonLinkClass();
            if (config.checkPaymentIntervalSec > 0)
                setTimeout(this.checkPaymentStatus.bind(this, true), config.checkPaymentIntervalSec*1000);
            //this.addPayButtonListener();

            if (this.cashtippr.$("#ct-qrcode-form").length !== 0)
                this.addPaymentFormEvents(this.cashtippr.$("#ct-qrcode-form").eq(0)); // the form after the order has been placed
        });
    }

    public onPayment(payment: AbstractPayment) {
        this.sendPaymentForValidation(payment);
    }

    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################

    protected sendPaymentForValidation(payment: AbstractPayment) {
        const firstButton = this.cashtippr.$(".ct-btn-wrap-top"); // there can only be 1 Woocommerce payment button per payment method
        let params = {
            txid: payment.txid,
            dbtxid: this.cashtippr.getPluginPaymentID(firstButton),
            am: payment.amount
        }
        this.webHelpers.getApi("/wp-json/cashtippr-wc/v1/validate", params, (data) => {
            if (data.data.length === 1) {
                this.cashtippr.window.document.location.assign(data.data[0].url);
            }
            // don't show an error message otherwise, because we likely just wait for more confirmations
        });
    }

    protected addPaymentFormEvents(paymentFormEl: JQuery) {
        this.cashtippr.addCopyInputListeners();
    }

    protected checkPaymentStatus(repeat = true) {
        const config = this.cashtippr.getConfig();
        if (config.nonce === undefined || config.orderID <= 0)
            return;
        let params = {
            n: config.nonce,
            oid: config.orderID
        }
        this.webHelpers.getApi("/wp-json/cashtippr-wc/v1/order-status", params, (data) => {
            if (data.error === true) {
                this.cashtippr.window.console.error("Error checking BCH payment status: %s", data.errorMsg);
                return;
            }
            if (data.data && data.data.length >= 1) {
                if (data.data[0].status === "paid") {
                    this.showPaymentReceived();
                    return;
                }
            }
            if (config.checkPaymentIntervalSec > 0) // TODO abort checking after x minutes?
                setTimeout(this.checkPaymentStatus.bind(this, true), config.checkPaymentIntervalSec*1000);
        });
    }

    protected addButtonLinkClass() {
        const buttons = this.cashtippr.$("#ct-pay-app");
        if (buttons.length === 0)
            return;
        // try to find a native WP theme button style first
        if (this.webHelpers.isExistingCssSelector("a.btn") === true)
            buttons.addClass("btn");
        else if (this.webHelpers.isExistingCssSelector("a.button") === true)
            buttons.addClass("button");
        else
            buttons.addClass("ct-button-link");
    }

    protected showPaymentReceived() {
        this.cashtippr.$("#ct-payment-status").text(this.cashtippr.getConfig().paidTxt);
        this.cashtippr.$("#ct-payment-pending").remove();
    }
}
