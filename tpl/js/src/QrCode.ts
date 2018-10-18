import {AbstractPayment, CashTippr, CashtipprApiRes} from "./CashTippr";
import {WebHelpers} from "./WebHelpers";


export class QrCode {
    protected cashtippr: CashTippr;
    protected webHelpers: WebHelpers;

    constructor(cashtippr: CashTippr, webHelpers: WebHelpers) {
        this.cashtippr = cashtippr;
        this.webHelpers = webHelpers;

        this.cashtippr.$(this.cashtippr.window.document).ready(($) => {
            this.cashtippr.$(".ct-qrcode-btn").click((event) => {
                this.showQrDialog(event.target);
            });
        });
    }

    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################

    protected showQrDialog(target: Element) {
        const paymentButtonlWrapper = this.cashtippr.$(target).parent().parent().parent();
        const txid = paymentButtonlWrapper.attr("id").replace(/^ct-btn-wrap-/, "");
        const paymentCtrlWrapper = paymentButtonlWrapper.parent();
        let amount = 0.0;
        if (paymentCtrlWrapper.find("input[name='amount']").length !== 0)
            amount = paymentCtrlWrapper.find("input[name='amount']").val(); // custom editable amount by user
        let params = {
            txid: txid,
            am: amount
        }
        let dialog = this.webHelpers.translate(this.cashtippr.$("#ct-modal-qrcode-dialog-tpl").html(), {
            dialog_class: "dialog-" + txid
        });
        this.cashtippr.$("body").append(dialog); // its position is fixed relative to the viewport

        this.webHelpers.getApi("/wp-json/cashtippr/v1/qrcode", params, (data) => {
            this.cashtippr.$(".ct-loading").remove();
            if (data.error === true) {
                this.cashtippr.window.console.error("Error starting QR code tip");
                this.cashtippr.window.console.error(data.errorMsg);
                return;
            }
            this.cashtippr.$(".ct-qr-code").fadeIn("slow").attr("src", data.data[0]);
        });

        this.cashtippr.$(".ct-close-dialog").click((event) => {
            this.cashtippr.$(".dialog-" + txid).remove();
        });
        this.cashtippr.$(".ct-copy-field").click((event) => {
            const inputEl = this.cashtippr.$(event.target).parent().find("input[type='text']");
            //let text = inputEl.val();
            inputEl.select();
            this.cashtippr.window.document.execCommand("copy");
        });
    }
}