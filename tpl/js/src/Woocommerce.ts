import {AbstractPayment, CashTippr} from "./CashTippr";
import {WebHelpers} from "./WebHelpers";
import {Order} from "./structs/Order";
import {AbstractModule} from "./AbstractModule";

/**
 * Class to interact with the Woocommerce store of a WordPress installation.
 */
export class Woocommerce extends AbstractModule {
    protected fullyPaid: boolean = false;
    protected checkServerPaymentTimerID: number = 0;

    constructor(cashtippr: CashTippr, webHelpers: WebHelpers) {
        super(cashtippr, webHelpers);
        this.cashtippr.$(this.cashtippr.window.document).ready(($) => {
            const config = this.cashtippr.getConfig();
            this.addButtonLinkClass();
            // check payment stastus sooner on 1st page load to update remaining amount
            if (config.checkPaymentIntervalSec > 0)
                setTimeout(this.checkPaymentStatus.bind(this, true), /*config.checkPaymentIntervalSec*/1*1000);
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
        clearTimeout(this.checkServerPaymentTimerID);
        this.webHelpers.getApi("/wp-json/cashtippr-wc/v1/order-status", params, (data) => {
            if (repeat === true && config.checkPaymentIntervalSec > 0) { // TODO abort checking after x minutes?
                clearTimeout(this.checkServerPaymentTimerID); // ensure we don't have multiple timers running
                this.checkServerPaymentTimerID = setTimeout(this.checkPaymentStatus.bind(this, true), config.checkPaymentIntervalSec * 1000);  // always repeat the check even if there was an error
            }
            if (data.error === true) {
                this.cashtippr.window.console.error("Error checking BCH payment status: %s", data.errorMsg);
                return;
            }
            if (data.data && data.data.length >= 1) {
                const order: Order = Object.assign(new Order(), data.data[0]);
                if (order.status === "paid") {
                    this.showPaymentReceived();
                    return;
                }
                else if (order.bchAmountReceived > 0.0) {
                    this.showPartialPayment(order);
                    return;
                }
            }
        });
    }

    protected showPartialPayment(order: Order) {
        const numDecimals = this.cashtippr.getConfig().paymentCommaDigits;
        const remainingAmount = order.calculateRemaningAmount().toFixed(numDecimals);
        if (this.cashtippr.$("#ct-pay-amount-txt").text() === remainingAmount)
            return;
        this.cashtippr.$("#ct-pay-amount-txt").text(remainingAmount);
        this.cashtippr.$("#ct-payment-remaining").fadeIn("slow");

        // update payment amount in URI and QR code
        this.cashtippr.$("#ct-qr-code-image").attr("src", order.qrcode);
        this.cashtippr.$("#ct-address").val(order.uri);
        this.cashtippr.$("#ct-pay-app").attr("href", order.uri);
        this.cashtippr.$(".ct-badger-button").attr("data-satoshis", order.calculateRemaningAmount());
    }

    protected showPaymentReceived() {
        if (this.fullyPaid === true)
            return; // already updated
        this.fullyPaid = true;
        clearTimeout(this.checkServerPaymentTimerID);
        this.cashtippr.$("#ct-payment-status").text(this.cashtippr.getConfig().paidTxt);
        this.cashtippr.$("#ct-payment-pending, #ct-pay-instructions, .ct-payment-option").fadeOut("slow");
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
}
