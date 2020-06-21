using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Drawing;
using System.Data;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;
using RightNow.AddIns.AddInViews;
using ccProcessing.cws;
using System.Xml;

namespace ccProcessing
{
    public partial class ccProcessingControl : UserControl, IDisposable
    {
        cwsModel model;
        IContact wsContact;
        IGenericObject wsDonation;
        public paymentMethods methods = new paymentMethods();
        private string[] allowedRefundStatus = { "Completed" };
        private string[] allowedChargeStatus = { "", "Declined" };
        private string[] allowedReversalStatus = { "Pending - Agent Initiated" };
        private string addPaymentTrackingId = "addPayment";
        private string makeChargeTrackingId = "makeCharge";
        private string transactionCompletedStatus = "Completed";
        WebBrowserNavigatedEventHandler handler;

        public ccProcessingControl()
        {
            InitializeComponent();
        }

        public void setupControl(cwsModel model, IContact contact, IGenericObject donation)
        {
            if (this.IsDisposed)
            {
                return;
            }
            this.Enabled = false;
            this.tableLayoutPanel1.Visible = true;
            this.wsNotReadyLbl.Visible = false;
            this.model = model;
            if (this.handler == null)
            {
                this.handler = new WebBrowserNavigatedEventHandler(frontStreamDisplay_navigating);
                this.frontStreamDisplay.Navigated += handler;
            }
            this.wsContact = contact;
            this.wsDonation = donation;

            BackgroundWorker bw = new BackgroundWorker();
            bw.DoWork += new DoWorkEventHandler(
                delegate(object o, DoWorkEventArgs args)
                {
                    cwsModel passedModel = args.Argument as cwsModel;
                    if (passedModel == null)
                    {
                        return;
                    }
                    //get some caching done in the model, this takes time so do it in a worker.
                    passedModel.getPaymentMethods(contact.ID);
                    passedModel.getTransactionStatusbyDonationId(donation.Id);
                });

            bw.RunWorkerCompleted += new RunWorkerCompletedEventHandler(
                delegate(object o, RunWorkerCompletedEventArgs args)
                {
                    if (this.IsDisposed)
                    {
                        return;
                    }
                    updatePaymentMethodGrid();
                    updateAllowedOperations();
                    if (frontStreamDisplay != null) { 
                    frontStreamDisplay.Navigate("about:blank");
                        }
                    frontStreamDisplay.Visible = false;
                    this.Enabled = true;
                });

            bw.RunWorkerAsync(this.model);
        }


        /**
         * Determine if we have a contact
         * If Not ask if the transaction is complete
         * If confirm close transaction, create closed transaction
         */
        internal bool closingAddTransaction()
        {
            if (this.model == null)
            {
                return true ;
            }
            String transStatus = this.model.getTransactionStatusbyDonationId(wsDonation.Id);
            IGenericField isCheck = this.wsDonation.GenericFields.Where(field => field.Name.Equals("isCheck")).First() as IGenericField;
            //only proceed if we're processing a check
            if (isCheck.DataValue.Value == null || isCheck.DataValue.Value.ToString() != "1")
            {
                return true;
            }

            if (transStatus == "")
            {
                DialogResult result = MessageBox.Show("No completed transaction is associated with this donation.  If this donation is complete, it will not be reported properly.  Would you like to add a completed transaction to this donation before closing the workspace?", "Create Transaction?", MessageBoxButtons.YesNoCancel);

                switch (result)
                {
                    case DialogResult.Cancel:
                        return false;
                        break;

                    case DialogResult.No:
                        return true;
                        break;

                    case DialogResult.Yes:
                        long transId = this.model.createOrUpdateTransactionObj(wsDonation.Id, "Automated create of transaction for manually entered donation.", getPaymentAmount(), this.transactionCompletedStatus, 0, false, null);
                        if (transId > 0)
                        {
                            return true;
                        }
                        else
                        {
                            MessageBox.Show("There was an error creating the transaction.  Refresh the workspace and verify that transaction with 'Completed' status has been created");
                            return false;
                        }
                        break;

                    default:
                        return false;
                }
            }
            return true;
        }

