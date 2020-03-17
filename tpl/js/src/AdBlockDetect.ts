import {AbstractPayment, CashTippr} from "./CashTippr";
import {WebHelpers} from "./WebHelpers";
import {AbstractModule} from "./AbstractModule";


export class AdBlockDetect extends AbstractModule {
    protected static loadedAdFrame = false;

    constructor(cashtippr: CashTippr, webHelpers: WebHelpers) {
        super(cashtippr, webHelpers);

        // TODO add option to store and load adblock lib files from /uploads/{random-filename}.js (created by PHP)
        this.cashtippr.$(this.cashtippr.window.document).ready(($) => {
            if (this.cashtippr.$("body").hasClass("wp-admin") === true)
                return; // don't run in admin panel
            else if (this.cashtippr.getConfig().detect_adblock === false || this.cashtippr.getConfig().tipAmount > 0.0)
                return;
            this.loadLib();
        });
    }

    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################

    protected loadLib() {
        // We look at whether FuckAdBlock already exists.
        const wnd: any = this.cashtippr.window;
        if(this.cashtippr.getConfig().adblockNoConflict !== true && (typeof wnd.fuckAdBlock !== 'undefined' || typeof wnd.FuckAdBlock !== 'undefined')) {
            // If this is the case, it means that something tries to usurp are identity
            // So, considering that it is a detection
            this.onAdBlockDetected();
        }
        else {
            wnd.fuckAdBlock = null; // prevent automatic instantiation
            const importFAB = this.cashtippr.window.document.createElement('script');
            importFAB.onload = () => {
                wnd.fuckAdBlock = new wnd.FuckAdBlock({
                    checkOnLoad: true, // At launch, check if AdBlock is enabled // Uses the method fuckAdBlock.check()
                    resetOnEnd: true, // At the end of the check, is that it removes all events added ?
                    loopCheckTime: 55, // The number of milliseconds between each check
                    loopMaxNumber: 6, // The number of negative checks after which there is considered that AdBlock is not enabled // Time (ms) = 50*(5-1) = 200ms (per default)
                    baitClass: 'pub_300x250 pub_300x250m pub_728x90 text-ad textAd banner_ad text_ad text_ads text-ads text-ad-links', // CSS class used by the bait caught AdBlock
                    baitStyle: 'width: 1px !important; height: 1px !important; position: absolute !important; left: -10000px !important; top: -1000px !important;', // CSS style used to hide the bait of the users
                    debug: false, // Displays the debug in the console (available only from version 3.2 and more)
                });

                // If all goes well, we configure FuckAdBlock
                wnd.fuckAdBlock.onDetected(this.onAdBlockDetected.bind(this));
                wnd.fuckAdBlock.onNotDetected(this.onAdBlockNotDetected.bind(this));
            };
            importFAB.onerror = () => {
                // If the script does not load (blocked, integrity error, ...)
                // Then a detection is triggered
                this.onAdBlockDetected();
            };
            importFAB.integrity = 'sha256-f5s2H2XgAi/4M/ipOOw8pxmim1o7cTOWL72QbKu0lSc=';
            importFAB.crossOrigin = 'anonymous';
            importFAB.src = this.cashtippr.getConfig().adBlockScript;
            this.cashtippr.window.document.head.appendChild(importFAB);
        }
    }

    public onPayment(payment: AbstractPayment) {
        //this.cashtippr.$(".dialog-adbl").remove(); // better reload the page because we don't know if the webmaster modified it more with our JS callback
        this.cashtippr.window.document.location.reload();
    }

    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################

    protected onAdBlockNotDetected(secondTry: boolean = false) {
        //console.log('AdBlock is not enabled');
        if (secondTry === false)
            this.loadAdFrame();
    }

    protected onAdBlockDetected(secondTry: boolean = false) {
        //console.log('AdBlock is enabled');
        const wnd: any = this.cashtippr.window;
        if (typeof wnd === "function")
            wnd.ctipAdblockDetected();
        // blank out the page immediately, even if a version of adblock tries to block the AJAX request below
        const disablePage = this.cashtippr.getConfig().adblockDisable === true;
        if (disablePage === true) {
            let dialog = this.webHelpers.translate(this.cashtippr.$("#ct-modal-adblock-dialog-tpl").html(), {
                dialog_class: "dialog-adbl",
                title: "",
                text: ""
            });
            this.cashtippr.$("body").append(dialog); // its position is fixed relative to the viewport
        }
        let data = {
            msg: disablePage === true ? "1" : "0"
        }
        this.webHelpers.getApi("/wp-json/cashtippr/v1/get-post-notice", data, (data) => { // always call this to fire PHP adblock detection hook
            this.cashtippr.$(".dialog-adbl").remove(); // we have to add it with content or else it doesn't resize properly
            if (disablePage !== true)
                return;
            if (data.error === true) {
                this.cashtippr.$("#ct-adbl-title").text(data.errorMsg);
                //this.cashtippr.$("#ct-adbl-content").html();
                return;
            }
            const postTitle = data.data[0].post.title;
            const postContent = data.data[0].post.content;
            let dialog = this.webHelpers.translate(this.cashtippr.$("#ct-modal-adblock-dialog-tpl").html(), {
                dialog_class: "dialog-adbl",
                title: postTitle,
                text: postContent
            }, true);
            this.cashtippr.$("body").append(dialog);
            // TODO generate 1-time addresses and enable qr-code. then check with WP cron for payment and store in session
            this.cashtippr.$("#ct-modal-adblock-dialog .ct-qrcode-wrap").css("display", "none");
        });
    }

    protected loadAdFrame() {
        if (AdBlockDetect.loadedAdFrame === true)
            return;
        AdBlockDetect.loadedAdFrame = true;
        const importScript = this.cashtippr.window.document.createElement('script');
        const wnd: any = this.cashtippr.window;
        importScript.onload = () => {
            setTimeout(() => { // delay check to be sure it's evaluated
                if (wnd.ctipAdblockOk !== true)
                    this.onAdBlockDetected(true);
                else
                    this.onAdBlockNotDetected(true);
            }, 100);
        };
        importScript.onerror = () => {
            this.onAdBlockDetected(true);
        };
        importScript.integrity = 'sha256-YrKAEKiVnq8uOpnEBYck9fbjgMBcJgjFcltgKGhtpkA=';
        importScript.crossOrigin = 'anonymous'; // not really needed here
        importScript.src = this.cashtippr.getConfig().adFrameBaitUrl;
        this.cashtippr.window.document.head.appendChild(importScript);
    }
}
