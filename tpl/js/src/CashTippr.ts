import {Tooltips} from "./admin/Tooltips";
import {WebHelpers, WebHelpersConfig} from "./WebHelpers";
import {BlurryImage} from "./BlurryImage";
import {Shout} from "./Shout";
import {QrCode} from "./QrCode";
import {BadgerWallet} from "./BadgerWallet";

export interface BitcoinCashConversionRate {
    [fiatCurrency: string]: number;
}
export interface CashTipprConfig extends WebHelpersConfig {
    // same keys as in php
    show_search_engines: boolean;
    display_currency: string;
    rate: BitcoinCashConversionRate;
}

export interface AbstractPayment {
    domID: string; // the full ID of the wrapper div for the payment control
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
    public readonly blurryImage: BlurryImage;
    public readonly shout: Shout;

    protected config: CashTipprConfig;
    protected webHelpers: WebHelpers;
    protected tooltips = new Tooltips(this);

    constructor(window: Window, $: JQueryStatic) {
        this.window = window;
        this.$ = $;
        this.config = this.window['cashtipprCfg'] || {};
        this.config.consentCookieName = CashTippr.CONSENT_COOKIE_NAME;
        this.config.confirmCookiesMsg = CashTippr.CONFIRM_COOKIES_MSG;
        this.config.confirmCookiesBtn = CashTippr.CONFIRM_COOKIES_BTN;

        this.webHelpers = new WebHelpers(this.window, this.$, this.config);
        this.badger = new BadgerWallet(this, this.webHelpers, true);
        this.qr = new QrCode(this, this.webHelpers);
        this.blurryImage = new BlurryImage(this);
        this.shout = new Shout(this);
        this.$(this.window.document).ready(($) => {
            this.tooltips.initToolTips();
            this.webHelpers.checkCookieConsent();
        });
    }

    public static toSatoshis(bch: number) {
        return Math.floor(bch * 100000000);
    }

    public getConfig() {
        return this.config;
    }

    public getWebHelpers() {
        return this.webHelpers;
    }

    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################
}