        private void updateAllowedOperations()
        {
            string transStatus = this.model.getTransactionStatusbyDonationId(wsDonation.Id);
            if (!allowedRefundStatus.Contains(transStatus))
            {
                this.RefundBtn.Enabled = false;
            }
            else
            {
                this.RefundBtn.Enabled = true;
            }

            if (!allowedChargeStatus.Contains(transStatus))
            {
                this.ChargeNewMethodBtn.Enabled = false;
                this.addMethodBtn.Enabled = false;
                if (this.paymentMethodsGrid.Columns["ChargeCard"] != null)
                {
                    this.paymentMethodsGrid.Columns["ChargeCard"].Visible = false;
                }
                this.paymentMethodsGrid.Refresh();
            }
            else
            {
                this.ChargeNewMethodBtn.Enabled = true;
                this.addMethodBtn.Enabled = true;
                if (this.paymentMethodsGrid.Columns["ChargeCard"] != null)
                {
                    this.paymentMethodsGrid.Columns["ChargeCard"].Visible = true; 
                }
                this.paymentMethodsGrid.Refresh();
            }
        }

        private void updatePaymentMethodGrid()
        {
            this.paymentMethodsBindingSource.Clear();
            foreach (paymentMethod method in this.model.getPaymentMethods(wsContact.ID))
            {
                this.paymentMethodsBindingSource.Add(method);
            }

            this.paymentMethodsGrid.Refresh();
        }

        /**
         * 
         * Event handler to handle when frontstream page goes to cc transaction completed url
         * 
         */
        private void frontStreamDisplay_navigating(object sender, WebBrowserNavigatedEventArgs args)
        {
            if ("https://" + args.Url.Host + args.Url.LocalPath == serverSettings.Instance.frontstreamHosted_postbackUrl)
            {
                postBackEventArgs postBackData = new postBackEventArgs();
                try
                {
                    postBackData.rawGetData = args.Url.Query;
                }
                catch (Exception e)
                {
                    MessageBox.Show("There was an issue adding this payment, check the transaction for further detail.");
                    model.createOrUpdateTransactionObj(this.wsDonation.Id, args.Url.Query);
                    frontStreamDisplay.Navigate("about:blank");
                    frontStreamDisplay.Visible = false;
                    return;
                }
                string paymentType = (postBackData.cardType == "Checking") ? "EFT" : "Credit Card";
                long paymentMethodId = model.createOrUpdatePaymentMethod(paymentType,
                                                            this.wsContact.ID,
                                                            postBackData.pnRef,
                                                            postBackData.lastFour,
                                                            -1,
                                                            postBackData.cardType,
                                                            postBackData.expMonth,
                                                            postBackData.expYear);
                model.createOrUpdateTransactionObj(this.wsDonation.Id, "Added payment method: " + paymentMethodId.ToString() + " to transaction", -1, null, paymentMethodId, null);

                if (paymentMethodId < 1)
                {
                    MessageBox.Show("The transaction was successful, but unable to store payment information for recurring use.");
                }
                if (postBackData.trackingId == this.addPaymentTrackingId)
                {
                    //reverse the $1 charge
                    if (initiateChargeReversal(paymentType))
                    {
                        MessageBox.Show("Successfully added new payment method");
                    }
                    else
                    {
                        MessageBox.Show("There may have been an issue adding this payment method.  Check the transaction for details and verify no charge has occured with merchant");
                    }
                }
                else
                {
                    this.completePaymentTransaction("Payment Completed", string.Join("\n", postBackData.rawGetData), "Completed", postBackData.pnRef);
                }
                frontStreamDisplay.Navigate("about:blank");
                frontStreamDisplay.Visible = false;
            }
            this.updateAllowedOperations();
        }

        public void displayMakeRefund()
        {
            this.Cursor = Cursors.WaitCursor;
            if (this.wsDonation == null)
            {
                resetControlState();
                return;
            }

            long transid = initiateRefundOnTransaction();
            if (transid < 1)
            {
                resetControlState();
                return;
            }

            aggregateTransactionData trans = model.getTransactionByDonationId(this.wsDonation.Id, this.wsContact.ID);
            if (trans == null)
            {
                this.paymentError("Unable to access transaction information");
                resetControlState();
                return;
            }
            string chargeType = null;
            frontstreamModel fsModel = new frontstreamModel();
            if (trans.paymentMethod != null)
            {
                chargeType = trans.paymentMethod.cardType;
            }

            frontStreamReturn fsReturn = null;
            if (chargeType != null && chargeType == "Checking")
            {
                fsReturn = fsModel.processCheck(trans.receiptPNRef, trans.amount, "Void");
            }
            else
            {
                fsReturn = fsModel.processCreditCard(trans.receiptPNRef, trans.amount, trans.transId, "Return");
            }

            if (fsReturn != null && fsReturn.result == 0)
            {
                string message = "Refunded $" + trans.amount + " to payment method with PR Ref. No. " + trans.pnRef;
                this.completePaymentTransaction(message, fsReturn.rawXmlResponseString, "Refunded", null);
                resetControlState();
                return;
            }
            else
            {
                this.paymentError("There was a problem with the transaction.  Message: " + fsReturn.Message + ". Check the transaction notes for further detail", fsReturn.rawXmlResponseString);
            }
            //get payment type refno and amount from transaction
            resetControlState();
        }

