import {Tooltips} from "./admin/Tooltips";
import {WebHelpers, WebHelpersConfig} from "./WebHelpers";
import {BlurryImage} from "./BlurryImage";
import {Shout} from "./Shout";
import {QrCode} from "./QrCode";
import {BadgerWallet} from "./BadgerWallet";
import {SlpPress, SlpPressConfig} from "./SlpPress";
import {Woocommerce} from "./Woocommerce";
import {AdBlockDetect} from "./AdBlockDetect";
import {AdminControls} from "./admin/AdminControls";

export interface BitcoinCashConversionRate {
    [fiatCurrency: string]: number;
}
export interface CashTipprConfig extends WebHelpersConfig {
    // same keys as in php
    show_search_engines: boolean;
    display_currency: string;
    rate: BitcoinCashConversionRate;
    ajaxConfirm?: boolean;
    keepTransaction?: boolean; // keep the transaction in mysql so that plugin addons can use them after payment
    paymentCommaDigits: number;

    detect_adblock: boolean;
    adblockDisable: boolean;
    adBlockScript: string;
    adblockNoConflict: boolean;
    adFrameBaitUrl: string;
    tipAmount: number;

    // SLP Press
    distributeTokens?: SlpPressConfig;

    // present after order is placed
    orderID?: number;
    nonce?: string;
    checkPaymentIntervalSec: number;

    // localizations
    badgerLocked: string;
    paidTxt: string;
}

export interface CashTipprAdminConfig extends WebHelpersConfig {
    //loadBlockchainApi: boolean;
    adminSlpWallet?: string;
    slpDistributeNonce?: string;

    // localizations
    distributeTokens?: SlpPressConfig;
}

export interface AbstractPayment {
    domID: string; // the full ID of the wrapper div for the payment control
    txid: string; // the blockchain transaction ID/hash
    amount: number; // the amount in the website's currency (USD,...)
}

export interface CashtipprApiRes {
    error: boolean;
    errorMsg: string;
    data: any[];
}

export class CashTippr {
    protected static readonly CONSENT_COOKIE_NAME = "ct-ck";
    protected static readonly CONFIRM_COOKIES_MSG = "#ct-cookieMsg";
    protected static readonly CONFIRM_COOKIES_BTN = "#ct-confirmCookies";
    // TODO separate entryPoints + classes for admin + public code? but tooltips and other admin stuff can be used publicly too (and is quite small)

    public readonly window: Window;
    public readonly $: JQueryStatic;
    public readonly badger: BadgerWallet;
    public readonly qr: QrCode;
    public readonly adBlockDetect: AdBlockDetect;
    public readonly blurryImage: BlurryImage;
    public readonly shout: Shout;
    public readonly woocommerce: Woocommerce;
    public readonly slpPress: SlpPress;

    protected config: CashTipprConfig;
    protected adminConfig:CashTipprAdminConfig;
    protected webHelpers: WebHelpers;
    protected adminControls: AdminControls;
    protected tooltips: Tooltips;

    constructor(window: Window, $: JQueryStatic) {
        this.window = window;
        this.$ = $;
        this.config = this.window['cashtipprCfg'] || {};
        this.addDefaultConfig(this.config);

        this.webHelpers = new WebHelpers(this.window, this.$, this.config);
        this.tooltips = new Tooltips(this, this.webHelpers);
        this.adminControls = new AdminControls(this);
        this.badger = new BadgerWallet(this, this.webHelpers, true);
        this.qr = new QrCode(this, this.webHelpers);
        this.adBlockDetect = new AdBlockDetect(this, this.webHelpers);
        this.blurryImage = new BlurryImage(this, this.webHelpers);
        this.shout = new Shout(this, this.webHelpers);
        this.woocommerce = new Woocommerce(this, this.webHelpers);
        this.slpPress = new SlpPress(this);
        this.$(this.window.document).ready(($) => {
            this.adminConfig = this.window['cashtipprAdminCfg'] || {}; // JS bundle is included early on admin page for AdminSettings
            if (this.window['cashtipprAdminCfg']) {
                this.addDefaultConfig(this.adminConfig);
                this.webHelpers.setConfig(this.adminConfig);
            }
            this.adminControls.init();
            this.webHelpers.checkCookieConsent();
        });
    }

    public static toSatoshis(bch: number) {
        return Math.floor(bch * 100000000);
    }

    public getConfig() {
        return this.config;
    }

    public getAdminConfig() {
        return this.adminConfig;
    }

    public getTooltips() {
        return this.tooltips;
    }

    public getWebHelpers() {
        return this.webHelpers;
    }

    public getBadgerWallet() {
        return this.badger;
    }

    public getPluginPaymentID(paymentControlsWrapper: JQuery) {
        return paymentControlsWrapper.attr("id").replace(/^ct-btn-wrap-/, "");
    }

    public addCopyInputListeners() {
        this.$(".ct-copy-field").click((event) => {
            event.preventDefault();
            const inputEl = this.$(event.target).parent().find("input[type='text']");
            //let text = inputEl.val();
            inputEl.select();
            try {
                this.window.document.execCommand("copy");
            }
            catch (err) { // try-catch shouldn't be needed except for some really old browsers
            }
            inputEl.select(); // ensure it's selected again for iOS devices to copy it manually. not working, remove?
        });
        this.$("#ct-qrcode-form input[type='text']").click((event) => {
            this.$(event.target).select();
        });
        if (this.webHelpers.isAppleIOS() === true) // copy to clipboard button doesn't work there
            this.$("#ct-qrcode-form .ct-copy-field").addClass("hidden");
    }

    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################

    protected addDefaultConfig(config: WebHelpersConfig) {
        config.consentCookieName = CashTippr.CONSENT_COOKIE_NAME;
        config.confirmCookiesMsg = CashTippr.CONFIRM_COOKIES_MSG;
        config.confirmCookiesBtn = CashTippr.CONFIRM_COOKIES_BTN;
    }
}
