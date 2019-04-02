import {AbstractPayment, CashTippr} from "./CashTippr";

/**
 * The class for displaying the shout addon.
 * We bundle it together with all our JavaScript instead of with the addon because
 * a 2nd JS WebPack project would create unnecessary dependency + bootstrap overhead.
 */
export class Shout {
    protected cashtippr: CashTippr;
    protected scheduleUpdateTimerID = 0;

    constructor(cashtippr: CashTippr) {
        this.cashtippr = cashtippr;
        // TODO implement events this class (and other addons) can listen to so that we don't have to call functions in here directly
        this.cashtippr.$(this.cashtippr.window.document).ready(($) => {
            if (this.cashtippr.$(".ct-message").length !== 0) {
                this.updateRemainingChars();
                this.addEventListeners();
            }
        });

        // admin area
        this.cashtippr.$(this.cashtippr.window.document).ready(($) => {
            this.addConfirmDeleteMessages();
        })
    }

    public onPayment(payment: AbstractPayment) {
        setTimeout(() => { // shouldn't be needed, but BadgerWallet is really fast (running locally). just be sure the WP backend processed our payment
            this.cashtippr.$("#" + payment.domID).parent().parent().submit();
        }, 500);
    }

    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################

    protected updateRemainingChars() {
        const forms = this.cashtippr.$(".ct-message").parent().parent();
        this.cashtippr.$.each(forms, (index, value) => {
            const formEl = this.cashtippr.$(value);
            let text = formEl.find(".ct-message").val();
            let maxChars = parseInt(formEl.find(".ct-max-chars").val());
            const remaining = maxChars - text.length;
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
    }

    protected addEventListeners() {
        this.cashtippr.$(".ct-message").keyup((event) => {
            this.scheduleCharsUpdate();
        });
        this.cashtippr.$(".ct-message").change((event) => {
            this.scheduleCharsUpdate();
        });
        this.cashtippr.$(".ct-shout-form").on("submit.cashtippr", (event) => { // fix for badger wallet submitting form on click (before payment)
            event.preventDefault();
            this.cashtippr.$(event.target).off("submit.cashtippr");
        });
    }

    protected addConfirmDeleteMessages() {
        this.cashtippr.$(".ct-delete-shout-link").on("click", (event) => {
            const question = this.cashtippr.$("#ct-delete-shout-confirm").text();
            if (this.cashtippr.window.confirm(question) === true)
                return;
            event.preventDefault();
            return false; // should't be needed anymore in 2018
        });
    }

    protected scheduleCharsUpdate() {
        if (this.scheduleUpdateTimerID !== 0)
            clearTimeout(this.scheduleUpdateTimerID);
        this.scheduleUpdateTimerID = setTimeout(() => {
            this.updateRemainingChars();
        }, 50);
    }
}