        private void resetControlState()
        {
            this.Cursor = Cursors.Default;
            this.updateAllowedOperations();
        }

        /**
         * 
         * 
         */
        private long initiateRefundOnTransaction()
        {
            if (this.wsDonation == null)
            {
                return -1;
            }
            string transStatus = model.getTransactionStatusbyDonationId(wsDonation.Id);

            if (!allowedRefundStatus.Contains(transStatus))
            {
                string message = "Unable to refund transaction with " + transStatus + " transaction status";
                this.completePaymentTransaction(message, message, transStatus, null);
                return -1;
            }

            //start the transaction
            long transid = this.model.createOrUpdateTransactionObj(wsDonation.Id, "Request to initiate refund started", -1, "Pending - Agent Initiated", -1, null);
            if (transid < 1)
            {
                MessageBox.Show("Unable to access transaction");
                return -1;
            }
            this.updateAllowedOperations();
            return transid;
        }

        /**
         * 
         * 
         * 
         */
        private long initiateReversalOnTransaction()
        {
            if (this.wsDonation == null)
            {
                return -1;
            }
            string transStatus = model.getTransactionStatusbyDonationId(wsDonation.Id);

            if (!allowedReversalStatus.Contains(transStatus))
            {
                string message = "Unable to reverse transaction with " + transStatus + " transaction status";
                this.completePaymentTransaction(message, message, transStatus, null);
                return -1;
            }

            //start the transaction
            long transid = this.model.createOrUpdateTransactionObj(wsDonation.Id, "Request to initiate reversal started", -1, "Processing", -1, null);
            if (transid < 1)
            {
                MessageBox.Show("Unable to access transaction");
                return -1;
            }
            return transid;
        }

        private Boolean initiateChargeReversal(string paymentType)
        {
            if (this.wsDonation == null)
            {
                return false;
            }

            long transid = initiateReversalOnTransaction();
            if (transid < 1)
            {
                return false;
            }
            aggregateTransactionData trans = model.getTransactionByDonationId(this.wsDonation.Id, this.wsContact.ID);
            if (trans == null)
            {
                MessageBox.Show("Unable to access transaction information");
                return false;
            }



            frontstreamModel fsModel = new frontstreamModel();
            frontStreamReturn fsReturn = null;
            if (paymentType == "EFT")
            {
                fsReturn = fsModel.processCheck(trans.pnRef, "", "Void");
            }
            else
            {
                fsReturn = fsModel.processCreditCard(trans.pnRef, "", trans.transId, "Reversal");
            }


            if (fsReturn != null && fsReturn.result == 0)
            {
                string message = "Reversed Charge with PR Ref. No. " + trans.pnRef;
                this.model.createOrUpdateTransactionObj(this.wsDonation.Id, message + "\n\n" + fsReturn.rawXmlResponseString, -1, "Reversed", -1, true, trans.pnRef);

                return true;
            }
            else
            {
                this.paymentError("There was a problem with the transaction.  Message: " + fsReturn.RespMSG + ". Check the transaction notes for further detail", fsReturn.rawXmlResponseString);
            }

            return false;

        }


        public void displayMakePayment()
        {
            displayMakePayment(this.makeChargeTrackingId);
        }

        /*
         *
         * @todo: abstract the payment stuff (creating objects, etc.) to another class
         * 
         */
        public void displayMakePayment(string trackingId)
        {
            this.Cursor = Cursors.WaitCursor;
            if (this.wsDonation == null)
            {
                resetControlState(); return;
            }

            IGenericField amount = this.wsDonation.GenericFields.Where(field => field.Name.Equals("Amount")).First() as IGenericField;
            long transid = startOrCancelPaymentTransaction();
            if (transid < 1)
            {
                resetControlState(); return;
            }
            float paymentAmount = getPaymentAmount();
            if (paymentAmount < 1)
            {
                this.paymentError("Payment value less than $1");
                resetControlState();
                return;
            }
            navigateToPaymentPage(wsContact, paymentAmount, transid, trackingId);
            resetControlState();
        }

        private long startOrCancelPaymentTransaction()
        {
            return startOrCancelPaymentTransaction(-1);
        }

