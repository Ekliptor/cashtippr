import {AbstractModule} from "../AbstractModule";
import {CashTippr} from "../CashTippr";

export class AdminControls extends AbstractModule {
    constructor(cashtippr: CashTippr) {
        super(cashtippr);
    }

    public init() {
        if (this.cashtippr.$("body").attr("class").indexOf("cashtippr") === -1)
            return; // not our plugin settings page

        this.cashtippr.getTooltips().initToolTips();
        this.cashtippr.$(this.cashtippr.window.document).ready(($) => {
            this.enableAdblockSettings();
        });
        this.cashtippr.$("#cashtippr_settings\\[detect_adblock\\]").on("change", (event) => {
            this.enableAdblockSettings();
        });
    }

    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################

    protected enableAdblockSettings() {
        const enabled = this.cashtippr.$("#cashtippr_settings\\[detect_adblock\\]").is(":checked") === true;
        const adblockSettingsSelector = "#cashtippr_settings\\[adblockDisable\\], #cashtippr_settings\\[adblock_page\\], #cashtippr_settings\\[adblockNoConflict\\]";
        if (enabled === false)
            this.cashtippr.$(adblockSettingsSelector).prop("disabled", true);
        else
            this.cashtippr.$(adblockSettingsSelector).removeProp("disabled");
    }
}
