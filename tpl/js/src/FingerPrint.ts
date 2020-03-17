import {AbstractModule} from "./AbstractModule";
import {CashTippr} from "./CashTippr";

export class FingerPrint extends AbstractModule {
    constructor(cashtippr: CashTippr) {
        super(cashtippr);
    }

    public getCanvasFingerPrint(): string {
        this.cashtippr.$("body").append('<canvas id="ct-canvas" width="60" height="20"></canvas>');
        let testCanvas = this.cashtippr.window.document.getElementById('ct-canvas') as HTMLCanvasElement;
        let ctx = testCanvas.getContext('2d');
        // Text with lowercase/uppercase/punctuation symbols
        let txt = "CashTippr.,com <canvas> 1.0";
        ctx.textBaseline = "top";
        // The most common type
        ctx.font = "14px 'Arial'";
        ctx.textBaseline = "alphabetic";
        ctx.fillStyle = "#f60";
        ctx.fillRect(125,1,62,20);
        // Some tricks for color mixing to increase the difference in rendering
        ctx.fillStyle = "#069";
        ctx.fillText(txt, 2, 15);
        ctx.fillStyle = "rgba(102, 204, 0, 0.7)";
        ctx.fillText(txt, 4, 17);

        let b64 = testCanvas.toDataURL("image/png").replace("data:image/png;base64,","");
        this.cashtippr.$("#ct-canvas").remove();
        if (typeof this.cashtippr.window.atob !== "function")
            return "";
        let binImage = this.cashtippr.window.atob(b64);
        // CRC32 position: https://browserleaks.com/canvas#how-does-it-work 4 bytes from 16 to 12 byte from the end of file
        let crc = this.bin2hex(binImage.slice(-16,-12));
        return crc;
    }

    // TODO we could read a lot more properties from window.navigator. but better to value a visitors privacy

    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################

    // https://gist.github.com/cythrawll/8603752
    protected bin2hex(s: string): string {
        // From: http://phpjs.org/functions
        // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +   bugfixed by: Onno Marsman
        // +   bugfixed by: Linuxworld
        // +   improved by: ntoniazzi (http://phpjs.org/functions/bin2hex:361#comment_177616)
        // *     example 1: bin2hex('Kev');
        // *     returns 1: '4b6576'
        // *     example 2: bin2hex(String.fromCharCode(0x00));
        // *     returns 2: '00'
        let i, l, o = "", n;
        s += "";

        for (i = 0, l = s.length; i < l; i++) {
            n = s.charCodeAt(i).toString(16)
            o += n.length < 2 ? "0" + n : n;
        }
        return o;
    }
}
