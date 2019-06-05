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
}