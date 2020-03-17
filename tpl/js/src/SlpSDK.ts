import {AbstractModule} from "./AbstractModule";
import {CashTippr} from "./CashTippr";

export interface ICreateWalletResult {
    address: string;
    addressSlp: string;
    mnemonic: string;
    addressList: string[]; // an array of all addresses created (if multiple). the 1st address is the same as 'address'
    addressListSlp: string[];
}

export class SlpSDK extends AbstractModule {
    protected static readonly CREATE_ADDRESS_COUNT = 10;
    protected static readonly CHECK_ADDRESS_COUNT = 100;

    protected className: string;
    protected slp: any;
    protected mnemonicLanguage = "english"; // TODO set this depending on user locale
    protected config = {
        testnet: false,
        //restUrl: "https://trest.bitcoin.com/v2/" // testnet
        restUrl: "https://rest.bitcoin.com/v2/"
    }

    constructor(cashtippr: CashTippr) {
        super(cashtippr);
        this.className = this.constructor["name"];
        const wnd: any = window;
        this.slp = new wnd.ekliptor.blockchainAPI.SLP({restURL: this.config.restUrl});

        // https://github.com/Bitcoin-com/wormhole-sdk/blob/master/examples/create-wallet/create-wallet.js
        //const that = this; // getInfo() is another scope
        this.slp.Blockchain.getBlockchainInfo().then((result) => {
            this.cashtippr.window.console.debug("Connected to %s blockchain %o", this.className, result);
        }).catch((err) => {
            this.cashtippr.window.console.error("Failed to connect to %s blockchain %o", this.className, err);
        })
    }

    public async createWallet(/*slpAddress: boolean*/): Promise<ICreateWalletResult> {
        // create 128 bit BIP39 mnemonic
        const mnemonic = this.slp.Mnemonic.generate(/*256*/128, this.slp.Mnemonic.wordLists()[this.mnemonicLanguage]);
        // TODO generate entropy? https://developer.bitcoin.com/slp/docs/js/hdnode

        // root seed buffer
        const rootSeed = this.slp.Mnemonic.toSeed(mnemonic)

        // master HDNode
        //const config = await this.loadConfig();
        let masterHDNode
        try {
            //masterHDNode = config.node.slp.testnet ? this.slp.HDNode.fromSeed(rootSeed, "testnet") : this.slp.HDNode.fromSeed(rootSeed);
            masterHDNode = this.slp.HDNode.fromSeed(rootSeed, this.config.testnet ? "testnet" : undefined);
        } catch (err) { // TODO currently issues with bitcoincashjs-lib in browser, working in node https://github.com/Bitcoin-com/slp-sdk/issues/76
            // @ts-ignore
            return null;
        }

        // HDNode of BIP44 account
        //const account = this.wormhole.HDNode.derivePath(masterHDNode, "m/44'/145'/0'")

        let addrsses: string[] = [];
        let addrssesSlp: string[] = [];
        for (let i = 0; i < SlpSDK.CREATE_ADDRESS_COUNT; i++)
        {
            const childNode = masterHDNode.derivePath(`m/44'/145'/0'/0/${i}`);
            addrsses.push(this.slp.HDNode.toCashAddress(childNode));
            addrssesSlp.push(this.slp.HDNode.toSLPAddress(childNode));

            // Save the first seed address for use
            /*
            if (i === 0) {
                //outObj.legacyAddress = this.slp.HDNode.toLegacyAddress(childNode)
            }
            */
        }

        // derive the first external change address HDNode which is going to spend utxo
        //const change = this.slp.HDNode.derivePath(account, "0/0");

        // get the cash address
        //let cashAddr = this.slp.HDNode.toCashAddress(change)

        return {
            address: addrsses[0],
            addressSlp: addrssesSlp[0],
            mnemonic: mnemonic,
            addressList: addrsses,
            addressListSlp: addrssesSlp
        };
    }

    public async loginToWallet(address: string, mnemonic: string, slp: boolean): Promise<boolean> {
        // TODO check with Mnemonic.validate() first?
        // root seed buffer
        const rootSeed = this.slp.Mnemonic.toSeed(mnemonic)

        // master HDNode
        const masterHDNode = this.slp.HDNode.fromSeed(rootSeed, this.config.testnet ? "testnet" : undefined)

        // HDNode of BIP44 account
        //const account = this.slp.HDNode.derivePath(masterHDNode, "m/44'/145'/0'")
        for (let i = 0; i < SlpSDK.CHECK_ADDRESS_COUNT; i++)
        {
            const childNode = masterHDNode.derivePath(`m/44'/145'/0'/0/${i}`);
            let curAddress = slp === true ? this.slp.HDNode.toSLPAddress(childNode) : this.slp.HDNode.toCashAddress(childNode);
            if (curAddress === address)
                return true;
        }
        return false;
    }