        /**
         * 
         * 
         */
        private long startOrCancelPaymentTransaction(long paymentMethodId)
        {

            if (this.wsDonation == null)
            {
                return -1;
            }
            string transStatus = model.getTransactionStatusbyDonationId(wsDonation.Id);
            if (!allowedChargeStatus.Contains(transStatus))
            {
                string message = "Unable to add payment to donation with " + transStatus + " transaction status";
                this.completePaymentTransaction(message, message, transStatus, null);
                return -1;
            }
            float amount = getPaymentAmount();
            if (amount < 1)
            {
                this.paymentError("Payment value less than $1");
                return -1;
            }
            //create transaction object
            long transid = this.model.createOrUpdateTransactionObj(wsDonation.Id, "Request to initiate payment started", amount, "Pending - Agent Initiated", paymentMethodId, null);
            if (transid < 1)
            {
                MessageBox.Show("Unable to create transaction");
                return -1;
            }
            return transid;
        }

        /**
         * Returns the value of the payment field on the donation workspace
         */
        private float getPaymentAmount()
        {
            if (this.wsDonation == null)
            {
                return -1;
            }

            //make sure we have all we need before proceeding: a saved contact and donation, a transaction, an amount, etc.
            //get amount
            IGenericField amount = this.wsDonation.GenericFields.Where(field => field.Name.Equals("Amount")).First() as IGenericField;
            if (amount == null || amount.DataValue == null)
            {
                MessageBox.Show("Total amount of donation not present.  Try saving before processing the payment");
                return -1;
            }
            return float.Parse(amount.DataValue.Value.ToString());
        }

        /**
         * Displays the browser control, and navigates to payment workspace.
         * 
         * Tracking id is used to differentiate between the 2 types of payments function: adding a payment method, 
         * or adding a payment method and charging that method.  This is handled on postback.
         */
        private void navigateToPaymentPage(IContact wsContact, float amount, long transid, string trackingId)
        {
            if (trackingId == this.addPaymentTrackingId)
            {
                amount = 1;
                //MessageBox.Show("make amount 1");
            }

            Dictionary<string, string> postVals = new Dictionary<string, string>();

            postVals.Add("EmailAddress", wsContact.EmailAddr);
            postVals.Add("FirstName", System.Uri.EscapeDataString(wsContact.NameFirst));
            postVals.Add("LastName", System.Uri.EscapeDataString(wsContact.NameLast));
            postVals.Add("PaymentAmount", amount.ToString());
            postVals.Add("BillingStreetAddress", System.Uri.EscapeDataString( wsContact.AddrStreet));
            postVals.Add("BillingStreetAddress2", "");
            postVals.Add("BillingCity", System.Uri.EscapeDataString(wsContact.AddrCity));
            postVals.Add("BillingStateOrProvince", "");
            postVals.Add("BillingPostalCode", System.Uri.EscapeDataString( wsContact.AddrPostalCode));
            postVals.Add("BillingCountry", "");
            postVals.Add("PaymentButtonText", "");
            postVals.Add("NotificationFlag", "0");
            postVals.Add("TrackingID", trackingId);
            postVals.Add("StyleSheetURL", "");
            postVals.Add("MerchantToken", serverSettings.Instance.frontstreamHosted_merchantToken);
            postVals.Add("PostbackURL", serverSettings.Instance.frontstreamHosted_postbackUrl);
            postVals.Add("PostBackRedirectURL", serverSettings.Instance.frontstreamHosted_postbackUrl);
            postVals.Add("PostBackErrorURL", serverSettings.Instance.frontstreamHosted_postbackUrl);
            postVals.Add("SetupMode", "Direct");
            postVals.Add("InvoiceNumber", transid.ToString());
            postVals.Add("HeaderImageURL", serverSettings.Instance.frontstreamHosted_headerUrl);
            postVals.Add("DirectUserName", serverSettings.Instance.frontstreamHosted_username);
            postVals.Add("DirectUserToken", System.Uri.EscapeDataString( serverSettings.Instance.frontstreamHosted_userToken));
            postVals.Add("DirectMerchantKey", serverSettings.Instance.frontstreamHosted_merchantKey);
            postVals.Add("NotificationType", "");
            //postVals.Add("buttonText2", buttonText);
            //postVals.Add("buttonData2", wsContact.Close);

            System.Text.UTF8Encoding encoding = new System.Text.UTF8Encoding();
            string postValsSTr = string.Join("&", postVals.Select(x => x.Key + "=" + x.Value).ToArray());
            byte[] postBytes = encoding.GetBytes(postValsSTr);
            string headers = "Content-Type: application/x-www-form-urlencoded";
            this.frontStreamDisplay.Navigate(serverSettings.Instance.frontstreamHosted_makePayment, "", postBytes, headers);
            this.frontStreamDisplay.Visible = true;
        }



