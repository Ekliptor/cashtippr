import {AbstractPayment, CashTippr, CashtipprApiRes} from "./CashTippr";
import {WebHelpers} from "./WebHelpers";
import {AbstractModule} from "./AbstractModule";


export class QrCode extends AbstractModule {

    constructor(cashtippr: CashTippr, webHelpers: WebHelpers) {
        super(cashtippr, webHelpers);

        this.cashtippr.$(this.cashtippr.window.document).ready(($) => {
            this.cashtippr.$(".ct-qrcode-btn").click((event) => {
                this.showQrDialog(event.target);
            });
        });
    }

    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################

    protected showQrDialog(target: Element) {
        const paymentButtonWrapper = this.cashtippr.$(target).parent().parent().parent().parent();
        const txid = this.cashtippr.getPluginPaymentID(paymentButtonWrapper);
        const paymentCtrlWrapper = paymentButtonWrapper.parent();
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
        this.cashtippr.addCopyInputListeners();
    }
}
