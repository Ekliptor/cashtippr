import {AbstractModule} from "./AbstractModule";
import {CashTippr} from "./CashTippr";
import {FingerPrint} from "./FingerPrint";
import {SlpAddress} from "./structs/SlpAddress";


export interface SlpPressConfig {
    userID: number;
    //amount: number;
    amountLow: number;
    timeSec: number;
    id: string;
    ticker: string;
    name: string;
    icon: string;
    website: string;

    // localizations
    tr: {
        unlockBadger: string;
        badgerReceived: string;
        walletReceived: string;
        enterWallet: string;
        moreInfo: string;
        invalidAddress: string;
        configureToken: string;
        invalidPassphrase: string;
        importOk: string;
        confirmDeleteWallet: string;
        laavePage: string;
    }

    html: {
        tokenMsg: string;
        askWallet: string;
    }

    admin?: { // only present for admins
        token: string;
        address: string;
        addressBch: string;
        mnemonic: string;
    }
}

export class SlpPress extends AbstractModule {
    protected fingerPrint: FingerPrint;

    constructor(cashtippr: CashTippr) {
        super(cashtippr);
        if (this.cashtippr.getConfig().distributeTokens === undefined || this.cashtippr.$("body").hasClass("wp-admin") === true)
            return;
        this.fingerPrint = new FingerPrint(this.cashtippr);
        this.cashtippr.$(this.cashtippr.window.document).ready(($) => {
            setTimeout(this.checkTokens.bind(this), this.cashtippr.getConfig().distributeTokens.timeSec*1000);
        });
    }

    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################

    protected startSendingTokens(amount: number, slpAddress: string) {
        const conf = this.cashtippr.getConfig().distributeTokens;
        const badgerWallet = this.cashtippr.getBadgerWallet();
        if (badgerWallet.isInstalled() === true) { // if badger wallet is installed we send tokens to it
            if (badgerWallet.isLoggedIn() === false)
                this.showTokenInfo(this.cashtippr.getWebHelpers().translate(conf.tr.unlockBadger, {
                    amount: amount,
                    ticker: conf.ticker
                }), true, false);
            else {
                let receivedHtml = this.cashtippr.getWebHelpers().translate(conf.tr.badgerReceived, {
                    amount: amount,
                    ticker: conf.ticker
                });
                this.showTokenInfo(receivedHtml + '<br>' + this.getTokenInfoLink(), false, true);
            }
            return;
        }

        // otherwise send it to the user's external wallet (we stored in server session)
        if (!slpAddress) {
            // we ask for the user's SLP address
            let askHtml = this.cashtippr.getWebHelpers().translate(conf.tr.enterWallet, {
                amount: amount,
                ticker: conf.ticker,
            });
            this.askUserWallet(askHtml + '<br>' + this.getTokenInfoLink());
        }
        else {
            let receivedHtml = this.cashtippr.getWebHelpers().translate(conf.tr.walletReceived, {
                amount: amount,
                ticker: conf.ticker,
                address: slpAddress
            });
            this.showTokenInfo(receivedHtml + '<br>' + this.getTokenInfoLink(), false, true);
        }
    }

    protected showTokenInfo(message: string, showReload = true, showClose = false) {
        let html = this.cashtippr.getConfig().distributeTokens.html.tokenMsg;
        const barHeight = this.getWpAdminbarHeight();
        html = this.webHelpers.translate(html, {
            message: message,
            style: barHeight !== 0.0 ? "top: " + barHeight + "px;" : "",
        }, true);
        this.cashtippr.$("body").append(html);

        // Close button
        if (showClose === false)
            this.cashtippr.$(".ct-close-message").css("display", "none");
        this.cashtippr.$(".ct-close-message").off("click").on("click", (event) => {
            this.cashtippr.$("#ct-tokenMsg").remove();
        });

        // Reload Button
        if (showReload === false) {
            this.cashtippr.$(".ct-reload-page").css("display", "none");
            return;
        }
        this.cashtippr.$(".ct-reload-page").off("click").on("click", (event) => {
            this.cashtippr.window.document.location.reload();
        });
    }

    protected askUserWallet(message: string) {
        let html = this.cashtippr.getConfig().distributeTokens.html.askWallet;
        const barHeight = this.getWpAdminbarHeight();
        html = this.webHelpers.translate(html, {
            message: message,
            style: barHeight !== 0.0 ? "top: " + barHeight + "px;" : "",
        }, true);
        this.cashtippr.$("body").append(html);

        this.cashtippr.$("#ct-input-slp-wallet-form").off("submit").on("submit", (event) => {
            event.preventDefault();
            let params = {
                slpWallet: this.cashtippr.$("#ct-slp-wallet").val()
            }
            if (SlpAddress.isValidSlpAddress(params.slpWallet) === false) {
                this.cashtippr.window.alert(this.cashtippr.getConfig().distributeTokens.tr.invalidAddress);
                return;
            }
            this.webHelpers.postApi("/wp-json/cashtippr-slp-press/v1/add-wallet", params, (data) => {
                if (data.error === true) {
                    this.cashtippr.window.console.error("CashTippr: Error adding wallet:", data);
                    return;
                }
                this.cashtippr.$("#ct-tokenMsg").remove();
                this.checkTokens(); // call it again
            });
        });
    }

    protected getWpAdminbarHeight(): number {
        return this.cashtippr.$("#wpadminbar").height() ?? 0.0;
        //return this.cashtippr.$("#wpadminbar").length !== 0 ? this.cashtippr.$("#wpadminbar").height() + 4 : 0.0;
    }

    protected getTokenInfoLink(): string {
        const conf = this.cashtippr.getConfig().distributeTokens;
        let linkHtml = '<div class="ct-token-info"><a target="_blank" href="url">';
        if (conf.icon)
            linkHtml += '<img src="{iconSrc}" alt="{ticker}" width="32" height="32"> ';
        linkHtml += '{text}</a></div>';
        return this.cashtippr.getWebHelpers().translate(linkHtml, {
            url: conf.website,
            text: conf.tr.moreInfo,
            iconSrc: conf.icon,
            ticker: conf.ticker
        });
    }

    protected checkTokens() {
        let params: any = {
            userID: this.cashtippr.getConfig().distributeTokens.userID,
            fingerprint: this.fingerPrint.getCanvasFingerPrint()
        }
        const badgerWallet = this.cashtippr.getBadgerWallet();
        if (badgerWallet.isInstalled() === true) {
            const badgerSlpAddress = badgerWallet.getDefaultSlpAddress();
            if (badgerSlpAddress)
                params.badgerSlpAddress = badgerSlpAddress;
        }
        this.webHelpers.postApi("/wp-json/cashtippr-slp-press/v1/check-tokens", params, (data) => {
            if (data.error === true) {
                this.cashtippr.window.console.error("CashTippr: Error checking for tokens:", data);
                return;
            }
            const amount = data.data[0].amount;
            if (amount > 0.0)
                this.startSendingTokens(amount, data.data[0].slpAddress);
        });
    }
}