        private void paymentMethodsGrid_CellClick(object sender, DataGridViewCellEventArgs e)
        {
            this.Cursor = Cursors.WaitCursor;
            if (e.RowIndex < 0 || e.ColumnIndex != paymentMethodsGrid.Columns["ChargeCard"].Index)
            {
                resetControlState();
                return;
            }

            string pnRef = paymentMethodsGrid.Rows[e.RowIndex].Cells["pnRefDataGridViewTextBoxColumn"].Value.ToString();
            if (pnRef == null || pnRef.Length < 1)
            {
                resetControlState(); return;
            }

            long paymentMethodId = long.Parse(paymentMethodsGrid.Rows[e.RowIndex].Cells["idDataGridViewTextBoxColumn"].Value.ToString());
            if (paymentMethodId < 1)
            {
                resetControlState();
                return;
            }

            DialogResult result = MessageBox.Show("Charge $" + this.getPaymentAmount() + " to payment method with PN Ref No. " + pnRef + "?", "Make Payment?", MessageBoxButtons.YesNo);
            if (result == DialogResult.No)
            {
                resetControlState(); return;
            }

            long transId = this.startOrCancelPaymentTransaction(paymentMethodId);
            if (transId < 1)
            {
                resetControlState(); return;
            }
            float paymentAmount = getPaymentAmount();
            if (paymentAmount < 1)
            {
                resetControlState();
                this.paymentError("Payment value less than $1");
                return;
            }

            frontstreamModel fsModel = new frontstreamModel();
            frontStreamReturn fsReturn;
            if (paymentMethodsGrid.Rows[e.RowIndex].Cells["cardTypeDataGridViewTextBoxColumn"].Value.ToString() == "Checking")
            {
                fsReturn = fsModel.processCheck(pnRef, paymentAmount.ToString(), transId, "RepeatSale");
            }
            else
            {
                fsReturn = fsModel.processCreditCard(pnRef, paymentAmount.ToString(), transId, "Sale");
            }

            if (fsReturn != null && fsReturn.result == 0)
            {
                string message = "Charged $" + paymentAmount + " with PR Ref. No. " + pnRef;
                //zc 1/6/16 here we need to get the receipt pnRef, not the payment pnref and pass it in
                string receiptPNRef = _getPNReffromXML(fsReturn.rawXmlResponseString);
                this.completePaymentTransaction(message, fsReturn.rawXmlResponseString, "Completed", receiptPNRef);
                resetControlState(); return;
            }
            else
            {
                string errorMsg = fsReturn.Message == null || fsReturn.Message.Length < 1 ? fsReturn.RespMSG : fsReturn.Message;
                this.completePaymentTransaction("There was a problem with the transaction.  Message: " + errorMsg + ". Check the transaction notes for further detail", fsReturn.rawXmlResponseString, "Declined", pnRef);
            }
            resetControlState();
        }

        /**
         * Updates the transaction with the passed status and writes a message to the transaction log and popup window
         */
        private void completePaymentTransaction(string message, string longMessage, string status, string PNRef)
        {
            if (message == longMessage)//this is where we need the pnref to go.
            {
                this.model.createOrUpdateTransactionObj(this.wsDonation.Id, longMessage, -1, status, -1, PNRef);
            }
            else
            {
                this.model.createOrUpdateTransactionObj(this.wsDonation.Id, message + "\n\n" + longMessage, -1, status, -1, PNRef);
            }
            resetControlState();
            MessageBox.Show(message);
            frontStreamDisplay.Navigate("about:blank");
            frontStreamDisplay.Visible = false;
            this.updateAllowedOperations();

        }

        /**
         * 
         */
        private void paymentError(string message, string longMessage)
        {
            completePaymentTransaction(message, longMessage, "Error", null);
        }

        /**
         * 
         */
        private void paymentError(string message)
        {
            paymentError(message, message);
        }

        private void RefundBtn_Click(object sender, EventArgs e)
        {
            displayMakeRefund();
        }

        private void chargeNewMethodBtn_Click(object sender, EventArgs e)
        {
            displayMakePayment();
        }

        private void addMethodBtn_Click(object sender, EventArgs e)
        {
            displayMakePayment(this.addPaymentTrackingId);
        }


        void IDisposable.Dispose()
        {
            return;
        }

        private string _getPNReffromXML(string xmlString)
        {
            string pnRef = "";
            int startIndex = xmlString.IndexOf("<PNRef>") + "<PNRef>".Length;
            int endIndex = xmlString.IndexOf("</PNRef>");
            pnRef = xmlString.Substring(startIndex, endIndex - startIndex);
            
            return pnRef;
        }


 
    }
}