    public async getWalletAddresses(mnemonic: string, numberAddresses: number = -1): Promise<ICreateWalletResult> {
        // root seed buffer
        const rootSeed = this.slp.Mnemonic.toSeed(mnemonic)

        // master HDNode
        let masterHDNode
        try {
            masterHDNode = this.slp.HDNode.fromSeed(rootSeed, this.config.testnet ? "testnet" : undefined);
        } catch (err) {
            // @ts-ignore
            return null;
        }

        let addrsses: string[] = [];
        let addrssesSlp: string[] = [];
        if (numberAddresses === -1)
            numberAddresses = SlpSDK.CREATE_ADDRESS_COUNT;
        for (let i = 0; i < numberAddresses; i++)
        {
            const childNode = masterHDNode.derivePath(`m/44'/145'/0'/0/${i}`);
            addrsses.push(this.slp.HDNode.toCashAddress(childNode));
            addrssesSlp.push(this.slp.HDNode.toSLPAddress(childNode));
        }

        return {
            address: addrsses[0],
            addressSlp: addrssesSlp[0],
            mnemonic: mnemonic,
            addressList: addrsses,
            addressListSlp: addrssesSlp
        };
    }

    public async getBalanceByKey(mnemonic: string): Promise<number> {
        // root seed buffer
        const rootSeed = this.slp.Mnemonic.toSeed(mnemonic)

        // master HDNode
        const masterHDNode = this.slp.HDNode.fromSeed(rootSeed, this.config.testnet ? "testnet" : undefined)

        // HDNode of BIP44 account
        //const account = this.slp.HDNode.derivePath(masterHDNode, "m/44'/145'/0'")
        const childNode = masterHDNode.derivePath(`m/44'/145'/0'/0/0`);

        //const change = this.slp.HDNode.derivePath(account, "0/0")
        const address = this.slp.HDNode.toCashAddress(childNode);

        // get the cash address
        //const cashAddress = this.slp.HDNode.toCashAddress(change)
        //const slpAddress = SLP.Address.toSLPAddress(cashAddress)
        return this.getBalance(address);
    }

    public async getBalance(cashAddress: string): Promise<number> {
        try {
            // get BCH balance
            const balance = await this.slp.Address.details(cashAddress)
            if (!balance || balance.balance < 0.0)
                return 0.0;
            /**
             * balance: 0.00000546
             balanceSat: 546
             totalReceived: 0.00000546
             totalReceivedSat: 546
             totalSent: 0
             totalSentSat: 0
             unconfirmedBalance: 0
             unconfirmedBalanceSat: 0
             unconfirmedTxApperances: 0
             txApperances: 1
             transactions: ["5951d4da192907a133a5f615e4e75abe586dad2972a7188722e9c5551582d8c8"]
             legacyAddress: "18qirAFiEYTr16EQcN9fCsENQJheBxUZdj"
             cashAddress: "bitcoincash:qp2ll3maq7668s2a9hvae7p6qkkqrzzm0s3s359hx2"
             slpAddress: "simpleledger:qp2ll3maq7668s2a9hvae7p6qkkqrzzm0sat60shc5"
             currentPage: 0
             pagesTotal: 1
             */
            return balance.balance;

        } catch (err) {
            this.cashtippr.window.console.error(`Error in getBalance: `, err)
            return 0.0;
        }
    }

    public async getTokenBalance(slpAddress: string, tokenID: string): Promise<number> {
        try {
            const tokens = await this.slp.Utils.balancesForAddress(slpAddress);
            if (Array.isArray(tokens) === false)
                return 0.0;
            /**
             * [{}tokenId: "7278363093d3b899e0e1286ff681bf50d7ddc3c2a68565df743d0efc54c0e7fd"
             balance: 10000
             balanceString: "10000"
             slpAddress: "simpleledger:qp2ll3maq7668s2a9hvae7p6qkkqrzzm0sat60shc5"
             decimalCount: 8}, ... ]
             */
            for (let i = 0; i < tokens.length; i++)
            {
                if (tokens[i].tokenId !== tokenID)
                    continue;
                return tokens[i].balance;
            }
            return 0.0;
        }
        catch (err) {
            this.cashtippr.window.console.error("Error getting token balances:", err);
            return 0.0;
        }
    }

    public async sendToken(mnemonic: string, toSlpAddress: string, tokenID: string, tokenAmount: number): Promise<boolean> {
        try {
            // root seed buffer
            const rootSeed = this.slp.Mnemonic.toSeed(mnemonic)

            // master HDNode
            const masterHDNode = this.slp.HDNode.fromSeed(rootSeed, this.config.testnet ? "testnet" : undefined)

            // HDNode of BIP44 account
            const account = this.slp.HDNode.derivePath(masterHDNode, "m/44'/145'/0'")
            const change = this.slp.HDNode.derivePath(account, "0/0")

            // get the cash address
            const cashAddress = this.slp.HDNode.toCashAddress(change)
            const slpAddress = this.slp.HDNode.toSLPAddress(change)

            const fundingAddress = slpAddress
            const fundingWif = this.slp.HDNode.toWIF(change) // <-- compressed WIF format
            const tokenReceiverAddress = toSlpAddress;
            const bchChangeReceiverAddress = cashAddress

            // Create a config object for minting
            const sendConfig = {
                fundingAddress,
                fundingWif,
                tokenReceiverAddress,
                bchChangeReceiverAddress,
                tokenId: tokenID,
                amount: tokenAmount
            }

            // Generate, sign, and broadcast a hex-encoded transaction for sending the tokens.
            const sendTxId = await this.slp.TokenType1.send(sendConfig);
            return sendTxId != "";
        }
        catch (err) {
            this.cashtippr.window.console.error(`Error in sendToken: `, err)
            return false;
        }
    }

    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################

}
