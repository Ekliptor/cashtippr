
export class ReaderTokens {
    public readonly id: number;
    public amount: number;
    public address: string;

    constructor(id: number) {
        this.id = typeof id === "number" ? id : parseInt(id);
    }

    public static fromJson(json: any): ReaderTokens {
        let token = new ReaderTokens(json.id);
        token.amount = parseFloat(json.amount);
        token.address = json.address;
        return token;
    }

    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################

}
